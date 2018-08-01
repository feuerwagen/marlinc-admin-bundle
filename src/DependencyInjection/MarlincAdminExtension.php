<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 14:17
 */

namespace Marlinc\AdminBundle\DependencyInjection;


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
            // override templates
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
                        'bundles/marlincselect2entity/js/select2entity.js'
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
            ]);
        }

        if ($container->hasExtension('sonata_doctrine_orm_admin')) {
            // add custom form widgets
            $container->prependExtensionConfig('sonata_doctrine_orm_admin', [
                'templates' => [
                    'form' => ['@MarlincAdmin/form/form_layout.html.twig']
                ]
            ]);
        }

        if ($container->hasExtension('picoss_sonata_extra_admin')) {
            // override templates
            $container->prependExtensionConfig('picoss_sonata_extra_admin', [
                'templates' => [
                    'inner_trash_list_row' => '@MarlincAdmin/list/list_trash_inner_row.html.twig',
                    'trash' => '@MarlincAdmin/list/trash.html.twig',
                ]
            ]);
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.xml');
    }
}