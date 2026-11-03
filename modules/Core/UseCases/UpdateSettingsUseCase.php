<?php

namespace Modules\Core\UseCases;

use Modules\Core\Contracts\Repositories\SettingsRepositoryInterface;
use Modules\Core\DTOs\Inputs\UpdateSettingsInput;
use Modules\Core\DTOs\Outputs\SettingsOutput;
use Modules\Core\Exceptions\DefaultCurrencyNotAvailableException;

final class UpdateSettingsUseCase
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    public function execute(UpdateSettingsInput $input): SettingsOutput
    {
        if (! $this->settings->isActiveCurrency($input->defaultCurrency)) {
            throw new DefaultCurrencyNotAvailableException;
        }

        $this->settings->save([
            'store.contact_email' => $input->contactEmail,
            'store.phone' => $input->phone,
            'store.address' => $input->address,
            'store.default_currency' => $input->defaultCurrency,
            'social.instagram' => $input->instagram,
            'social.facebook' => $input->facebook,
            'social.tiktok' => $input->tiktok,
            'social.pinterest' => $input->pinterest,
            'shipping.standard_shipping_amount' => $input->standardShippingAmount,
            'shipping.free_shipping_threshold' => $input->freeShippingThreshold,
            'shipping.express_shipping_amount' => $input->expressShippingAmount,
            'shipping.delivery_time_days' => $input->deliveryTimeDays,
            'shipping.express_delivery_time_days' => $input->expressDeliveryTimeDays,
        ]);

        return SettingsOutput::fromValues($this->settings->values(), $this->settings->activeCurrencies());
    }
}
