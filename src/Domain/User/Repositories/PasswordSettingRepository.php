<?php

namespace Domain\User\Repositories;

use Domain\User\Interfaces\PasswordSettingRepositoryInterface;
use Domain\User\Models\PasswordSetting;

class PasswordSettingRepository implements PasswordSettingRepositoryInterface
{
    public function getAll(): array
    {
        return PasswordSetting::query()
            ->pluck('value', 'key')
            ->toArray();
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = PasswordSetting::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public function setValue(string $key, string $value): void
    {
        PasswordSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
