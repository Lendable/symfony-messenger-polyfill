<?php

declare(strict_types=1);

namespace Lendable\Polyfill\Symfony\MessengerBundle\DependencyInjection;

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class MessengerExtension extends ConfigurableExtension
{
    protected function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));

        $loader->load('messenger.xml');

        if (\class_exists(Application::class)) {
            $loader->load('console.xml');
        }

        $container->registerForAutoconfiguration(MessageHandlerInterface::class)
            ->addTag('messenger.message_handler');
        $container->registerForAutoconfiguration(TransportFactoryInterface::class)
            ->addTag('messenger.transport_factory');

        $this->registerMessengerConfiguration($config, $container);
    }

    private function registerMessengerConfiguration(array $config, ContainerBuilder $container): void
    {
        if (empty($config['transports'])) {
            $container->removeDefinition('messenger.transport.symfony_serializer');
            $container->removeDefinition('messenger.transport.amqp.factory');
        } else {
            if ($config['serializer']['enabled'] && ('messenger.transport.symfony_serializer' === $config['serializer']['id'] || null === $config['serializer']['id'])) {
                $container->getDefinition('messenger.transport.symfony_serializer')
                    ->replaceArgument(1, $config['serializer']['format'])
                    ->replaceArgument(2, $config['serializer']['context']);
                $container->setAlias('messenger.transport.serializer', 'messenger.transport.symfony_serializer');
            } elseif (null !== $config['serializer']['id']) {
                $container->setAlias('messenger.transport.serializer', $config['serializer']['id']);
            } else {
                $container->removeDefinition('messenger.transport.amqp.factory');
            }
        }

        if (null === $config['default_bus'] && 1 === \count($config['buses'])) {
            $config['default_bus'] = \key($config['buses']);
        }

        $defaultMiddleware = [
            'before' => [['id' => 'logging']],
            'after' => [['id' => 'send_message'], ['id' => 'handle_message']],
        ];
        foreach ($config['buses'] as $busId => $bus) {
            $middleware = $bus['middleware'];

            if ($bus['default_middleware']) {
                if ('allow_no_handlers' === $bus['default_middleware']) {
                    $defaultMiddleware['after'][1]['arguments'] = [true];
                } else {
                    unset($defaultMiddleware['after'][1]['arguments']);
                }
                $middleware = \array_merge($defaultMiddleware['before'], $middleware, $defaultMiddleware['after']);
            }

            foreach ($middleware as $middlewareItem) {
                if (!($config['validation']['enabled'] ?? false) && \in_array($middlewareItem['id'], ['validation', 'messenger.middleware.validation'], true)) {
                    throw new \LogicException('The Validation middleware is only available when the Validator component is installed and enabled. Try running "composer require symfony/validator".');
                }
            }

            if ($container->getParameter('kernel.debug') && \class_exists(Stopwatch::class)) {
                \array_unshift($middleware, ['id' => 'traceable', 'arguments' => [$busId]]);
            }

            $container->setParameter($busId.'.middleware', $middleware);
            $container->register($busId, MessageBus::class)->addArgument([])->addTag('messenger.bus');

            if ($busId === $config['default_bus']) {
                $container->setAlias('message_bus', $busId)->setPublic(true);
                $container->setAlias(MessageBusInterface::class, $busId);
            } else {
                if (method_exists($container, 'registerAliasForArgument')) {
                    $container->registerAliasForArgument($busId, MessageBusInterface::class);
                } else {
                    $this->registerAliasForArgument($container, $busId, MessageBusInterface::class);
                }
            }
        }

        $senderAliases = [];
        foreach ($config['transports'] as $name => $transport) {
            if (0 === \strpos($transport['dsn'], 'amqp://') && !$container->hasDefinition('messenger.transport.amqp.factory')) {
                throw new \LogicException('The default AMQP transport is not available. Make sure you have installed and enabled the Serializer component. Try enabling it or running "composer require symfony/serializer-pack".');
            }

            $transportDefinition = (new Definition(TransportInterface::class))
                ->setFactory([new Reference('messenger.transport_factory'), 'createTransport'])
                ->setArguments([$transport['dsn'], $transport['options']])
                ->addTag('messenger.receiver', ['alias' => $name]);
            $container->setDefinition($transportId = 'messenger.transport.'.$name, $transportDefinition);
            $senderAliases[$name] = $transportId;
        }

        $messageToSendersMapping = [];
        $messagesToSendAndHandle = [];
        foreach ($config['routing'] as $message => $messageConfiguration) {
            if ('*' !== $message && !\class_exists($message) && !\interface_exists($message, false)) {
                throw new \LogicException(\sprintf('Invalid Messenger routing configuration: class or interface "%s" not found.', $message));
            }
            $senders = [];
            foreach ($messageConfiguration['senders'] as $sender) {
                $senders[$sender] = new Reference($senderAliases[$sender] ?? $sender);
            }

            $sendersId = 'messenger.senders.'.$message;
            $container->register($sendersId, RewindableGenerator::class)
                ->setFactory('current')
                ->addArgument([new IteratorArgument($senders)]);
            $messageToSendersMapping[$message] = new Reference($sendersId);

            $messagesToSendAndHandle[$message] = $messageConfiguration['send_and_handle'];
        }

        $container->getDefinition('messenger.senders_locator')
            ->replaceArgument(0, $messageToSendersMapping)
            ->replaceArgument(1, $messagesToSendAndHandle);
    }

    public function getAlias(): string
    {
        return 'lendable_polyfill_messenger';
    }
    
    private function registerAliasForArgument(ContainerBuilder $container, string $id, string $type, string $name = null)
    {
        $name = lcfirst(str_replace(' ', '', ucwords(preg_replace('/[^a-zA-Z0-9\x7f-\xff]++/', ' ', $name ?? $id))));

        if (!preg_match('/^[a-zA-Z_\x7f-\xff]/', $name)) {
            throw new InvalidArgumentException(sprintf('Invalid argument name "%s" for service "%s": the first character must be a letter.', $name, $id));
        }

        return $container->setAlias($type . ' $' . $name, $id);
    }
}
