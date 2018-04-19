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
     * @return Collection
     */
    public function getUsers(): Collection;

    /**
     * @param Collection $users
     * @return self
     */
    public function setUsers(Collection $users);

    /**
     * @param UserInterface $user
     * @return self
     */
    public function addUser(UserInterface $user);

    /**
     * @param UserInterface $user
     * @return self
     */
    public function removeUser(UserInterface $user);

    /**
     * @param UserInterface $user
     * @return bool
     */
    public function hasUser(UserInterface $user): bool;
}