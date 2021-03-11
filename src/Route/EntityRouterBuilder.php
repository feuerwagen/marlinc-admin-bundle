<?php

namespace Marlinc\AdminBundle\Route;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\RouteBuilderInterface;
use Sonata\AdminBundle\Model\AuditManagerInterface;
use Sonata\AdminBundle\Route\PathInfoBuilder;

class EntityRouterBuilder implements RouteBuilderInterface
{
    /**
     * @var AuditManagerInterface
     */
    private $manager;

    public function __construct(AuditManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function build(AdminInterface $admin, RouteCollectionInterface $collection,PathInfoBuilder $pathInfoBuilder): void
    {
        $pathInfoBuilder->build($admin, $collection);

        if ($this->manager->hasReader($admin->getClass())) {
            $collection->add('history_revert', $admin->getRouterIdParameter() . '/history/{revision}/revert');
        }
    }
}