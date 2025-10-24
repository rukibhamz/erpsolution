<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNigerianCity implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValidNigerianCity($value)) {
            $fail('The :attribute must be a valid Nigerian city.');
        }
    }

    /**
     * Validate Nigerian city
     */
    private function isValidNigerianCity(string $city): bool
    {
        $nigerianCities = [
            'Abuja', 'Lagos', 'Kano', 'Ibadan', 'Port Harcourt', 'Benin City',
            'Kaduna', 'Maiduguri', 'Zaria', 'Aba', 'Jos', 'Ilorin', 'Oyo',
            'Enugu', 'Abeokuta', 'Sokoto', 'Onitsha', 'Warri', 'Kaduna',
            'Calabar', 'Uyo', 'Akure', 'Osogbo', 'Bauchi', 'Katsina',
            'Minna', 'Makurdi', 'Lafia', 'Keffi', 'Lokoja', 'Asaba',
            'Awka', 'Owerri', 'Umuahia', 'Yenagoa', 'Ado-Ekiti', 'Akure',
            'Ibadan', 'Ilorin', 'Kano', 'Kaduna', 'Lagos', 'Port Harcourt'
        ];

        return in_array(ucwords(strtolower($city)), $nigerianCities);
    }
}
