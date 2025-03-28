<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use DB;

class TransactionController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     summary="Create a new transaction",
     *     description="Store a credit or debit transaction for an authenticated user's account.",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account_number", "type", "amount"},
     *             @OA\Property(property="account_number", type="string", example="123456789012"),
     *             @OA\Property(property="type", type="string", enum={"Credit", "Debit"}, example="Credit"),
     *             @OA\Property(property="amount", type="number", format="float", example=100.50),
     *             @OA\Property(property="description", type="string", example="Salary Deposit")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=201,
     *         description="Transaction successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transaction successful"),
     *             @OA\Property(property="transaction", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="account_id", type="integer", example=101),
     *                 @OA\Property(property="type", type="string", example="Credit"),
     *                 @OA\Property(property="amount", type="number", format="float", example=100.50),
     *                 @OA\Property(property="description", type="string", example="Salary Deposit")
     *             ),
     *             @OA\Property(property="account", type="object",
     *                 @OA\Property(property="id", type="integer", example=101),
     *                 @OA\Property(property="balance", type="number", format="float", example=1500.75)
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized access to account"))
     *     ),
     * 
     *     @OA\Response(
     *         response=400,
     *         description="Insufficient balance for debit transaction",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Insufficient balance"))
     *     ),
     * 
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="An unexpected error occurred"))
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Authenticate user via Bearer Token
            $user = Auth::guard('api')->user();
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Validate the request
            $validatedData = $request->validate([
                'account_number' => 'required|exists:accounts,account_number',
                'type'           => 'required|in:Credit,Debit',
                'amount'         => 'required|numeric|min:0.01',
                'description'    => 'nullable|string|max:255',
            ]);

            // Retrieve the account
            $account = Account::where('account_number', $validatedData['account_number'])->first();

            // Ensure authenticated user owns the account
            if ($account->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access to account'
                ], 403);
            }

            // Check sufficient balance for debit transactions
            if ($validatedData['type'] === 'Debit' && $validatedData['amount'] > $account->balance) {
                return response()->json([
                    'message' => 'Insufficient balance'
                ], 400);
            }

            // Begin database transaction
            DB::beginTransaction();

            // Create transaction record
            $transaction = Transaction::create([
                'account_id'  => $account->id,
                'type'        => $validatedData['type'],
                'amount'      => $validatedData['amount'],
                'description' => $validatedData['description'],
            ]);

            // Update account balance
            if ($validatedData['type'] === 'Credit') {
                $account->increment('balance', $validatedData['amount']);
            } else {
                $account->decrement('balance', $validatedData['amount']);
            }

            DB::commit();

            return response()->json([
                'message'      => 'Transaction successful',
                'transaction'  => $transaction,
                'account'      => [
                    'id'       => $account->id,
                    'balance'  => $account->balance
                ]
            ], 201);

        } catch (ValidationException $e) {
            // Handle validation exceptions
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Rollback in case of failure
            DB::rollBack();

            return response()->json([
                'message' => 'An unexpected error occurred',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

/**
 * @OA\Get(
 *     path="/api/transactions",
 *     summary="Retrieve account transactions",
 *     description="Fetches transactions for a given account number with optional date range and pagination.",
 *     tags={"Transactions"},
 *     security={{"bearerAuth":{}}}, 
 *     @OA\Parameter(
 *         name="account_number",
 *         in="query",
 *         required=true,
 *         description="The account number to fetch transactions for",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="from",
 *         in="query",
 *         required=false,
 *         description="Start date for filtering transactions (YYYY-MM-DD)",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="to",
 *         in="query",
 *         required=false,
 *         description="End date for filtering transactions (YYYY-MM-DD)",
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         description="Number of transactions per page (default: all transactions)",
 *         @OA\Schema(type="integer", minimum=1, maximum=100)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Transactions retrieved successfully"),
 *             @OA\Property(
 *                 property="transactions",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="account_id", type="integer", example=10),
 *                     @OA\Property(property="type", type="string", example="Credit"),
 *                     @OA\Property(property="amount", type="number", format="float", example=100.50),
 *                     @OA\Property(property="description", type="string", example="Payment received"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-28T14:30:00Z")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Unauthorized access",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthorized access to account")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Validation error"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Unexpected error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="An unexpected error occurred"),
 *             @OA\Property(property="error", type="string")
 *         )
 *     )
 * )
 */ public function index(Request $request)
    {
        
        try {
            // Validate request parameters
            $validatedData = $request->validate([
                'account_number' => 'required|exists:accounts,account_number',
                'from'       => 'nullable|date',
                'to'         => 'nullable|date',
                'per_page'   => 'nullable|integer|min:1|max:100'
            ]);

            // Retrieve account
            $account = Account::where('account_number', $validatedData['account_number'])->first();

            // Ensure the authenticated user owns the account
            if ($account->user_id !== Auth::id()) {
                return response()->json([
                    'message' => 'Unauthorized access to account'
                ], 403);
            }

            // Retrieve transactions with optional date filters
            $transactions = Transaction::where('account_id', $account->id)
                ->when($validatedData['from'] ?? null, fn($query, $from) => $query->whereDate('created_at', '>=', $from))
                ->when($validatedData['to'] ?? null, fn($query, $to) => $query->whereDate('created_at', '<=', $to))
                ->orderBy('created_at', 'desc');

            // Apply pagination if requested
            $transactions = $validatedData['per_page'] ?? false
                ? $transactions->paginate($validatedData['per_page'])
                : $transactions->get();

            return response()->json([
                'message'      => 'Transactions retrieved successfully',
                'transactions' => $transactions
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    
}
