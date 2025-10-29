<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalesePhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validation sans regex - vérification manuelle des formats sénégalais
        $value = trim($value);

        // Formats acceptés pour les numéros sénégalais
        // +221771234567, 771234567, +221781234567, 781234567, etc.

        // Vérifier si c'est un numéro international avec +221
        if (str_starts_with($value, '+221')) {
            $number = substr($value, 4); // Enlever +221
            if (strlen($number) !== 9) {
                $fail('Le numéro de téléphone doit être un numéro sénégalais valide (ex: +221771234567 ou 771234567).');
                return;
            }
        } elseif (strlen($value) === 9) {
            $number = $value;
        } else {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide (ex: +221771234567 ou 771234567).');
            return;
        }

        // Vérifier que c'est bien un numéro sénégalais (commence par les bons indicatifs)
        $validPrefixes = ['77', '78', '76', '70', '75', '33'];

        $prefix = substr($number, 0, 2);
        if (!in_array($prefix, $validPrefixes)) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide (ex: +221771234567 ou 771234567).');
            return;
        }

        // Vérifier que le reste sont des chiffres
        $remaining = substr($number, 2);
        if (!ctype_digit($remaining)) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide (ex: +221771234567 ou 771234567).');
            return;
        }
    }
}
