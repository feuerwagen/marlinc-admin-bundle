<?php

namespace Marlinc\AdminBundle\Model;

interface TrashReaderInterface
{
    /**
     * TODO: Interface / type for object
     * @param object $object The object to undelete.
     */
    public function restore($object): void;
}
