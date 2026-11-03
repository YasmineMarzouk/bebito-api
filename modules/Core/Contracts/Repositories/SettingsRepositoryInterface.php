<?php

namespace Modules\Core\Contracts\Repositories;

interface SettingsRepositoryInterface
{
    /** @return array<string, mixed> */
    public function values(): array;

    /** @param array<string, mixed> $values */
    public function save(array $values): void;

    /** @return array<int, array{code: string, name: string, symbol: string, decimalPlaces: int}> */
    public function activeCurrencies(): array;

    public function isActiveCurrency(string $currencyCode): bool;
}
