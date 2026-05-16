<?php

namespace Domain\PasswordPolicy\Interfaces;

/**
 * Contract for PasswordPolicy settings data access.
 *
 * Settings are stored as key-value pairs in the password_settings table.
 */
interface PasswordPolicyRepositoryInterface
{
    /**
     * Retrieve all password settings as an associative array.
     *
     * @return array<string, string>  Map of setting key to value.
     */
    public function getAll(): array;

    /**
     * Get the value of a single setting by key.
     *
     * @param  string  $key      
     * @param  mixed   $default  
     * @return mixed            
     */
    public function getValue(string $key, mixed $default = null): mixed;

    /**
     * Create or update a setting by key (upsert).
     *
     * @param  string  $key  
     * @param  string  $value  
     * @return void
     */
    public function setValue(string $key, string $value): void;
}
