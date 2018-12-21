<?php

declare(strict_types=1);

namespace Tests\Lendable\Polyfill\Symfony\MessengerBundle;

use Lendable\Polyfill\Symfony\MessengerBundle\MessengerBundle;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Messenger\DependencyInjection\MessengerPass;

final class MessengerBundleTest extends TestCase
{
    /**
     * @var MessengerBundle
     */
    private $bundle;

    public function testCompilerPasses(): void
    {
        $containerBuilder = $this->prophesize(ContainerBuilder::class);

        $containerBuilder->addCompilerPass(
            Argument::type(MessengerPass::class)
        )->shouldBeCalledTimes(1);

        $this->bundle->build($containerBuilder->reveal());
    }

    protected function setUp(): void
    {
        $this->bundle = new MessengerBundle();
    }
}
