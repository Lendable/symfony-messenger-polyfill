<?php

declare(strict_types=1);

namespace Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\Handler;

use Tests\Lendable\Polyfill\Symfony\MessengerBundle\Features\Fixtures\Project\Query\DoesItWork;

class DoesItWorkHandler
{
    public function __invoke(DoesItWork $query)
    {
        return 'works';
    }
}
