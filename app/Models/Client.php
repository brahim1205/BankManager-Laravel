<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;



class Client extends Model
{
    use HasFactory;
    use HasUuids;


    
     // Désactiver l’auto-incrément
    public $incrementing = false;

    // La clé primaire n’est pas un entier
    protected $keyType = 'string';

    protected $fillable = [
        'numero',
        'nom',
        'prenom',
        'nci',
        'email',
        'telephone',
        'adresse',
        'password',
        'code_verification',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'code_verification',
    ];

    protected $casts = [
        'id' => 'string',
        'email_verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Générer automatiquement un UUID et numéro lors de la création
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            if (empty($model->numero)) {
                $model->numero = 'CLI-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relations
    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }

    // Accessor pour le nom complet
    public function getNomCompletAttribute(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    // Accessor pour le nombre de comptes
    public function getNombreComptesAttribute(): int
    {
        return $this->comptes()->count();
    }

    // Accessor pour le solde total
    public function getSoldeTotalAttribute(): float
    {
        return $this->comptes()->sum('solde');
    }
}
