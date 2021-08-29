<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Admin;


use Marlinc\AdminBundle\Entity\EntityAssignedUsersInterface;
use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AssignedUserAdminExtension extends AbstractAdminExtension
{
    protected TokenStorageInterface $tokenStorage;

    private AuthorizationCheckerInterface $authChecker;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authChecker) {
        $this->tokenStorage = $tokenStorage;
        $this->authChecker = $authChecker;
    }

    public function configureQuery(AdminInterface $admin, ProxyQueryInterface $query): void
    {
        /** @var EntityAssignedUsersInterface $entityClass */
        $entityClass = $admin->getClass();

        // Skip if current entity class does not implement interface OR user is super admin
        if (
            !in_array(EntityAssignedUsersInterface::class, class_implements($entityClass))
            || $this->authChecker->isGranted('ROLE_SUPER_ADMIN')
        ) {
            return;
        }

        if (is_array($entityClass::getParents())) {
            // Retrieve current logged user token
            $user = $this->tokenStorage->getToken()->getUser();

            // Require join to user entity > get alias for parent entity and user table
            // Add condition to query to check for required relation to current user
            $alias = current($query->getRootAliases());

            foreach ($entityClass::getParents() as $parent) {
                $query->innerJoin($alias.'.'.$parent, 'a'.$parent);
                $alias = 'a'.$parent;
            }
            $query
                ->innerJoin($alias.'.users', 'ausers')
                ->andWhere('ausers = :user')
                ->setParameter('user', $user);
        }
    }
}