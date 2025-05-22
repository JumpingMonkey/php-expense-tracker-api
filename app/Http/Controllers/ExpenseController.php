<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Create a new ExpenseController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Middleware will be applied in the routes
    }
    
    /**
     * Display a listing of the resource with optional filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Expense::where('user_id', Auth::id())
            ->with('category');

        // Filter by date range
        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'past_week':
                    $query->where('expense_date', '>=', Carbon::now()->subWeek());
                    break;
                case 'past_month':
                    $query->where('expense_date', '>=', Carbon::now()->subMonth());
                    break;
                case 'last_3_months':
                    $query->where('expense_date', '>=', Carbon::now()->subMonths(3));
                    break;
                case 'custom':
                    if ($request->has('start_date') && $request->has('end_date')) {
                        $query->whereBetween('expense_date', [
                            Carbon::parse($request->start_date)->startOfDay(),
                            Carbon::parse($request->end_date)->endOfDay()
                        ]);
                    }
                    break;
            }
        }
        
        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sort expenses
        $sortBy = $request->input('sort_by', 'expense_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSortFields = ['id', 'description', 'amount', 'expense_date', 'created_at', 'updated_at'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('expense_date', 'desc');
        }
        
        // Paginate the results
        $perPage = $request->input('per_page', 15);
        $expenses = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $expenses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $expense = Expense::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'description' => $request->description,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'notes' => $request->notes,
        ]);

        // Load the category relation
        $expense->load('category');

        return response()->json([
            'status' => 'success',
            'message' => 'Expense created successfully',
            'data' => $expense,
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $expense = Expense::with('category')
            ->where('user_id', Auth::id())
            ->find($id);

        if (!$expense) {
            return response()->json([
                'status' => 'error',
                'message' => 'Expense not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $expense,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'description' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'expense_date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        $expense = Expense::where('user_id', Auth::id())->find($id);

        if (!$expense) {
            return response()->json([
                'status' => 'error',
                'message' => 'Expense not found',
            ], 404);
        }

        $expense->update($request->only([
            'category_id',
            'description',
            'amount',
            'expense_date',
            'notes',
        ]));

        // Load the category relation
        $expense->load('category');

        return response()->json([
            'status' => 'success',
            'message' => 'Expense updated successfully',
            'data' => $expense,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $expense = Expense::where('user_id', Auth::id())->find($id);

        if (!$expense) {
            return response()->json([
                'status' => 'error',
                'message' => 'Expense not found',
            ], 404);
        }

        $expense->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Expense deleted successfully',
        ]);
    }

    /**
     * Get expense summary statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary(Request $request)
    {
        $query = Expense::where('user_id', Auth::id());
        
        // Apply date filtering if provided
        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'past_week':
                    $query->where('expense_date', '>=', Carbon::now()->subWeek());
                    break;
                case 'past_month':
                    $query->where('expense_date', '>=', Carbon::now()->subMonth());
                    break;
                case 'last_3_months':
                    $query->where('expense_date', '>=', Carbon::now()->subMonths(3));
                    break;
                case 'custom':
                    if ($request->has('start_date') && $request->has('end_date')) {
                        $query->whereBetween('expense_date', [
                            Carbon::parse($request->start_date)->startOfDay(),
                            Carbon::parse($request->end_date)->endOfDay()
                        ]);
                    }
                    break;
            }
        }

        // Get total amount spent
        $totalAmount = $query->sum('amount');
        
        // Get amount spent by category
        $expensesByCategory = Expense::where('user_id', Auth::id())
            ->select('category_id')
            ->selectRaw('SUM(amount) as total')
            ->with('category:id,name')
            ->groupBy('category_id')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_amount' => $totalAmount,
                'by_category' => $expensesByCategory
            ],
        ]);
    }
}
