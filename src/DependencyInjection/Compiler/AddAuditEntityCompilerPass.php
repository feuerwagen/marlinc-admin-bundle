<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;


/**
 * Register the audit reader for all entities with an admin that is enabled for audit.
 */
class AddAuditEntityCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('doctrine.orm.entity_manager')) {
            return;
        }

        $auditedEntities = array();
        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $attributes) {

            if ($attributes[0]['manager_type'] != 'orm') {
                continue;
            }

            if (!isset($attributes[0]['audit']) || $attributes[0]['audit'] == false) {
                continue;
            }

            $definition = $container->getDefinition($id);
            $auditedEntities[] = $this->getModelName($container, $definition->getArgument(1));
        }

        $auditedEntities = array_unique($auditedEntities);

        $container->getDefinition('sonata.admin.audit.manager')->addMethodCall('setReader', ['marlinc.admin.audit.orm.reader', $auditedEntities]);
    }

    private function getModelName(ContainerBuilder $container, string $name): string
    {
        if ($name[0] == '%') {
            return $container->getParameter(substr($name, 1, -1));
        }

        return $name;
    }
}
