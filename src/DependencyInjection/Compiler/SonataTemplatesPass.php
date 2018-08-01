<?php

namespace Marlinc\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SonataTemplatesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $extraTemplates = [
            'realdelete' => '@MarlincAdmin/edit/realdelete.html.twig'
        ];
        $doctrineTemplates = $container->getParameter('sonata_doctrine_orm_admin.templates');

        $container->setParameter('sonata_doctrine_orm_admin.templates', array_merge_recursive($extraTemplates, $doctrineTemplates));

        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition = $container->getDefinition($id);
                $definition->addMethodCall('setTemplate', ['realdelete', $extraTemplates['realdelete']]);
            }
        }
    }
}
