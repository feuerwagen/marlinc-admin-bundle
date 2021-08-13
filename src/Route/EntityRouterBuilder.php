<?php

namespace Marlinc\AdminBundle\Route;

use Marlinc\SonataExtraAdminBundle\Model\TrashManagerInterface;
use Sonata\AdminBundle\Model\AuditManagerInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\RouteBuilderInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Route\PathInfoBuilder;

class EntityRouterBuilder  implements RouteBuilderInterface
{
    private PathInfoBuilder $decorated;

    private AuditManagerInterface $auditManager;

    private TrashManagerInterface $trashManager;

    public function __construct(PathInfoBuilder $decorated, AuditManagerInterface $auditManager, TrashManagerInterface $trashManager)
    {
        $this->decorated = $decorated;
        $this->trashManager = $trashManager;
        $this->auditManager = $auditManager;
    }

    public function build(AdminInterface $admin, RouteCollectionInterface $collection): void
    {
        $this->decorated->build($admin, $collection);

        if ($this->auditManager->hasReader($admin->getClass())) {
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
