<?php

namespace Modules\Core\Repositories;

use Modules\Core\Contracts\Repositories\SettingsRepositoryInterface;
use Modules\Core\Models\Currency;
use Modules\Core\Models\Setting;

final class SettingsRepository implements SettingsRepositoryInterface
{
    public function values(): array
    {
        return Setting::query()->get()->mapWithKeys(fn (Setting $setting): array => [$setting->group.'.'.$setting->key => json_decode((string) $setting->getRawOriginal('value'), true)])->all();
    }

    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            [$group, $name] = explode('.', $key, 2);
            Setting::query()->updateOrInsert(['group' => $group, 'key' => $name], ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()]);
        }
    }

    public function activeCurrencies(): array
    {
        return Currency::query()->where('is_active', true)->orderBy('code')->get()->map(fn (Currency $currency): array => ['code' => $currency->code, 'name' => $currency->name, 'symbol' => $currency->symbol, 'decimalPlaces' => $currency->decimal_places])->all();
    }

    public function isActiveCurrency(string $currencyCode): bool
    {
        return Currency::query()->whereKey($currencyCode)->where('is_active', true)->exists();
    }
}
