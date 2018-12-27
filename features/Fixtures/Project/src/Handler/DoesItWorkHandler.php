<?php

namespace Tests\Lendable\Polyfill\Features\Fixtures\Project\Handler;

use Tests\Lendable\Polyfill\Features\Fixtures\Project\Query\DoesItWork;

class DoesItWorkHandler
{
    public function __invoke(DoesItWork $query)
    {
        return 'works';
    }
}
