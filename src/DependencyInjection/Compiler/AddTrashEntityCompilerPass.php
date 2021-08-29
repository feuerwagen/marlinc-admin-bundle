<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Register the trash reader for all entities with an admin that is enabled for trash view.
 */
class AddTrashEntityCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('doctrine.orm.entity_manager')) {
            return;
        }

        $trashedEntities = [];
        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $attributes) {
            if ($attributes[0]['manager_type'] != 'orm' || !isset($attributes[0]['trash']) || $attributes[0]['trash'] == false) {
                continue;
            }

            $definition = $container->getDefinition($id);
            $trashedEntities[] = $this->getModelName($container, $definition->getArgument(1));
        }

        $trashedEntities = array_unique($trashedEntities);

        $container->getDefinition('marlinc.admin.trash.manager')->addMethodCall('setReader', ['marlinc.admin.trash.manager', $trashedEntities]);
    }

    private function getModelName(ContainerBuilder $container, string $name): string
    {
        if ($name[0] == '%') {
            return $container->getParameter(substr($name, 1, -1));
        }

        return $name;
    }
}
