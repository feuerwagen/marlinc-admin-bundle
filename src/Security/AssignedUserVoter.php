<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Security;


use Marlinc\AdminBundle\Entity\EntityAssignedUsersInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Vote on access restrictions to entities based on their relation to the current user.
 * @see EntityAssignedUsersInterface
 */
class AssignedUserVoter extends Voter
{
    /**
     * @var string[]
     */
    private array $attributes;

    /**
     * @var string[]
     */
    private array $superAdminRoles;

    private AccessDecisionManagerInterface $decisionManager;

    private Pool $adminPool;

    private SecurityHandlerInterface $securityHandler;

    public function __construct(AccessDecisionManagerInterface $decisionManager, SecurityHandlerInterface $securityHandler, Pool $adminPool, array $superAdminRoles)
    {
        $this->decisionManager = $decisionManager;
        $this->securityHandler = $securityHandler;
        $this->adminPool = $adminPool;
        $this->attributes = [ // TODO: Replace with constants / list provided by SonataAdminBundle?
            'VIEW',
            'EDIT',
            'HISTORY',
            'DELETE'
        ];
        $this->superAdminRoles = $superAdminRoles;
    }

    public function addAttribute(string $attribute): self
    {
        $this->attributes[] = $attribute;
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, $subject): bool
    {
        // Vote only on entities with assigned users.
        if (!$subject instanceof EntityAssignedUsersInterface) {
            return false;
        }

        // Vote only on entities with an admin class.
        if (($admin = $this->adminPool->getAdminByClass(get_class($subject))) === null) {
            return false;
        }

        $role = $this->securityHandler->getBaseRole($admin);
        $roles = array_map(function ($attr) use ($role) {
            return sprintf($role, $attr);
        }, $this->attributes);

        return in_array($attribute, $roles);
    }

    /**
     * @inheritDoc
     *
     * @param EntityAssignedUsersInterface $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // The user must be logged in.
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Skip for super admin.
        foreach ($this->superAdminRoles as $role) {
            if ($this->decisionManager->decide($token, [$role])) {
                return true;
            }
        }

        // Check if user has role matching the attribute + user is assigned to the entity
        if ($this->decisionManager->decide($token, [$attribute]) && $subject->hasUser($user)) {
            return true;
        }

        return false;
    }
}