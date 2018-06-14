<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 19.04.18
 * Time: 09:28
 */

namespace Marlinc\AdminBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;

interface EntityAssignedUsersInterface
{
    /**
     * Return an array of field names leading to the parent entity which holds the user association.
     * If not applicable, return null.
     *
     * @return array|null
     */
    public static function getParents(): ?array;

    /**
     * Get all assigned users.
     *
     * @return Collection
     */
    public function getUsers(): Collection;

    /**
     * Set all assigned users.
     *
     * @param Collection $users
     * @return self
     */
    public function setUsers(Collection $users);

    /**
     * Add an assigned user.
     *
     * @param UserInterface $user
     * @return self
     */
    public function addUser(UserInterface $user);

    /**
     * Remove an assigned user.
     *
     * @param UserInterface $user
     * @return self
     */
    public function removeUser(UserInterface $user);

    /**
     * Check if the given user is assigned to this entity (or a parent entity).
     *
     * @param UserInterface $user
     * @return bool
     */
    public function hasUser(UserInterface $user): bool;
}