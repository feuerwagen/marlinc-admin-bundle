<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 18.04.18
 * Time: 13:10
 */

namespace Marlinc\AdminBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class AdminOverridesCompilerPass implements CompilerPassInterface
{
    /**
     * Replace the default admin datagrid builder with our custom one.
     * This enables per-entity list access checks.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $method = 'setDatagridBuilder';

        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition = $container->getDefinition($id);

                foreach ($definition->getMethodCalls() as $call) {
                    if ($call[0] === $method && $call[1][0] != 'sonata.admin.builder.orm_datagrid') {
                        continue 2;
                    }
                }

                $definition->addMethodCall($method, [new Reference('marlinc.admin.builder.orm_datagrid')]);
            }
        }
    }
}