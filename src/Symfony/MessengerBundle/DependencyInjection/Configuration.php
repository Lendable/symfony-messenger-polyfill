<?php

declare(strict_types=1);

namespace Lendable\Polyfill\Symfony\MessengerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Bundle\FullStack;
use Symfony\Component\Serializer\Serializer;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('messenger_polyfill')
                ->fixXmlConfig('transport')
                ->fixXmlConfig('bus', 'buses');

        $rootNode->children()
            ->arrayNode('routing')
                ->useAttributeAsKey('message_class')
                ->beforeNormalization()
                    ->always()
                    ->then(function ($config) {
                        if (!\is_array($config)) {
                            return array();
                        }

                        $newConfig = array();
                        foreach ($config as $k => $v) {
                            if (!\is_int($k)) {
                                $newConfig[$k] = array(
                                    'senders' => $v['senders'] ?? (\is_array($v) ? array_values($v) : array($v)),
                                    'send_and_handle' => $v['send_and_handle'] ?? false,
                                );
                            } else {
                                $newConfig[$v['message-class']]['senders'] = array_map(
                                    function ($a) {
                                        return \is_string($a) ? $a : $a['service'];
                                    },
                                    array_values($v['sender'])
                                );
                                $newConfig[$v['message-class']]['send-and-handle'] = $v['send-and-handle'] ?? false;
                            }
                        }

                        return $newConfig;
                    })
                ->end()
                ->prototype('array')
                    ->children()
                        ->arrayNode('senders')
                            ->requiresAtLeastOneElement()
                            ->prototype('scalar')->end()
                        ->end()
                        ->booleanNode('send_and_handle')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('serializer')
                ->addDefaultsIfNotSet()
                ->beforeNormalization()
                    ->always()
                    ->then(function ($config) {
                        if (false === $config) {
                            return array('id' => null);
                        }

                        if (\is_string($config)) {
                            return array('id' => $config);
                        }

                        return $config;
                    })
                ->end()
                ->children()
                    ->scalarNode('id')->defaultValue(!class_exists(FullStack::class) && class_exists(Serializer::class) ? 'messenger.transport.symfony_serializer' : null)->end()
                    ->scalarNode('format')->defaultValue('json')->end()
                    ->arrayNode('context')
                        ->normalizeKeys(false)
                        ->useAttributeAsKey('name')
                        ->defaultValue(array())
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('transports')
                ->useAttributeAsKey('name')
                ->arrayPrototype()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $dsn) {
                            return array('dsn' => $dsn);
                        })
                    ->end()
                    ->fixXmlConfig('option')
                    ->children()
                        ->scalarNode('dsn')->end()
                        ->arrayNode('options')
                            ->normalizeKeys(false)
                            ->defaultValue(array())
                            ->prototype('variable')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('default_bus')->defaultNull()->end()
            ->arrayNode('buses')
                ->defaultValue(array('messenger.bus.default' => array('default_middleware' => true, 'middleware' => array())))
                ->useAttributeAsKey('name')
                ->arrayPrototype()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('default_middleware')
                            ->values(array(true, false, 'allow_no_handlers'))
                            ->defaultTrue()
                        ->end()
                        ->arrayNode('middleware')
                            ->beforeNormalization()
                                ->ifTrue(function ($v) { return \is_string($v) || (\is_array($v) && !\is_int(key($v))); })
                                ->then(function ($v) { return array($v); })
                            ->end()
                            ->defaultValue(array())
                            ->arrayPrototype()
                                ->beforeNormalization()
                                    ->always()
                                    ->then(function ($middleware): array {
                                        if (!\is_array($middleware)) {
                                            return array('id' => $middleware);
                                        }
                                        if (isset($middleware['id'])) {
                                            return $middleware;
                                        }
                                        if (1 < \count($middleware)) {
                                            throw new \InvalidArgumentException(sprintf('Invalid middleware at path "framework.messenger": a map with a single factory id as key and its arguments as value was expected, %s given.', json_encode($middleware)));
                                        }

                                        return array(
                                            'id' => key($middleware),
                                            'arguments' => current($middleware),
                                        );
                                    })
                                ->end()
                                ->fixXmlConfig('argument')
                                ->children()
                                    ->scalarNode('id')->isRequired()->cannotBeEmpty()->end()
                                    ->arrayNode('arguments')
                                        ->normalizeKeys(false)
                                        ->defaultValue(array())
                                        ->prototype('variable')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end()
        ;
    }
}
