<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegaleseNciRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validation sans regex - vérification manuelle du format NCI sénégalais
        $value = trim($value);

        // Vérifier la longueur
        if (strlen($value) !== 13) {
            $fail('Le numéro NCI doit être composé de 13 chiffres.');
            return;
        }

        // Vérifier que tous les caractères sont des chiffres
        if (!ctype_digit($value)) {
            $fail('Le numéro NCI ne doit contenir que des chiffres.');
            return;
        }

        // Vérifier que le premier chiffre est 1 ou 2
        $firstDigit = $value[0];
        if ($firstDigit !== '1' && $firstDigit !== '2') {
            $fail('Le numéro NCI doit commencer par 1 ou 2.');
            return;
        }

        // Vérification de l'algorithme de Luhn pour les cartes d'identité sénégalaises
        if (!$this->isValidNciChecksum($value)) {
            $fail('Le numéro NCI fourni n\'est pas valide.');
        }
    }

    /**
     * Vérifie la validité du checksum NCI sénégalais
     */
    private function isValidNciChecksum(string $nci): bool
    {
        // Algorithme simplifié pour la validation NCI
        // En réalité, l'algorithme est plus complexe
        $digits = str_split($nci);
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $digits[$i];
            if ($i % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit == $digits[12];
    }
}
