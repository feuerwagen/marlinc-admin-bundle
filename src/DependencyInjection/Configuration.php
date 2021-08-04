<?php

/*
 * This file is part of the YesWeHack BugBounty backend
 *
 * (c) Romain Honel <romain.honel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Marlinc\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @author Romain Honel <romain.honel@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('marlinc_admin');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('templates')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('history')->defaultValue('@MarlincAdmin/edit/realdelete.html.twig')->cannotBeEmpty()->end()
                        ->scalarNode('history')->defaultValue('@MarlincAdmin/CRUD/history.html.twig')->cannotBeEmpty()->end()
                        ->scalarNode('history_revert')->defaultValue('@MarlincAdmin/CRUD/history_revert.html.twig')->cannotBeEmpty()->end()
                        ->scalarNode('history_revision_timestamp')->defaultValue('@MarlincAdmin/CRUD/history_revision_timestamp.html.twig')->cannotBeEmpty()->end()
                        ->scalarNode('trash')->defaultValue('@MarlincAdmin/CRUD/trash.html.twig')->cannotBeEmpty()->end()
                        ->scalarNode('untrash')->defaultValue('@MarlincAdmin/CRUD/untrash.html.twig')->cannotBeEmpty()->end()
                        ->scalarNode('inner_trash_list_row')->defaultValue('@MarlincAdmin/CRUD/list_trash_inner_row.html.twig')->cannotBeEmpty()->end()
                        ->scalarNode('button_trash')->defaultValue('@MarlincAdmin/Button/trash_button.html.twig')->cannotBeEmpty()->end()
                        ->arrayNode('types')
                            ->children()
                                ->arrayNode('list')
                                    ->useAttributeAsKey('name')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('show')
                                    ->useAttributeAsKey('name')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
