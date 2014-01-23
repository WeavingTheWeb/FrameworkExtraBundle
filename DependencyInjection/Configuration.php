<?php

namespace WeavingTheWeb\Bundle\FrameworkExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Configuration implements ConfigurationInterface
{
    private $user;
    
    private $password;
    
    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('weaving_the_web_framework_extra');

        $rootNode
            ->children()
                ->arrayNode('test_user')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('user')
                            ->defaultValue($this->user)
                        ->end()
                        ->scalarNode('password')
                            ->defaultValue($this->password)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
