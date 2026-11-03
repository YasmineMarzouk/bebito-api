<?php

namespace Modules\Core\UseCases;

use Modules\Core\Contracts\Repositories\SettingsRepositoryInterface;
use Modules\Core\DTOs\Outputs\SettingsOutput;

final class GetSettingsUseCase
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    public function execute(): SettingsOutput
    {
        return SettingsOutput::fromValues($this->settings->values(), $this->settings->activeCurrencies());
    }
}
