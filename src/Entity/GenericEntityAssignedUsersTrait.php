<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Entity;


use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Implements @see EntityAssignedUsersInterface
 * TODO: Move to EntityBundle
 */
trait GenericEntityAssignedUsersTrait
{
    public static function getParents(): ?array
    {
        return null; // No dedicated field for parent entities.
    }

    public function getUsers(): Collection
    {
        // Nothing to do here, this entity doesn't have its own users.
    }

    public function setUsers(Collection $users)
    {
        // Nothing to do here, this entity doesn't have its own users.
    }

    public function addUser(UserInterface $user)
    {
        // Nothing to do here, this entity doesn't have its own users.
    }

    public function removeUser(UserInterface $user)
    {
        // Nothing to do here, this entity doesn't have its own users.
    }

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