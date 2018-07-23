<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 23.07.18
 * Time: 15:28
 */

namespace Marlinc\AdminBundle\DependencyInjection\Compiler;


use Doctrine\Common\Inflector\Inflector;
use Marlinc\AdminBundle\Builder\ListBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddDependencyCallsCompilerPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $settings = $container->getParameter('sonata.admin.configuration.admin_services');
        $defaultAddServices = [
            'list_builder' => ListBuilder::class,
        ];
        $templates = $container->getParameter('sonata_doctrine_orm_admin.templates');

        // define the templates
        $container->getDefinition(ListBuilder::class)
            ->replaceArgument(1, $templates['types']['list']);

        // Replace list builder
        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition = $container->getDefinition($id);
                $overwriteAdminConfiguration = isset($settings[$id]) ? $settings[$id] : [];

                foreach ($defaultAddServices as $attr => $addServiceId) {
                    $method = 'set' . Inflector::classify($attr);

                    if (isset($overwriteAdminConfiguration[$attr]) || !$definition->hasMethodCall($method)) {
                        $args = [new Reference(isset($overwriteAdminConfiguration[$attr]) ? $overwriteAdminConfiguration[$attr] : $addServiceId)];
                        if ('translator' === $attr) {
                            $args[] = false;
                        }

                        $definition->addMethodCall($method, $args);
                    }
                }
            }
        }
    }
}