<?php
declare(strict_types=1);

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
            'realdelete' => '@MarlincAdmin/edit/realdelete.html.twig',
            'history' => '@MarlincAdmin/edit/realdelete.html.twig',
            'history_revert' => '@MarlincAdmin/edit/realdelete.html.twig',
            'history_revision_timestamp' => '@MarlincAdmin/edit/realdelete.html.twig',
            'trash' => '@MarlincAdmin/edit/realdelete.html.twig',
            'untrash' => '@MarlincAdmin/edit/realdelete.html.twig',
            'inner_trash_list_row' => '@MarlincAdmin/edit/realdelete.html.twig',
            'button_trash' => '@MarlincAdmin/edit/realdelete.html.twig',
            'types' => [
                'list' => [
                    'image' => '@MarlincAdmin/CRUD/list_image.html.twig',
                    'string_template' => '@MarlincAdmin/CRUD/list_string_template.html.twig',
                    'progress_bar' => '@MarlincAdmin/CRUD/list_progress_bar.html.twig',
                    'label' => '@MarlincAdmin/CRUD/list_label.html.twig',
                    'badge' => '@MarlincAdmin/CRUD/list_badge.html.twig',
                ],
                'show' => [
                    'image' => '@MarlincAdmin/CRUD/show_image.html.twig',
                    'string_template' => '@MarlincAdmin/CRUD/show_string_template.html.twig',
                    'progress_bar' => '@MarlincAdmin/CRUD/show_progress_bar.html.twig',
                    'label' => '@MarlincAdmin/CRUD/show_label.html.twig',
                    'badge' => '@MarlincAdmin/CRUD/show_badge.html.twig',
                ]
            ]
        ];

        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['SonataDoctrineORMAdminBundle'])) {
            $templates = array_merge_recursive($extraTemplates, $container->getParameter('sonata_doctrine_orm_admin.templates'));
            $container->setParameter('sonata_doctrine_orm_admin.templates', $templates);

            // define the templates
            $container->getDefinition('sonata.admin.builder.orm_list')
                ->replaceArgument(1, $templates['types']['list']);

            $container->getDefinition('sonata.admin.builder.orm_show')
                ->replaceArgument(1, $templates['types']['show']);
        }

        $doctrineTemplates = $container->getParameter('sonata_doctrine_orm_admin.templates');

        $container->setParameter('sonata_doctrine_orm_admin.templates', array_merge_recursive($extraTemplates, $doctrineTemplates));

        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->addMethodCall('setTemplate', ['realdelete', $extraTemplates['realdelete']]);
            $definition->addMethodCall('setTemplate', ['history', $extraTemplates['history']]);
            $definition->addMethodCall('setTemplate', ['history_revert', $extraTemplates['history_revert']]);
            $definition->addMethodCall('setTemplate', ['history_revision_timestamp', $extraTemplates['history_revision_timestamp']]);
            $definition->addMethodCall('setTemplate', ['trash', $extraTemplates['trash']]);
            $definition->addMethodCall('setTemplate', ['untrash', $extraTemplates['untrash']]);
            $definition->addMethodCall('setTemplate', ['inner_trash_list_row', $extraTemplates['inner_trash_list_row']]);
            $definition->addMethodCall('setTemplate', ['button_trash', $extraTemplates['button_trash']]);
        }
    }
}
