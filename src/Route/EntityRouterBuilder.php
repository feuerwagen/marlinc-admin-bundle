<?php

namespace Marlinc\AdminBundle\Route;

use Picoss\SonataExtraAdminBundle\Model\TrashManagerInterface;
use Sonata\AdminBundle\Model\AuditManagerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\RouteBuilderInterface;
use Sonata\AdminBundle\Route\PathInfoBuilder;
use Sonata\AdminBundle\Route\RouteCollection;

class EntityRouterBuilder extends PathInfoBuilder implements RouteBuilderInterface
{
    /**
     * @var TrashManagerInterface
     */
    protected $trashManager;

    /**
     * @param \Sonata\AdminBundle\Model\AuditManagerInterface $manager
     * @param TrashManagerInterface $trashManager
     */
    public function __construct(AuditManagerInterface $manager, TrashManagerInterface $trashManager)
    {
        parent::__construct($manager);

        $this->trashManager = $trashManager;
    }
    /**
     * @param \Sonata\AdminBundle\Admin\AdminInterface $admin
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     */
    public function build(AdminInterface $admin, RouteCollection $collection)
    {
        parent::build($admin, $collection);

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