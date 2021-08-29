<?php

namespace Marlinc\AdminBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;

class TrashManager implements TrashManagerInterface
{
    /**
     * @var array<string, string> Map service ID to FQCN
     */
    protected array $readers = [];

    protected ContainerInterface $container;

    /**
     * TODO: Don't rely on the container - use DI instead.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function setReader(string $serviceId, array $classes): self
    {
        $this->readers[$serviceId] = $classes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasReader(string $class): bool
    {
        foreach ($this->readers as $classes) {
            if (in_array($class, $classes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getReader(string $class): TrashReaderInterface
    {
        foreach ($this->readers as $readerId => $classes) {
            if (in_array($class, $classes)) {
                return $this->container->get($readerId);
            }
        }

        throw new \RuntimeException(sprintf('The class "%s" does not have any trash reader.', $class));
    }
}
