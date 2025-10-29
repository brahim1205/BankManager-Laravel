<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloquerCompteRequest extends FormRequest
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
            'motif' => 'required|string|max:500',
            'date_debut' => 'required|date|after_or_equal:today',
            'date_fin' => 'nullable|date|after:date_debut',
            'duree_jours' => 'nullable|integer|min:1|max:365',
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
            'motif.required' => 'Le motif de blocage est obligatoire.',
            'motif.string' => 'Le motif doit être une chaîne de caractères.',
            'motif.max' => 'Le motif ne doit pas dépasser 500 caractères.',
            'date_debut.required' => 'La date de début de blocage est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_debut.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
            'duree_jours.integer' => 'La durée doit être un nombre entier.',
            'duree_jours.min' => 'La durée minimale est de 1 jour.',
            'duree_jours.max' => 'La durée maximale est de 365 jours.',
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
            'motif' => 'motif de blocage',
            'date_debut' => 'date de début',
            'date_fin' => 'date de fin',
            'duree_jours' => 'durée en jours',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Si une durée est fournie, calculer la date de fin
        if ($this->has('duree_jours') && $this->has('date_debut')) {
            $dateDebut = \Carbon\Carbon::parse($this->input('date_debut'));
            $dateFin = $dateDebut->copy()->addDays($this->input('duree_jours'));

            $this->merge([
                'date_fin' => $dateFin->toDateString(),
            ]);
        }
    }
}
