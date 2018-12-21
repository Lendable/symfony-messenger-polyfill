<?php

declare(strict_types=1);

namespace Tests\Lendable\Polyfill\Symfony\MessengerBundle\DependencyInjection;

use Lendable\Polyfill\Symfony\MessengerBundle\DependencyInjection\MessengerExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

final class MessengerExtensionTest extends AbstractExtensionTestCase
{
    public function testInContainer(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService('messenger.middleware.handle_message');
    }

    protected function getContainerExtensions()
    {
        return [new MessengerExtension()];
    }
}
