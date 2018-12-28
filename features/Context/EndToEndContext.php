<?php

namespace Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Context;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelDictionary;
use PHPUnit\Framework\Assert;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\Query\AMQPDoesItWork;

class EndToEndContext implements Context
{
    use KernelDictionary;

    private $messageBus;

    /** @var Envelope|null */
    private $response;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @When I dispatch a query
     */
    public function iDispatchAQuery()
    {
        $this->response = $this->messageBus->dispatch(new AMQPDoesItWork());
    }

    /**
     * @Then I should get a response
     */
    public function iShouldGetAResponse()
    {
        $stamps = $this->response->all();
        Assert::assertArrayHasKey(HandledStamp::class, $stamps);
        Assert::assertNotEmpty($stamps[HandledStamp::class]);
        Assert::assertSame('works', $stamps[HandledStamp::class][0]->getResult());
    }
}
