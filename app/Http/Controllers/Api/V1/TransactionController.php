<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionCollection;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\StoreTransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions bancaires"
 * )
 */
class TransactionController extends Controller
{
    use ApiResponseTrait;

    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Lister les transactions avec filtrage et pagination
     *
     * Récupère la liste paginée des transactions avec possibilité de filtrage
     *
     * @OA\Get(
     *     path="/transactions",
     *     summary="Lister les transactions",
     *     tags={"Transactions"},
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
     *         @OA\Schema(type="string", enum={"depot", "retrait", "transfert", "virement"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         @OA\Schema(type="string", enum={"en_attente", "validee", "rejete"})
     *     ),
     *     @OA\Parameter(
     *         name="compte_id",
     *         in="query",
     *         description="Filtrer par compte (source ou destination)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="numero", type="string"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="montant", type="number"),
     *                     @OA\Property(property="devise", type="string"),
     *                     @OA\Property(property="statut", type="string"),
     *                     @OA\Property(property="date_transaction", type="string", format="date-time")
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
        $query = Transaction::with(['compteSource.client', 'compteDestination.client']);

        // Filtres
        if ($request->has('type') && in_array($request->type, ['depot', 'retrait', 'transfert', 'virement'])) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut') && in_array($request->statut, ['en_attente', 'validee', 'rejete'])) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('compte_id') && !empty($request->compte_id)) {
            $query->where(function($q) use ($request) {
                $q->where('compte_source_id', $request->compte_id)
                  ->orWhere('compte_destination_id', $request->compte_id);
            });
        }

        // Filtre par dates
        if ($request->has('date_debut') && !empty($request->date_debut)) {
            $query->whereDate('date_transaction', '>=', $request->date_debut);
        }

        if ($request->has('date_fin') && !empty($request->date_fin)) {
            $query->whereDate('date_transaction', '<=', $request->date_fin);
        }

        // Tri par défaut : date décroissante
        $query->orderBy('date_transaction', 'desc');

        // Pagination
        $limit = min($request->get('limit', 10), 100);
        $transactions = $query->paginate($limit);

        return new TransactionCollection($transactions);
    }

    /**
     * Créer une nouvelle transaction
     *
     * Crée une transaction bancaire avec validation métier
     *
     * @OA\Post(
     *     path="/transactions",
     *     summary="Créer une transaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "montant"},
     *             @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert", "virement"}, example="transfert"),
     *             @OA\Property(property="montant", type="number", minimum=100, example=50000),
     *             @OA\Property(property="devise", type="string", default="XOF", example="XOF"),
     *             @OA\Property(property="description", type="string", example="Paiement facture"),
     *             @OA\Property(property="compte_source_id", type="string", format="uuid"),
     *             @OA\Property(property="compte_destination_id", type="string", format="uuid"),
     *             @OA\Property(property="date_transaction", type="string", format="date", example="2025-10-28")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction créée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numero", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="montant", type="number"),
     *                 @OA\Property(property="statut", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=400, description="Erreur métier")
     * )
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->createTransaction($request->validated());

            return $this->successResponse(
                new TransactionResource($transaction->load(['compteSource.client', 'compteDestination.client'])),
                'Transaction créée avec succès',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la création de la transaction: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Récupérer une transaction spécifique
     *
     * @OA\Get(
     *     path="/transactions/{transaction}",
     *     summary="Récupérer une transaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="transaction",
     *         in="path",
     *         required=true,
     *         description="UUID de la transaction",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numero", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="montant", type="number"),
     *                 @OA\Property(property="statut", type="string"),
     *                 @OA\Property(property="compte_source", type="object"),
     *                 @OA\Property(property="compte_destination", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée")
     * )
     */
    public function show(Transaction $transaction): JsonResponse
    {
        return $this->successResponse(
            new TransactionResource($transaction->load(['compteSource.client', 'compteDestination.client']))
        );
    }

}
