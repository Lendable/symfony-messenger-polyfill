<?php

declare(strict_types=1);

namespace Lendable\Polyfill\Symfony\MessengerBundle;

use Lendable\Polyfill\Symfony\MessengerBundle\DependencyInjection\MessengerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Messenger\DependencyInjection\MessengerPass;

final class MessengerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MessengerPass());
    }

    public function getContainerExtension(): MessengerExtension
    {
        return new MessengerExtension();
    }
}
