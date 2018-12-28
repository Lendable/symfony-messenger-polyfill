<?php

declare(strict_types=1);

namespace Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project;

use Lendable\Polyfill\Symfony\MessengerBundle\MessengerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\DependencyInjection\Compiler\ExposeServicesAsPublicForTestingCompilerPass;
use Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\Handler\DoesItWorkHandler;
use Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\Query\AMQPDoesItWork;
use Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\Handler\AMQPDoesItWorkHandler;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return __DIR__.'/..';
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/test';
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/logs';
    }

    public function registerBundles(): \Generator
    {
        yield new FrameworkBundle();
        yield new MessengerBundle();
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->setParameter('kernel.secret', 'secret');

        $container->setDefinition(
            DoesItWorkHandler::class,
            (new Definition(DoesItWorkHandler::class))->addTag('messenger.message_handler')
        );

        $container->prependExtensionConfig('lendable_polyfill_messanger', [
            'transports' => [
                'amqp' => 'amqp://guest:guest@localhost:5672/%2f/messages',
            ],
            'routing' => [
                AMQPDoesItWork::class => 'amqp',
            ],
        ]);

        $container->addCompilerPass(new ExposeServicesAsPublicForTestingCompilerPass());
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
    }
}
