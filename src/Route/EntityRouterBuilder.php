<?php

namespace Marlinc\AdminBundle\Route;

use Sonata\AdminBundle\Model\AuditManagerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\RouteBuilderInterface;
use Sonata\AdminBundle\Route\RouteCollection;

class EntityRouterBuilder  implements RouteBuilderInterface
{
    private $decorated;
    private $manager;

    /**
     * @var TrashManagerInterface
     */
    protected $trashManager;

    /**
     * @param \Sonata\AdminBundle\Model\AuditManagerInterface $manager
     * @param TrashManagerInterface $trashManager
     */
    public function __construct(PathInfoBuilder $decorated,AuditManagerInterface $manager)
    {
        $this->decorated = $decorated;

        $this->manager = $manager;
    }
    /**
     * @param \Sonata\AdminBundle\Admin\AdminInterface $admin
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     */
    public function build(AdminInterface $admin, RouteCollection $collection)
    {
        $this->decorated->build($admin, $collection);

        if ($this->manager->hasReader($admin->getClass())) {
            $collection->add('history_revert', $admin->getRouterIdParameter() . '/history/{revision}/revert');
        }

    }
}
