<?php

namespace Knp\Bundle\LastTweetsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('knp_last_tweets');

        $rootNode
            ->children()
                ->arrayNode('fetcher')
                ->treatNullLike(array('driver' => 'oauth'))
                ->treatTrueLike(array('driver' => 'oauth'))
                ->children()
                    ->scalarNode('driver')->defaultValue('oauth')->end()
                    ->arrayNode('options')->useAttributeAsKey(0)->prototype('scalar')->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
