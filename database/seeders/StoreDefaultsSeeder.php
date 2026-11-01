<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreDefaultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('locales')->upsert([
            ['code' => 'fr', 'name' => 'French', 'is_active' => true, 'is_default' => true],
            ['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => false],
        ], ['code'], ['name', 'is_active', 'is_default']);

        DB::table('currencies')->upsert([
            ['code' => 'TND', 'name' => 'Tunisian dinar', 'symbol' => 'د.ت', 'decimal_places' => 3, 'is_active' => true],
            ['code' => 'USD', 'name' => 'US dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => false],
        ], ['code'], ['name', 'symbol', 'decimal_places', 'is_active']);

        DB::table('countries')->upsert([['code' => 'TN', 'name' => 'Tunisia', 'is_active' => true]], ['code'], ['name', 'is_active']);
        DB::table('roles')->upsert(collect(['owner', 'admin', 'editor', 'support'])->map(fn (string $name) => ['name' => $name])->all(), ['name']);

        DB::table('settings')->upsert([
            ['group' => 'store', 'key' => 'default_currency', 'value' => json_encode('TND')],
            ['group' => 'store', 'key' => 'supported_locales', 'value' => json_encode(['fr', 'en'])],
            ['group' => 'payment', 'key' => 'cash_on_delivery_enabled', 'value' => json_encode(true)],
            ['group' => 'payment', 'key' => 'online_payment_enabled', 'value' => json_encode(false)],
            ['group' => 'shipping', 'key' => 'enabled_countries', 'value' => json_encode(['TN'])],
        ], ['group', 'key'], ['value']);
    }
}
