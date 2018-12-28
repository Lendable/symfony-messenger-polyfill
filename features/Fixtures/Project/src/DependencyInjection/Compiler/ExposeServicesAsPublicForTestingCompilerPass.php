<?php

declare(strict_types=1);

namespace Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ExposeServicesAsPublicForTestingCompilerPass implements CompilerPassInterface
{
    private const PUBLIC_SERVICE_IDS = [
        'messenger.receiver_locator',
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::PUBLIC_SERVICE_IDS as $serviceId) {
            $container->getDefinition($serviceId)->setPublic(true);
        }
    }
}
