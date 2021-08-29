<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\DependencyInjection\Compiler;


use Marlinc\AdminBundle\Bridge\AdminExporter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register export formats and writers with the @see AdminExporter
 */
final class AdminExporterCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(AdminExporter::class)) {
            return;
        }

        $definition = $container->findDefinition(AdminExporter::class);
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