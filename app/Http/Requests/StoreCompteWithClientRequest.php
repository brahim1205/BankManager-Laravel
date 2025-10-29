<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompteWithClientRequest extends FormRequest
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
        return [
            'type' => 'required|in:courant,epargne,entreprise,joint',
            'soldeInitial' => 'required|numeric|min:10000',
            'devise' => 'required|string|size:3|in:XOF,USD,EUR',
            'client.id' => 'nullable|exists:clients,id',
            'client.nom' => 'required_without:client.id|string|max:255',
            'client.prenom' => 'required_without:client.id|string|max:255',
            'client.nci' => [
                'nullable',
                'required_without:client.id',
                new \App\Rules\SenegaleseNciRule()
            ],
            'client.email' => 'required_without:client.id|email|unique:clients,email',
            'client.telephone' => [
                'required_without:client.id',
                'unique:clients,telephone',
                new \App\Rules\SenegalesePhoneRule()
            ],
            'client.adresse' => 'nullable|string|max:500',
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
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être : courant, épargne, entreprise ou joint.',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être d\'au moins 10 000 FCFA.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'devise.in' => 'La devise doit être XOF, USD ou EUR.',
            'client.id.exists' => 'Le client sélectionné n\'existe pas.',
            'client.nom.required_without' => 'Le nom est obligatoire pour un nouveau client.',
            'client.nom.string' => 'Le nom doit être une chaîne de caractères.',
            'client.nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'client.prenom.required_without' => 'Le prénom est obligatoire pour un nouveau client.',
            'client.prenom.string' => 'Le prénom doit être une chaîne de caractères.',
            'client.prenom.max' => 'Le prénom ne doit pas dépasser 255 caractères.',
            'client.nci.required_without' => 'Le numéro NCI est obligatoire pour un nouveau client.',
            'client.email.required_without' => 'L\'email est obligatoire pour un nouveau client.',
            'client.email.email' => 'L\'email doit être valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required_without' => 'Le numéro de téléphone est obligatoire pour un nouveau client.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'client.adresse.max' => 'L\'adresse ne doit pas dépasser 500 caractères.',
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
            'type' => 'type de compte',
            'soldeInitial' => 'solde initial',
            'devise' => 'devise',
            'client.id' => 'ID client',
            'client.nom' => 'nom',
            'client.prenom' => 'prénom',
            'client.nci' => 'numéro NCI',
            'client.email' => 'email',
            'client.telephone' => 'numéro de téléphone',
            'client.adresse' => 'adresse',
        ];
    }

}
