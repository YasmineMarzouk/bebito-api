<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\Services\TransactionManagerInterface;

final class DatabaseTransactionManager implements TransactionManagerInterface
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
