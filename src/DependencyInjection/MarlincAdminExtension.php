<?php
declare(strict_types=1);


namespace Marlinc\AdminBundle\DependencyInjection;

use Marlinc\AdminBundle\Controller\MarlincAdminController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class MarlincAdminExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
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

        // TODO: Set default controller config value for SonataAdmin
    }

}