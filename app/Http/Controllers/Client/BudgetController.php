<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientBudgetModel;
use App\Models\Admin\CategoriesModel;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BudgetController extends Controller
{
    /**
     * Display the budget page with list of budgets.
     */
    public function index()
    {
        // Get all budgets for the authenticated client
        $budgets = ClientBudgetModel::with('category')
            ->byClient(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get categories for dropdowns
        $categories = CategoriesModel::active()
            ->orderBy('category_name')
            ->get(['id', 'category_name']);
        
        return view('client.set-budget', compact('budgets', 'categories'));
    }

    /**
     * Get all budgets for the authenticated client (AJAX) - Keep for filtering
     */
    public function getBudgets(Request $request)
    {
        try {
            $clientId = Auth::id();
            
            $query = ClientBudgetModel::with('category')
                ->byClient($clientId);
            
            // Apply filters if provided
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            if ($request->filled('budget_type')) {
                $query->where('budget_type', $request->budget_type);
            }
            
            $budgets = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'budgets' => $budgets
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch budgets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created budget.
     */
    public function store(StoreBudgetRequest $request)
    {
        try {
            // Create budget
            $budget = ClientBudgetModel::create([
                'client_id' => Auth::id(),
                'budget_name' => $request->budget_name,
                'description' => $request->description,
                'minimum_budget' => $request->minimum_budget,
                'maximum_budget' => $request->maximum_budget,
                'preferred_budget' => $request->preferred_budget,
                'category_id' => $request->category_id,
                'budget_type' => $request->budget_type,
                'status' => $request->status,
            ]);

            // Load relationship for response
            $budget->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Budget created successfully',
                'budget' => $budget
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified budget.
     */
    public function show($id)
    {
        try {
            $budget = ClientBudgetModel::with('category')
                ->where('client_id', Auth::id())
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'budget' => $budget
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Budget not found'
            ], 404);
        }
    }

    /**
     * Update the specified budget.
     */
    public function update(UpdateBudgetRequest $request, $id)
    {
        try {
            // Find budget
            $budget = ClientBudgetModel::where('client_id', Auth::id())
                ->findOrFail($id);
            
            // Update budget
            $budget->update([
                'budget_name' => $request->budget_name,
                'description' => $request->description,
                'minimum_budget' => $request->minimum_budget,
                'maximum_budget' => $request->maximum_budget,
                'preferred_budget' => $request->preferred_budget,
                'category_id' => $request->category_id,
                'budget_type' => $request->budget_type,
                'status' => $request->status,
            ]);

            // Load relationship for response
            $budget->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Budget updated successfully',
                'budget' => $budget
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified budget.
     */
    public function destroy($id)
    {
        try {
            $budget = ClientBudgetModel::where('client_id', Auth::id())
                ->findOrFail($id);
            
            $budget->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Budget deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle budget status (active/inactive).
     */
    public function toggleStatus($id)
    {
        try {
            $budget = ClientBudgetModel::where('client_id', Auth::id())
                ->findOrFail($id);
            
            $budget->status = $budget->status === 'active' ? 'inactive' : 'active';
            $budget->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Budget status updated successfully',
                'status' => $budget->status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update budget status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories for dropdown (AJAX).
     */
    public function getCategories()
    {
        try {
            $categories = CategoriesModel::active()
                ->orderBy('category_name')
                ->get(['id', 'category_name']);
            
            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget statistics.
     */
    public function getStatistics()
    {
        try {
            $clientId = Auth::id();
            
            $stats = [
                'total_budgets' => ClientBudgetModel::byClient($clientId)->count(),
                'active_budgets' => ClientBudgetModel::byClient($clientId)->active()->count(),
                'inactive_budgets' => ClientBudgetModel::byClient($clientId)->inactive()->count(),
                'average_minimum' => ClientBudgetModel::byClient($clientId)->active()->avg('minimum_budget'),
                'average_maximum' => ClientBudgetModel::byClient($clientId)->active()->avg('maximum_budget'),
                'average_preferred' => ClientBudgetModel::byClient($clientId)->active()->avg('preferred_budget'),
            ];
            
            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}