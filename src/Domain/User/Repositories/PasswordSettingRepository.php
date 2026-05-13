<?php

namespace Domain\User\Repositories;

use Domain\User\Interfaces\PasswordSettingRepositoryInterface;
use Domain\User\Models\PasswordSetting;

class PasswordSettingRepository  implements PasswordSettingRepositoryInterface
{
    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = PasswordSetting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public function setValue(string $key, string $value): void
    {
        PasswordSetting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}