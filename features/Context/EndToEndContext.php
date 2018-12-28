<?php

namespace Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Context;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelDictionary;
use PHPUnit\Framework\Assert;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\Query\AMQPDoesItWork;

class EndToEndContext implements Context
{
    use KernelDictionary;

    private $messageBus;

    private $receiverLocator;

    public function __construct(MessageBusInterface $messageBus, ContainerInterface $receiverLocator)
    {
        $this->messageBus = $messageBus;
        $this->receiverLocator = $receiverLocator;
    }

    /**
     * @When I dispatch a query
     */
    public function iDispatchAQuery()
    {
        $this->messageBus->dispatch(new AMQPDoesItWork());
    }

    /**
     * @Then I should get a response
     */
    public function iShouldGetAResponse()
    {
        $receiver = $this->receiverLocator->get('amqp');

        $receiver->receive(function (Envelope $envelope) use ($receiver)  {
            $message = $envelope->getMessage();
            Assert::assertInstanceOf(AMQPDoesItWork::class, $message);
            Assert::assertSame('works', $message->works);
            $receiver->stop();
        });
    }
}
