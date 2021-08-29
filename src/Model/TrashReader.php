<?php

/*
 * This file is part of the YesWeHack BugBounty backend
 *
 * (c) Romain Honel <romain.honel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Marlinc\AdminBundle\Model;

use Doctrine\ORM\EntityManagerInterface;

class TrashReader implements TrashReaderInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @inheritdoc
     */
    public function restore($object): void
    {
        $object->setDeletedAt(null);
        $this->em->flush();
    }
}