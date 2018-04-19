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
    public function getUsers(): Collection;

    public function setUsers(Collection $users);

    public function addUser(UserInterface $user);

    public function removeUser(UserInterface $user);

    public function hasUser(UserInterface $user);
}