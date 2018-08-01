<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 14:16
 */

namespace Marlinc\AdminBundle;


use Marlinc\AdminBundle\DependencyInjection\Compiler\AddRouteBuilderCompilerPass;
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
    }
}