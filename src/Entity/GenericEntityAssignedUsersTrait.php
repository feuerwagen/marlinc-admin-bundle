<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 14.06.18
 * Time: 13:01
 */

namespace Marlinc\AdminBundle\Entity;


use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;

trait GenericEntityAssignedUsersTrait
{
    /**
     * @inheritDoc
     */
    public static function getParents(): ?array
    {
        return null; // No dedicated field for parent entities.
    }

    /**
     * @inheritDoc
     */
    public function getUsers(): Collection
    {
        // Nothing to do here, files don't have own users.
    }

    /**
     * @inheritDoc
     */
    public function setUsers(Collection $users)
    {
        // Nothing to do here, files don't have own users.
    }

    /**
     * @inheritDoc
     */
    public function addUser(UserInterface $user)
    {
        // Nothing to do here, files don't have own users.
    }

    /**
     * @inheritDoc
     */
    public function removeUser(UserInterface $user)
    {
        // Nothing to do here, files don't have own users.
    }

    /**
     * @inheritDoc
     */
    public function hasUser(UserInterface $user): bool
    {
        if ($this->getReferencingEntities()->isEmpty()) {
            return true;
        }

        foreach ($this->getReferencingEntities() as $entity) {
            if ($entity instanceof EntityAssignedUsersInterface && $entity->hasUser($user)) {
                return true;
            }
        }

        return false;
    }
}