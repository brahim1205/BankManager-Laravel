<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use App\Services\CompteService;
use App\Http\Resources\CompteResource;
use App\Http\Resources\CompteCollection;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\StoreCompteWithClientRequest;
use App\Http\Requests\BloquerCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Info(
 *     title="Banque API",
 *     version="1.0.0",
 *     description="API de gestion bancaire avec comptes et transactions"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1",
 *     description="Serveur de développement"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes bancaires"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    protected CompteService $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    /**
     * Créer un nouveau compte bancaire avec client
     *
     * Crée un compte bancaire et un client si nécessaire, envoie les notifications
     *
     * @OA\Post(
     *     path="/comptes",
     *     summary="Créer un compte bancaire",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "soldeInitial", "devise", "client"},
     *             @OA\Property(property="type", type="string", enum={"courant", "epargne", "entreprise", "joint"}, example="courant"),
     *             @OA\Property(property="soldeInitial", type="number", minimum=10000, example=50000),
     *             @OA\Property(property="devise", type="string", enum={"XOF", "USD", "EUR"}, example="XOF"),
     *             @OA\Property(property="client", type="object",
     *                 oneOf={
     *                     @OA\Schema(
     *                         required={"id"},
     *                         @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000")
     *                     ),
     *                     @OA\Schema(
     *                         required={"titulaire", "nci", "email", "telephone"},
     *                         @OA\Property(property="titulaire", type="string", example="Cheikh Sy"),
     *                         @OA\Property(property="nci", type="string", pattern="^[12]\\d{12}$", example="1234567890123"),
     *                         @OA\Property(property="email", type="string", format="email", example="cheikh.sy@email.com"),
     *                         @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                         @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *                     )
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numero", type="string"),
     *                 @OA\Property(property="libelle", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="solde", type="number"),
     *                 @OA\Property(property="devise", type="string"),
     *                 @OA\Property(property="statut", type="string"),
     *                 @OA\Property(property="client", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function store(StoreCompteWithClientRequest $request)
    {
        try {
            $compte = $this->compteService->createCompteWithClient($request->validated());

            return $this->successResponse(
                new CompteResource($compte->load('client')),
                'Compte créé avec succès',
                201
            );

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return $this->errorResponse(
                    'Une contrainte d\'unicité a été violée. Vérifiez que l\'email et le téléphone sont uniques.',
                    422
                );
            }

            return $this->errorResponse(
                'Erreur lors de la création du compte: ' . $e->getMessage(),
                500
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur inattendue lors de la création du compte: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Lister tous les comptes non archivés
     *
     * Récupère la liste paginée des comptes non supprimés avec possibilité de filtrage
     *
     * @OA\Get(
     *     path="/comptes",
     *     summary="Lister les comptes",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=10)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type",
     *         @OA\Schema(type="string", enum={"courant", "epargne", "entreprise", "joint"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="public",
     *         in="query",
     *         description="Mode public pour les tests (sans authentification)",
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="numero", type="string"),
     *                     @OA\Property(property="libelle", type="string"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="solde", type="number"),
     *                     @OA\Property(property="devise", type="string"),
     *                     @OA\Property(property="statut", type="string"),
     *                     @OA\Property(property="client", type="object",
     *                         @OA\Property(property="nom", type="string"),
     *                         @OA\Property(property="prenom", type="string")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Mode public pour les tests (sans authentification)
        $isPublicMode = $request->boolean('public', false);

        // En mode public, on contourne complètement les restrictions d'autorisation
        if ($isPublicMode) {
            $query = Compte::withoutGlobalScopes()->with('client');
        } else {
            // Mode authentifié : vérifier les permissions
            if (!$user) {
                return $this->errorResponse('Authentification requise', 401);
            }

            $query = Compte::with('client');

            // Filtrage par rôle utilisateur
            if (!$user->isAdmin()) {
                // Client ne voit que ses propres comptes
                if ($user->client_id) {
                    $query->parClient($user->client_id);
                } else {
                    // Si pas de client_id, retourner une collection vide
                    $query->whereRaw('1 = 0');
                }
            }
            // Admin voit tous les comptes (pas de filtrage supplémentaire)
        }

        // Filtres
        if ($request->has('type') && in_array($request->type, ['courant', 'epargne', 'entreprise', 'joint'])) {
            $query->parType($request->type);
        }

        if ($request->has('statut') && in_array($request->statut, ['actif', 'bloque'])) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('nom', 'like', "%{$search}%")
                                  ->orWhere('prenom', 'like', "%{$search}%");
                  });
            });
        }

        // Tri
        $sortField = $request->get('sort', 'date_ouverture');
        $sortOrder = $request->get('order', 'desc');

        $allowedSortFields = ['date_ouverture', 'solde', 'numero'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'date_ouverture';
        }

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $limit = min($request->get('limit', 10), 100);
        $comptes = $query->paginate($limit);

        return new CompteCollection($comptes);
    }

    /**
     * Récupérer un compte spécifique
     *
     * Admin peut récupérer un compte à partir de l'id
     * Client peut récupérer un de ses comptes par id
     *
     * Stratégie de recherche:
     * - Par défaut la recherche se fait sur la base locale lorsque le compte est chèque ou épargne actif
     * - La recherche est faite sur la base serverless lorsque le compte ne se trouve pas en local
     *
     * @OA\Get(
     *     path="/comptes/{compte}",
     *     summary="Récupérer un compte spécifique",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="UUID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numero", type="string", example="CC-ABC12345"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", enum={"courant", "epargne", "entreprise", "joint"}),
     *                 @OA\Property(property="solde", type="number", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                 @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}),
     *                 @OA\Property(property="motifBlocage", type="string", nullable=true),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=403, description="Accès non autorisé")
     * )
     */
    public function show(string $compteId)
    {
        try {
            // Recherche du compte avec chargement des relations
            $compte = \App\Models\Compte::withoutGlobalScopes()->with('client')->find($compteId);

            // Si pas trouvé localement, rechercher dans Neon (simulation)
            if (!$compte) {
                // Simulation de recherche dans Neon
                $compte = $this->searchInNeon($compteId);
                if (!$compte) {
                    throw new \App\Exceptions\CompteNotFoundException($compteId);
                }
            }

            // Vérifier les permissions d'accès
            $user = auth()->user();
            if (!$user->isAdmin() && $compte->client_id !== $user->client_id) {
                return $this->errorResponse('Vous n\'avez pas accès à ce compte', 403);
            }

            // Formater la réponse selon les spécifications
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $compte->id,
                    'numero' => $compte->numero,
                    'titulaire' => $compte->client->nom_complet,
                    'type' => $compte->type,
                    'solde' => $compte->solde,
                    'devise' => $compte->devise,
                    'dateCreation' => $compte->date_ouverture->toISOString(),
                    'statut' => $compte->statut,
                    'motifBlocage' => $compte->motif_blocage,
                    'metadata' => [
                        'derniereModification' => $compte->updated_at->toISOString(),
                        'version' => 1 // Pour le versioning des données
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new \App\Exceptions\CompteNotFoundException($compteId);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération du compte: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Simulation de recherche dans la base Neon
     */
    private function searchInNeon(string $compteId)
    {
        // Simulation - En production, ceci ferait une requête vers Neon
        // Pour l'instant, on retourne null pour indiquer que le compte n'existe pas
        \Illuminate\Support\Facades\Log::info("Recherche du compte {$compteId} dans Neon");

        // Simulation d'un compte trouvé dans Neon
        // return \App\Models\Compte::fromNeon($neonData);

        return null; // Pas trouvé
    }

    /**
     * Lister les comptes archivés (admin seulement)
     *
     * Récupère la liste des comptes bloqués/archivés
     */
    public function comptesArchives(Request $request)
    {
        $user = auth()->user();

        // Seuls les admins peuvent voir les comptes archivés
        if (!$user->isAdmin()) {
            return $this->errorResponse('Accès non autorisé', 403);
        }

        $query = Compte::with('client')->where('statut', 'bloque');

        // Pagination
        $limit = min($request->get('limit', 10), 100);
        $comptes = $query->paginate($limit);

        return new CompteCollection($comptes);
    }

    /**
     * Lister les comptes du client connecté
     */
    public function mesComptes(Request $request)
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->errorResponse('Les administrateurs utilisent l\'endpoint général /comptes', 400);
        }

        $query = Compte::with('client')->where('client_id', $user->client_id);

        // Filtres
        if ($request->has('type') && in_array($request->type, ['courant', 'epargne', 'entreprise', 'joint'])) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut') && in_array($request->statut, ['actif', 'bloque'])) {
            $query->where('statut', $request->statut);
        }

        // Pagination
        $limit = min($request->get('limit', 10), 100);
        $comptes = $query->paginate($limit);

        return new CompteCollection($comptes);
    }

    /**
     * Mettre à jour les informations d'un compte
     *
     * Met à jour partiellement les informations d'un compte
     * Tous les champs sont optionnels mais au moins un doit être fourni
     *
     * @OA\Patch(
     *     path="/comptes/{compte}",
     *     summary="Mettre à jour un compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="UUID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="libelle", type="string", example="Nouveau libellé"),
     *             @OA\Property(property="description", type="string", example="Nouvelle description"),
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="nom", type="string", example="Nouveau nom"),
     *                 @OA\Property(property="prenom", type="string", example="Nouveau prénom"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234568"),
     *                 @OA\Property(property="email", type="string", format="email", example="nouveau@email.com"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     * @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(UpdateCompteRequest $request, string $compteId)
    {
        try {
            $compte = Compte::findOrFail($compteId);

            // Vérifier les permissions
            $user = auth()->user();
            if (!$user->isAdmin() && $compte->client_id !== $user->client_id) {
                return $this->errorResponse('Vous n\'avez pas accès à ce compte', 403);
            }

            $compte = $this->compteService->updateCompte($compteId, $request->validated());

            return $this->successResponse(
                new CompteResource($compte),
                'Compte mis à jour avec succès'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new CompteNotFoundException($compteId);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la mise à jour du compte: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Supprimer un compte (soft delete)
     *
     * Effectue une suppression douce du compte
     *
     * @OA\Delete(
     *     path="/comptes/{compte}",
     *     summary="Supprimer un compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="UUID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numero", type="string"),
     *                 @OA\Property(property="statut", type="string", example="ferme"),
     *                 @OA\Property(property="dateFermeture", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function destroy(string $compteId)
    {
        try {
            $compte = Compte::findOrFail($compteId);

            // Vérifier les permissions
            $user = auth()->user();
            if (!$user->isAdmin()) {
                return $this->errorResponse('Seuls les administrateurs peuvent supprimer des comptes', 403);
            }

            $compte = $this->compteService->deleteCompte($compteId);

            return $this->successResponse([
                'id' => $compte->id,
                'numero' => $compte->numero,
                'statut' => 'ferme',
                'dateFermeture' => now()->toISOString(),
            ], 'Compte supprimé avec succès');

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new CompteNotFoundException($compteId);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la suppression du compte: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Bloquer un compte bancaire
     *
     * Bloque temporairement un compte avec motif et dates
     *
     * @OA\Post(
     *     path="/comptes/{compte}/bloquer",
     *     summary="Bloquer un compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="UUID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif", "date_debut"},
     *             @OA\Property(property="motif", type="string", maxLength=500, example="Suspicion de fraude"),
     *             @OA\Property(property="date_debut", type="string", format="date", example="2025-10-28"),
     *             @OA\Property(property="date_fin", type="string", format="date", example="2025-11-28"),
     *             @OA\Property(property="duree_jours", type="integer", minimum=1, maximum=365, example=30)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numero", type="string"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="date_debut_blocage", type="string", format="date"),
     *                 @OA\Property(property="date_fin_blocage", type="string", format="date"),
     *                 @OA\Property(property="motif_blocage", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function bloquer(BloquerCompteRequest $request, string $compteId)
    {
        try {
            // Vérifier les permissions - seuls les admins peuvent bloquer
            $user = auth()->user();
            if (!$user->isAdmin()) {
                return $this->errorResponse('Seuls les administrateurs peuvent bloquer des comptes', 403);
            }

            $compte = $this->compteService->bloquerCompte($compteId, $request->validated());

            return $this->successResponse(
                new CompteResource($compte->load('client')),
                'Compte bloqué avec succès'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors du blocage du compte: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get account statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $totalComptes = \App\Models\Compte::count();
            $comptesActifs = \App\Models\Compte::where('statut', 'actif')->count();
            $comptesBloques = \App\Models\Compte::where('statut', 'bloque')->count();
            $soldeTotal = \App\Models\Compte::sum('solde');

            $stats = [
                'total_comptes' => $totalComptes,
                'comptes_actifs' => $comptesActifs,
                'comptes_bloques' => $comptesBloques,
                'comptes_fermes' => $totalComptes - $comptesActifs - $comptesBloques,
                'solde_total' => $soldeTotal,
                'date_generation' => now()->toISOString()
            ];

            return $this->successResponse(
                $stats,
                'Statistiques des comptes récupérées avec succès',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des statistiques',
                500,
                $e->getMessage()
            );
        }
    }
}

/**
 * @OA\Schema(
 *     schema="Compte",
 *     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="numero", type="string", example="CC-ABC12345"),
 *     @OA\Property(property="libelle", type="string", example="Compte Courant"),
 *     @OA\Property(property="type", type="string", enum={"courant", "epargne", "entreprise", "joint"}, example="courant"),
 *     @OA\Property(property="solde", type="number", format="float", example=50000.00),
 *     @OA\Property(property="devise", type="string", example="XOF"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif"),
 *     @OA\Property(property="date_ouverture", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="client", ref="#/components/schemas/Client"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Client",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="numero", type="string", example="CLI-ABC12345"),
 *     @OA\Property(property="nom", type="string", example="Sy"),
 *     @OA\Property(property="prenom", type="string", example="Cheikh"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     @OA\Property(property="first", type="string", example="http://127.0.0.1:8000/api/v1/comptes?page=1"),
 *     @OA\Property(property="last", type="string", example="http://127.0.0.1:8000/api/v1/comptes?page=5"),
 *     @OA\Property(property="prev", type="string", nullable=true),
 *     @OA\Property(property="next", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="per_page", type="integer", example=10),
 *     @OA\Property(property="to", type="integer", example=10),
 *     @OA\Property(property="total", type="integer", example=50)
 * )
 */
