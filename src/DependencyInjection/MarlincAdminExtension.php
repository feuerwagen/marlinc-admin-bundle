<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 14:17
 */

namespace Marlinc\AdminBundle\DependencyInjection;

use Marlinc\AdminBundle\Controller\ExtraAdminController;
use Marlinc\SonataExtraAdminBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class MarlincAdminExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('sonata_admin')) {
            // Override templates & assets
            // TODO: This is really crude and messy:
            //  - Update the templates (figure out whats really needed)
            //  - Only load assets that are part of the bundle and it's dependencies
            /*
            $container->prependExtensionConfig('sonata_admin', [
                'templates' => [
                    'layout' => '@MarlincAdmin/layout.html.twig',
                    'list' => '@MarlincAdmin/list/list.html.twig',
                    'base_list_field' => '@MarlincAdmin/list/base_list_field.html.twig',
                ],
                'assets' => [
                    'remove_javascripts' => [
                        'bundles/sonatacore/vendor/select2/select2.min.js'
                    ],
                    'extra_javascripts' => [
                        'vendor/select2/dist/js/select2.full.min.js',
                        'bundles/marlincselect2entity/js/select2entity.js',
                        'bundles/marlincadmin/js/form-dependency.js'
                    ],
                    'remove_stylesheets' => [
                        'bundles/sonatacore/vendor/select2/select2.css',
                        'bundles/sonatacore/vendor/select2-bootstrap-css/select2-bootstrap.min.css',
                        'bundles/sonataadmin/vendor/admin-lte/dist/css/AdminLTE.min.css' // Remove and re-add for correct file order
                    ],
                    'extra_stylesheets' => [
                        'vendor/select2/dist/css/select2.min.css',
                        'vendor/select2-bootstrap-theme/dist/select2-bootstrap.min.css',
                        'bundles/sonataadmin/vendor/admin-lte/dist/css/AdminLTE.min.css',
                        'bundles/sonatatranslation/css/sonata-translation.css'
                    ]
                ]
            ]);//*/
        }

        if ($container->hasExtension('sonata_doctrine_orm_admin')) {
            // add custom form widgets
            // TODO: Check each widget if it still works as intended
            /*$container->prependExtensionConfig('sonata_doctrine_orm_admin', [
                'templates' => [
                    'form' => ['@MarlincAdmin/form/form_layout.html.twig']
                ]
            ]);//*/
        }

    }

    public function load(array $configs, ContainerBuilder $container)
    {

        $configs = $this->fixTemplatesConfiguration($configs);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('marlinc_admin.templates', $config['templates']);

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.xml');

        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['SonataDoctrineORMAdminBundle'])) {
            $loader->load('ORM/sortable.xml');

            if ($container->hasDefinition('stof_doctrine_extensions.listener.loggable')) {
                $loader->load('ORM/audit.xml');
            }

            if ($container->hasDefinition('stof_doctrine_extensions.listener.softdeleteable')) {
                $loader->load('ORM/trash.xml');
            }
        }

        $container->registerForAutoconfiguration(ExtraAdminController::class)
            ->addTag('controller.service_arguments');

    }

    protected function fixTemplatesConfiguration(array $configs)
    {
        $defaultConfig = array(
            'templates' => array(
                'types' => array(
                    'list' => array(
                        'image' => '@MarlincAdmin/CRUD/list_image.html.twig',
                        'string_template' => '@MarlincAdmin/CRUD/list_string_template.html.twig',
                        'progress_bar' => '@MarlincAdmin/CRUD/list_progress_bar.html.twig',
                        'label' => '@MarlincAdmin/CRUD/list_label.html.twig',
                        'badge' => '@MarlincAdmin/CRUD/list_badge.html.twig',
                    ),
                    'show' => array(
                        'image' => '@MarlincAdmin/CRUD/show_image.html.twig',
                        'string_template' => '@MarlincAdmin/CRUD/show_string_template.html.twig',
                        'progress_bar' => '@MarlincAdmin/CRUD/show_progress_bar.html.twig',
                        'label' => '@MarlincAdmin/CRUD/show_label.html.twig',
                        'badge' => '@MarlincAdmin/CRUD/show_badge.html.twig',
                    )
                )
            )
        );

        array_unshift($configs, $defaultConfig);

        return $configs;
    }
}