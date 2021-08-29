<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\Model;

interface TrashManagerInterface
{
    /**
     * Set TrashReaderInterface service id responsible for an array of $classes.
     *
     * @param string        $serviceId
     * @param array<string> $classes
     */
    public function setReader(string $serviceId, array $classes): self;

    /**
     * Returns true if $class has TrashReaderInterface instance assigned.
     */
    public function hasReader(string $class): bool;

    /**
     * Get TrashReaderInterface service for $class.
     *
     * @throws \RuntimeException If no reader is available
     */
    public function getReader(string $class): TrashReaderInterface;
}
