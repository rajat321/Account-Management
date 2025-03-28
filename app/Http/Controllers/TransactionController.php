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

    // Transaction Details Store Base on Account
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

    // Transaction Details Get Base On Account
    public function index(Request $request)
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
