<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Models\Role;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('INITIAL_ADMIN_NAME', 'Store Owner'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $owner = Role::query()->firstOrCreate(['name' => 'owner']);
        $user->roles()->syncWithoutDetaching([$owner->id]);
    }
}
