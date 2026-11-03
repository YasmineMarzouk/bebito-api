<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Mockery;
use Modules\Auth\Contracts\Repositories\AuthUserRepositoryInterface;
use Modules\Auth\DTOs\Inputs\LoginInput;
use Modules\Auth\Exceptions\AccountBlockedException;
use Modules\Auth\Exceptions\AdministratorAccessRequiredException;
use Modules\Auth\Exceptions\InvalidCredentialsException;
use Modules\Auth\UseCases\AuthenticateUserUseCase;
use Tests\TestCase;

class AuthenticateUserUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_verifies_the_hash_and_returns_an_administrator(): void
    {
        $user = new User;
        $user->setRawAttributes(['password' => 'stored-hash']);

        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')->once()->with('admin@example.test')->andReturn($user);
        $users->shouldReceive('hasAdministratorRole')->once()->with($user)->andReturnTrue();

        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('check')->once()->with('correct-password', 'stored-hash')->andReturnTrue();

        $useCase = new AuthenticateUserUseCase($users, $hasher);

        self::assertSame($user, $useCase->execute(new LoginInput('admin@example.test', 'correct-password', false)));
    }

    public function test_it_rejects_invalid_credentials_without_checking_roles(): void
    {
        $user = new User;
        $user->setRawAttributes(['password' => 'stored-hash']);

        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')->once()->with('admin@example.test')->andReturn($user);
        $users->shouldNotReceive('hasAdministratorRole');

        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('check')->once()->with('wrong-password', 'stored-hash')->andReturnFalse();

        $this->expectException(InvalidCredentialsException::class);

        (new AuthenticateUserUseCase($users, $hasher))->execute(
            new LoginInput('admin@example.test', 'wrong-password', false),
        );
    }

    public function test_it_rejects_non_administrators_after_verifying_their_password(): void
    {
        $user = new User;
        $user->setRawAttributes(['password' => 'stored-hash']);

        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')->once()->andReturn($user);
        $users->shouldReceive('hasAdministratorRole')->once()->with($user)->andReturnFalse();

        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('check')->once()->with('correct-password', 'stored-hash')->andReturnTrue();

        $this->expectException(AdministratorAccessRequiredException::class);

        (new AuthenticateUserUseCase($users, $hasher))->execute(
            new LoginInput('customer@example.test', 'correct-password', false),
        );
    }

    public function test_it_rejects_a_blocked_account(): void
    {
        $user = new User;
        $user->setRawAttributes(['password' => 'stored-hash', 'is_blocked' => true]);

        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $users->shouldReceive('findByEmail')->once()->andReturn($user);
        $users->shouldNotReceive('hasAdministratorRole');

        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('check')->once()->with('correct-password', 'stored-hash')->andReturnTrue();

        $this->expectException(AccountBlockedException::class);

        (new AuthenticateUserUseCase($users, $hasher))->execute(
            new LoginInput('blocked@example.test', 'correct-password', false),
        );
    }
}
