<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Scopes\NonSupprimeScope;

class Compte extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new NonSupprimeScope);
    }

    protected $fillable = [
        'numero',
        'libelle',
        'type',
        'solde',
        'devise',
        'client_id',
        'date_ouverture',
        'statut',
        'description',
        'date_debut_blocage',
        'date_fin_blocage',
        'motif_blocage',
        'archive',
        'date_archivage',
    ];

    protected $casts = [
        'id' => 'string',
        'solde' => 'decimal:2',
        'date_ouverture' => 'date',
        'client_id' => 'string',
        'date_debut_blocage' => 'datetime',
        'date_fin_blocage' => 'datetime',
        'date_archivage' => 'datetime',
        'archive' => 'boolean',
        'deleted_at' => 'datetime',
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
                $prefix = match($model->type) {
                    'courant' => 'CC',
                    'epargne' => 'CE',
                    'entreprise' => 'CE',
                    'joint' => 'CJ',
                    default => 'C'
                };
                $model->numero = $prefix . '-' . strtoupper(Str::random(10));
            }

            if (empty($model->date_ouverture)) {
                $model->date_ouverture = now()->toDateString();
            }
        });
    }

    // Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactionsSource()
    {
        return $this->hasMany(Transaction::class, 'compte_source_id');
    }

    public function transactionsDestination()
    {
        return $this->hasMany(Transaction::class, 'compte_destination_id');
    }

    public function transactions()
    {
        return Transaction::where('compte_source_id', $this->id)
                         ->orWhere('compte_destination_id', $this->id);
    }

    // Accessors
    public function getSoldeFormateAttribute(): string
    {
        return number_format($this->solde, 2, ',', ' ') . ' ' . $this->devise;
    }

    public function getSoldeCalculeAttribute(): float
    {
        // Solde = Somme des dépôts - Somme des retraits
        $debits = $this->transactionsSource()->where('type', 'retrait')->sum('montant');
        $credits = $this->transactionsSource()->where('type', 'depot')->sum('montant');

        return $credits - $debits;
    }

    public function getTypeLibelleAttribute(): string
    {
        return match($this->type) {
            'courant' => 'Compte Courant',
            'epargne' => 'Compte Épargne',
            'entreprise' => 'Compte Entreprise',
            'joint' => 'Compte Joint',
            default => ucfirst($this->type)
        };
    }

    public function getStatutLibelleAttribute(): string
    {
        return match($this->statut) {
            'actif' => 'Actif',
            'bloque' => 'Bloqué',
            'ferme' => 'Fermé',
            default => ucfirst($this->statut)
        };
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeChequeOuEpargneActifs($query)
    {
        return $query->where('statut', 'actif')
                    ->whereIn('type', ['courant', 'epargne']);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeParClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeNonArchives($query)
    {
        return $query->where('archive', false);
    }

    public function scopeArchives($query)
    {
        return $query->where('archive', true);
    }

    public function scopeBloques($query)
    {
        return $query->where('statut', 'bloque');
    }

    public function scopeBlocageExpire($query)
    {
        return $query->where('date_fin_blocage', '<=', now());
    }

    public function scopeNumero($query, $numero)
    {
        return $query->where('numero', $numero);
    }

    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('client', function($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }

    // Méthodes métier
    public function peutDebiter(float $montant): bool
    {
        return $this->statut === 'actif' && $this->solde >= $montant;
    }

    public function debiter(float $montant): bool
    {
        if (!$this->peutDebiter($montant)) {
            return false;
        }

        $this->solde -= $montant;
        return $this->save();
    }

    public function crediter(float $montant): bool
    {
        if ($this->statut !== 'actif') {
            return false;
        }

        $this->solde += $montant;
        return $this->save();
    }
}
