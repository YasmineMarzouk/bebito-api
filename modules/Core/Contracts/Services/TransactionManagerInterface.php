<?php

namespace Modules\Core\Contracts\Services;

interface TransactionManagerInterface
{
    public function run(callable $callback, int $attempts = 1): mixed;
}
