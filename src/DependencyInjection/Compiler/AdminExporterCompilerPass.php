<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 03.07.17
 * Time: 10:24
 */

namespace Marlinc\AdminBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class AdminExporterCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('marlinc.admin.exporter')) {
            return;
        }

        $definition = $container->findDefinition('marlinc.admin.exporter');
        $formats = $container->findTaggedServiceIds('marlinc.exporter.format');

        foreach ($formats as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addFormat', [
                    new Reference($id),
                    $attributes['alias'],
                    $attributes['class']
                ]);
            }
        }

        $writers = $container->findTaggedServiceIds('sonata.exporter.writer');

        foreach (array_keys($writers) as $id) {
            $definition->addMethodCall('addWriter', [new Reference($id)]);
        }
    }
}