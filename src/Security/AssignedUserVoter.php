<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 19.04.18
 * Time: 09:35
 */

namespace Marlinc\AdminBundle\Security;


use Marlinc\AdminBundle\Entity\EntityAssignedUsersInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AssignedUserVoter extends Voter
{
    private $attributes = [
        'VIEW',
        'LIST',
        'EDIT',
        'DELETE'
    ];

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var Pool
     */
    private $adminPool;

    /**
     * @var SecurityHandlerInterface
     */
    private $securityHandler;

    public function __construct(AccessDecisionManagerInterface $decisionManager, SecurityHandlerInterface $securityHandler, Pool $adminPool)
    {
        $this->decisionManager = $decisionManager;
        $this->securityHandler = $securityHandler;
        $this->adminPool = $adminPool;
    }

    /**
     * @inheritDoc
     */
    protected function supports($attribute, $subject)
    {
        // Vote only on entities with assigned users.
        if (!$subject instanceof EntityAssignedUsersInterface) {
            return false;
        }

        // Vote only on entities with an admin class.
        if (($admin = $this->adminPool->getAdminByClass(get_class($subject))) !== null) {
            return false;
        }

        $role = $this->securityHandler->getBaseRole($admin);
        $roles = [];

        foreach ($this->attributes as $attribute) {
            $roles[] = sprintf($role, $attribute);
        }

        return in_array($attribute, $roles);
    }

    /**
     * @inheritDoc
     * @param EntityAssignedUsersInterface $subject
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // The user must be logged in.
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Skip for super admin.
        if ($this->decisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
            return true;
        }

        // Check if user has Sonata role matching the attribute + user is assigned to the entity
        if ($this->decisionManager->decide($token, [$attribute]) && $subject->hasUser($user)) {
            return true;
        }
    }
}