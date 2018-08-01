<?php

namespace Marlinc\AdminBundle\DependencyInjection\Compiler;

use Marlinc\AdminBundle\Route\EntityRouterBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddRouteBuilderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $attributes) {

            if ($attributes[0]['manager_type'] != 'orm') {
                continue;
            }

            if (!isset($attributes[0]['trash']) || $attributes[0]['trash'] == false) {
                continue;
            }

            $definition = $container->getDefinition($id);
            $definition->addMethodCall('setRouteBuilder', [new Reference(EntityRouterBuilder::class)]);
        }
    }
}
