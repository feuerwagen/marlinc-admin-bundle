<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;

interface EntityAssignedUsersInterface
{
    /**
     * Return an array of field names leading to the parent entity which holds the user association.
     * If not applicable, return null.
     */
    public static function getParents(): ?array;

    /**
     * Get all assigned users.
     *
     * @return Collection<UserInterface>
     */
    public function getUsers(): Collection;

    /**
     * Set all assigned users.
     *
     * @param Collection<UserInterface> $users
     */
    public function setUsers(Collection $users): self;

    /**
     * Add an assigned user.
     */
    public function addUser(UserInterface $user): self;

    /**
     * Remove an assigned user.
     */
    public function removeUser(UserInterface $user): self;

    /**
     * Check if the given user is assigned to this entity (or a parent entity).
     */
    public function hasUser(UserInterface $user): bool;
}