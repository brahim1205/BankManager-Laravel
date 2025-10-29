<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
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
            'type' => 'required|in:depot,retrait,transfert,virement',
            'montant' => 'required|numeric|min:100|max:10000000',
            'devise' => 'string|size:3|default:XOF',
            'description' => 'nullable|string|max:500',
            'compte_source_id' => 'required_if:type,transfert,virement|uuid|exists:comptes,id',
            'compte_destination_id' => 'required_if:type,transfert,virement|uuid|exists:comptes,id|different:compte_source_id',
            'date_transaction' => 'date|before_or_equal:today|after:2020-01-01',
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
            'type.required' => 'Le type de transaction est obligatoire.',
            'type.in' => 'Le type doit être : depot, retrait, transfert ou virement.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 100 FCFA.',
            'montant.max' => 'Le montant maximum est de 10 000 000 FCFA.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'description.max' => 'La description ne doit pas dépasser 500 caractères.',
            'compte_source_id.required_if' => 'Le compte source est obligatoire pour les transferts et virements.',
            'compte_source_id.uuid' => 'Le compte source doit être un UUID valide.',
            'compte_source_id.exists' => 'Le compte source n\'existe pas.',
            'compte_destination_id.required_if' => 'Le compte destination est obligatoire pour les transferts et virements.',
            'compte_destination_id.uuid' => 'Le compte destination doit être un UUID valide.',
            'compte_destination_id.exists' => 'Le compte destination n\'existe pas.',
            'compte_destination_id.different' => 'Le compte destination doit être différent du compte source.',
            'date_transaction.date' => 'La date de transaction doit être une date valide.',
            'date_transaction.before_or_equal' => 'La date de transaction ne peut pas être dans le futur.',
            'date_transaction.after' => 'La date de transaction doit être postérieure à 2020.',
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
            'type' => 'type de transaction',
            'montant' => 'montant',
            'devise' => 'devise',
            'description' => 'description',
            'compte_source_id' => 'compte source',
            'compte_destination_id' => 'compte destination',
            'date_transaction' => 'date de transaction',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Pour les dépôts et retraits, on ne nécessite pas de comptes source/destination
        if (in_array($this->input('type'), ['depot', 'retrait'])) {
            $this->request->remove('compte_source_id');
            $this->request->remove('compte_destination_id');
        }
    }
}
