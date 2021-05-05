<?php

namespace Marlinc\AdminBundle\Route;

use Sonata\AdminBundle\Model\AuditManagerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\RouteBuilderInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Route\PathInfoBuilder;
use Marlinc\SonataExtraAdminBundle\Model\TrashManager;

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
    public function __construct(PathInfoBuilder $decorated,AuditManagerInterface $manager, TrashManager $trashManager)
    {
        $this->decorated = $decorated;
        $this->trashManager = $trashManager;
        $this->manager = $manager;
    }
    /**
     * @param \Sonata\AdminBundle\Admin\AdminInterface $admin
     * @param \Sonata\AdminBundle\Route\RouteCollectionInterface $collection
     */
    public function build(AdminInterface $admin, RouteCollectionInterface $collection): void
    {
        $this->decorated->build($admin, $collection);

        if ($this->manager->hasReader($admin->getClass())) {
            $collection->add('history_revert', $admin->getRouterIdParameter() . '/history/{revision}/revert');
        }

        if ($this->trashManager->hasReader($admin->getClass())) {
            $collection->add('batch_trash', 'trash/batch');
            $collection->add('trash', 'trash');
            $collection->add('untrash', $admin->getRouterIdParameter() . '/untrash');
            $collection->add('realdelete', $admin->getRouterIdParameter() . '/realdelete');
        }

    }
}
