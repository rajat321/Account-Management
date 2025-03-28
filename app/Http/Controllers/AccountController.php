<?php 

namespace App\Http\Controllers;

use App\Models\Account;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Info(title="My API", version="1.0")
 * @OA\Server(url="http://127.0.0.1:8000")
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 */
class AccountController extends Controller
{
    use AuthorizesRequests;

    /**
     * @OA\Post(
     *     path="/api/accounts",
     *     summary="Create a new account",
     *     tags={"Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account_name", "account_type", "currency"},
     *             @OA\Property(property="account_name", type="string", example="My Savings Account"),
     *             @OA\Property(property="account_type", type="string", enum={"Personal", "Business"}, example="Personal"),
     *             @OA\Property(property="currency", type="string", enum={"USD", "EUR", "GBP", "INR"}, example="USD"),
     *             @OA\Property(property="balance", type="number", example=100.50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Account created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="account_name", type="string", example="My Savings Account"),
     *                 @OA\Property(property="account_type", type="string", example="Personal"),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="balance", type="number", example=100.50),
     *                 @OA\Property(property="created_at", type="string", example="2025-03-27 12:00:00"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-03-27 12:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="account_name", type="array",
     *                     @OA\Items(type="string", example="The account name field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred"),
     *             @OA\Property(property="error", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
       
        try {
            // Validate the incoming request
            $request->validate([
                'account_name' => 'required|string|unique:accounts,account_name',
                'account_type' => 'required|in:Personal,Business',
                'currency' => 'required|in:USD,EUR,GBP,INR',
                'balance' => 'nullable|numeric|min:0',
            ]);

            // Create the account record
            $account = Account::create([
                'user_id' => Auth::id(),
                'account_name' => $request->account_name,
                'account_type' => $request->account_type,
                'currency' => $request->currency,
                'balance' => $request->balance ?? 0,
            ]);

            // Return a success response with the account data
            return response()->json([
                'message' => 'Account created successfully',
                'data' => $account
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exceptions
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);  // HTTP status code 422 is for unprocessable entity (validation errors)
            
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);  // HTTP status code 500 is for server errors
        }
       

    }

   /**
 * @OA\Get(
 *     path="/api/accounts/{account_number}",
 *     summary="Retrieve account details",
 *     description="Fetch account details by account number (Requires Bearer Token)",
 *     tags={"Accounts"},
 *     security={{"bearerAuth":{}}},  
 *     @OA\Parameter(
 *         name="account_number",
 *         in="path",
 *         required=true,
 *         description="The account number of the account to retrieve",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Account details retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Account details retrieved successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="account_name", type="string", example="John Doe"),
 *                 @OA\Property(property="account_number", type="string", example="123456789"),
 *                 @OA\Property(property="account_type", type="string", example="Savings"),
 *                 @OA\Property(property="currency", type="string", example="USD"),
 *                 @OA\Property(property="balance", type="number", format="float", example=1000.50),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-27T12:34:56Z")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthorized")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden: Access denied",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Forbidden: Access denied")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Account not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Account not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="An error occurred",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred"),
 *             @OA\Property(property="error", type="string", example="Internal Server Error")
 *         )
 *     )
 * )
 */
    public function show($account_number)
    {
       
        try {
           
            // Retrieve account details
            $account = Account::where('account_number', $account_number)
                ->select('user_id','account_name', 'account_number', 'account_type', 'currency', 'balance', 'created_at')
                ->firstOrFail();
                
            // Ensure the authenticated user owns the account
            if ($account->user_id !== Auth::id()) {
                return response()->json(['message' => 'Forbidden: Access denied You Don`t access another account'], 403);
            }
    
            return response()->json([
                'message' => 'Account details get successfully',
                'data' => $account
            ], 200); // 200 OK for successful retrieval
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
           
            return response()->json([
                'message' => 'Account not found'
            ], 404); 
    
        } catch (\Exception $e) {
           
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500); // 500 for unexpected errors
        }
    }


    /**
     * @OA\Put(
     *     path="/api/accounts/{account_number}",
     *     summary="Update an existing account",
     *     tags={"Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="account_number",
     *         in="path",
     *         required=true,
     *         description="Account number to update",
     *         @OA\Schema(type="string", example="ACC123456")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account_name", "account_type", "currency"},
     *             @OA\Property(property="account_name", type="string", example="Updated Account Name"),
     *             @OA\Property(property="account_type", type="string", enum={"Personal", "Business"}, example="Business"),
     *             @OA\Property(property="currency", type="string", enum={"USD", "EUR", "GBP", "INR"}, example="EUR")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=123),
     *                 @OA\Property(property="account_name", type="string", example="Updated Account Name"),
     *                 @OA\Property(property="account_type", type="string", example="Business"),
     *                 @OA\Property(property="currency", type="string", example="EUR"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-03-27 12:30:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="account_name", type="array",
     *                     @OA\Items(type="string", example="The account name field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred"),
     *             @OA\Property(property="error", type="string", example="Unexpected error")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $account_number)
    {
       
        try {
            // Validate incoming request data
            $request->validate([
                'account_name' => 'required|string|unique:accounts,account_name,' . $account_number . ',account_number',
                'account_type' => 'required|in:Personal,Business',
                'currency' => 'required|in:USD,EUR,GBP,INR',
            ]);

            // Retrieve the account by account number
            $account = Account::where('account_number', $account_number)->firstOrFail();


            // Ensure the authenticated user owns the account
            if ($account->user_id !== Auth::id()) {
                return response()->json(['message' => 'Forbidden: Access denied You Don`t Update another account'], 403);
            }

            // Check if the authenticated user has permission to update this account
           // $this->authorize('update', $account);

            // Update the account with validated data
            $account->update($request->only(['account_name', 'account_type', 'currency']));

            // Return a successful response with the updated account data
            return response()->json([
                'message' => 'Account updated successfully',
                'data' => $account
            ], 200); // 200 OK for successful update

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422); // 422 for validation errors

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle the case when the account is not found
            return response()->json([
                'message' => 'Account not found'
            ], 404); // 404 Not Found if the account does not exist

        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500); // 500 for unexpected errors
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/accounts/{account_number}",
     *     summary="Delete (deactivate) an account",
     *     tags={"Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="account_number",
     *         in="path",
     *         required=true,
     *         description="Account number to delete",
     *         @OA\Schema(type="string", example="ACC123456")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account deactivated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account deactivated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred"),
     *             @OA\Property(property="error", type="string", example="Unexpected error")
     *         )
     *     )
     * )
     */
    public function destroy($account_number)
    {
        try {
            $account = Account::where('account_number', $account_number)->firstOrFail();


            // Ensure the authenticated user owns the account
            if ($account->user_id !== Auth::id()) {
                return response()->json(['message' => 'Forbidden: Access denied You Don`t Delete another account'], 403);
            }

            //$this->authorize('delete', $account);
            $account->delete();
            return response()->json([
                'message' => 'Account deactivated successfully'
            ], 200); 
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Account not found'
            ], 404); 
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
