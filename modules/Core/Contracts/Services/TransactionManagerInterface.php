<?php

namespace Modules\Core\Contracts\Services;

interface TransactionManagerInterface
{
    public function run(callable $callback): mixed;
}
