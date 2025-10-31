<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $compteId = $this->route('compte')?->id ?? $this->route('compte');

        return [
            'libelle' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:courant,epargne,entreprise,joint',
            'solde' => 'sometimes|numeric|min:0',
            'devise' => 'sometimes|string|size:3',
            'client_id' => 'sometimes|required|exists:clients,id',
            'date_ouverture' => 'sometimes|date|before_or_equal:today',
            'statut' => 'sometimes|in:actif,bloque,ferme',
            'description' => 'nullable|string|max:1000',
            'client' => 'sometimes|array',
            'client.nom' => 'sometimes|string|max:255',
            'client.prenom' => 'sometimes|string|max:255',
            'client.email' => 'sometimes|email|unique:clients,email,' . ($this->route('compte')?->client_id ?? ''),
            'client.telephone' => 'sometimes|string|regex:/^\+221\d{9}$/',
            'client.nci' => 'sometimes|string|regex:/^[12]\d{12}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé du compte est obligatoire.',
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être : courant, épargne, entreprise ou joint.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde ne peut pas être négatif.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
            'date_ouverture.date' => 'La date d\'ouverture doit être une date valide.',
            'date_ouverture.before_or_equal' => 'La date d\'ouverture ne peut pas être dans le futur.',
            'statut.in' => 'Le statut doit être : actif, bloqué ou fermé.',
            'description.max' => 'La description ne doit pas dépasser 1000 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'libelle' => 'libellé',
            'type' => 'type de compte',
            'solde' => 'solde',
            'devise' => 'devise',
            'client_id' => 'client',
            'date_ouverture' => 'date d\'ouverture',
            'statut' => 'statut',
            'description' => 'description',
        ];
    }
}
