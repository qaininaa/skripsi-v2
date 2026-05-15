<?php

namespace Domain\PasswordPolicy\Repositories;

use Domain\PasswordPolicy\Interfaces\PasswordPolicyRepositoryInterface;
use Domain\PasswordPolicy\Models\PasswordSetting;

/**
 * Eloquent implementation of PasswordPolicyRepositoryInterface.
 *
 * Reads and writes password policy settings stored as key-value pairs
 * in the password_settings table.
 */
class PasswordPolicyRepository implements PasswordPolicyRepositoryInterface
{
    /**
     * Retrieve all password settings as an associative array.
     *
     * @return array<string, string>  Map of setting key to value.
     */
    public function getAll(): array
    {
        return PasswordSetting::query()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get the value of a single setting by key.
     *
     * Returns the default if the key does not exist in the database.
     *
     * @param  string  $key      The setting key (e.g. 'password_expiration_days').
     * @param  mixed   $default  Value to return if the key is not found.
     * @return mixed             The stored value, or the default.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = PasswordSetting::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    /**
     * Create or update a setting by key.
     *
     * Uses upsert semantics: inserts if the key does not exist, updates if it does.
     *
     * @param  string  $key    The setting key.
     * @param  string  $value  The new value to store.
     * @return void
     */
    public function setValue(string $key, string $value): void
    {
        PasswordSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
