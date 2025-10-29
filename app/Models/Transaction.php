<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'numero',
        'type',
        'montant',
        'devise',
        'description',
        'compte_source_id',
        'compte_destination_id',
        'date_transaction',
        'statut',
        'archive',
        'date_archivage',
    ];

    protected $casts = [
        'id' => 'string',
        'montant' => 'decimal:2',
        'date_transaction' => 'datetime',
        'compte_source_id' => 'string',
        'compte_destination_id' => 'string',
        'archive' => 'boolean',
        'date_archivage' => 'datetime',
    ];

    // Désactiver l'auto-incrément
    public $incrementing = false;
    protected $keyType = 'string';

    // Générer automatiquement un UUID et numéro lors de la création
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            if (empty($model->numero)) {
                $model->numero = 'TRX-' . strtoupper(Str::random(10));
            }

            if (empty($model->date_transaction)) {
                $model->date_transaction = now();
            }
        });
    }

    // Relations
    public function compteSource()
    {
        return $this->belongsTo(Compte::class, 'compte_source_id');
    }

    public function compteDestination()
    {
        return $this->belongsTo(Compte::class, 'compte_destination_id');
    }

    // Accessors
    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant, 2, ',', ' ') . ' ' . $this->devise;
    }

    public function getTypeLibelleAttribute(): string
    {
        return match($this->type) {
            'depot' => 'Dépôt',
            'retrait' => 'Retrait',
            'transfert' => 'Transfert',
            'virement' => 'Virement',
            default => ucfirst($this->type)
        };
    }

    public function getStatutLibelleAttribute(): string
    {
        return match($this->statut) {
            'en_attente' => 'En attente',
            'validee' => 'Validée',
            'rejete' => 'Rejetée',
            default => ucfirst($this->statut)
        };
    }

    // Scopes
    public function scopeValidees($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeParCompte($query, $compteId)
    {
        return $query->where(function($q) use ($compteId) {
            $q->where('compte_source_id', $compteId)
              ->orWhere('compte_destination_id', $compteId);
        });
    }
}
