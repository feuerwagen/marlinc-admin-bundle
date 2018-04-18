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
    public function process(ContainerBuilder $container)
    {
        $method = 'setDatagridBuilder';

        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition = $container->getDefinition($id);

                if ($definition->hasMethodCall($method)) {
                    continue;
                }

                $definition->addMethodCall($method, [new Reference('marlinc.admin.builder.orm_datagrid')]);
            }
        }
    }
}