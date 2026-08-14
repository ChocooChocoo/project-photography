<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Models\Admin\CategoriesModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoriesController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $categories = CategoriesModel::all();
        return view('admin.view-categories', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('admin.create-categories');
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = CategoriesModel::create([
                'category_name' => $request->category_name,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully!',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'category_name' => ['required', 'string', 'max:255', Rule::unique('tbl_categories', 'category_name')->whereNull('deleted_at')->ignore($id)],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            $category = CategoriesModel::findOrFail($id);
            $category->update([
                'category_name' => $request->category_name,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully!',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories data for DataTables.
     */
    public function getCategories(Request $request): JsonResponse
    {
        $query = CategoriesModel::query();
        
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('category_name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $totalRecords = $query->count();
        $categories = $query->skip($request->start)->take($request->length)->get();

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $categories
        ]);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $category = CategoriesModel::findOrFail($id);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}