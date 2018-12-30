<?php

declare(strict_types=1);

namespace Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\Query;

final class AMQPDoesItWork
{
    public $works;

    public function __construct(string $works = 'works')
    {
        $this->works = $works;
    }
}
