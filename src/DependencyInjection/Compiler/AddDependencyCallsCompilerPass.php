<?php

namespace Marlinc\AdminBundle\DependencyInjection\Compiler;

use Doctrine\Common\Inflector\Inflector;
use Marlinc\AdminBundle\Admin\AdminWithTrash;
use Marlinc\AdminBundle\Builder\TrashDatagridBuilder;
use Sonata\AdminBundle\Datagrid\Pager;
use Sonata\AdminBundle\Templating\TemplateRegistry;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add all dependencies to the Admin class, this avoid to write too many lines
 * in the configuration files.
 */
class AddDependencyCallsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $tags) {
            $definition = $container->getDefinition($id);

            if (in_array(AdminWithTrash::class, class_implements($definition->getClass()))) {
                $definition->addMethodCall('setTrashDatagridBuilder', [TrashDatagridBuilder::class]);
            }
        }
    }
}
