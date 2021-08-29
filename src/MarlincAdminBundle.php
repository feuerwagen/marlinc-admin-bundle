<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle;


use Marlinc\AdminBundle\DependencyInjection\Compiler\AddAuditEntityCompilerPass;
use Marlinc\AdminBundle\DependencyInjection\Compiler\AddRouteBuilderCompilerPass;
use Marlinc\AdminBundle\DependencyInjection\Compiler\AddTrashEntityCompilerPass;
use Marlinc\AdminBundle\DependencyInjection\Compiler\AdminExporterCompilerPass;
use Marlinc\AdminBundle\DependencyInjection\Compiler\SonataTemplatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarlincAdminBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AdminExporterCompilerPass());
        $container->addCompilerPass(new SonataTemplatesPass());
        $container->addCompilerPass(new AddRouteBuilderCompilerPass());
        $container->addCompilerPass(new AddTrashEntityCompilerPass());
        $container->addCompilerPass(new AddAuditEntityCompilerPass());
    }
}