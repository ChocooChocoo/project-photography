<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\StudioOwner\DiscountRuleModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscountRuleController extends Controller
{
    /**
     * Show the discount rules for the owner's studios.
     */
    public function index()
    {
        $user = Auth::user();
        $studios = StudiosModel::with(['discountRules' => function ($query) {
            $query->latest();
        }])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('owner.discounts', compact('studios'));
    }

    /**
     * Store a new discount rule for one of the owner's studios.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'studio_id' => 'required|exists:tbl_studios,id',
                'name' => 'required|string|max:100',
                'percentage' => 'required|numeric|min:0|max:100',
                'description' => 'nullable|string|max:500',
                'is_active' => 'nullable|boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = Auth::user();
        $studio = StudiosModel::where('id', $validatedData['studio_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$studio) {
            return response()->json([
                'success' => false,
                'message' => 'You can only manage discount rules for your own studios.',
            ], 403);
        }

        $rule = DiscountRuleModel::create([
            'studio_id' => $studio->id,
            'name' => $validatedData['name'],
            'percentage' => $validatedData['percentage'],
            'description' => $validatedData['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Discount rule created successfully.',
            'redirect' => route('owner.discounts.index'),
        ]);
    }

    /**
     * Update an existing discount rule.
     */
    public function update(Request $request, $rule)
    {
        try {
            $validatedData = $request->validate([
                'studio_id' => 'required|exists:tbl_studios,id',
                'name' => 'required|string|max:100',
                'percentage' => 'required|numeric|min:0|max:100',
                'description' => 'nullable|string|max:500',
                'is_active' => 'nullable|boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = Auth::user();
        $discountRule = $this->resolveOwnedRule($rule, $user->id, $validatedData['studio_id']);

        if (!$discountRule) {
            return response()->json([
                'success' => false,
                'message' => 'You can only manage discount rules for your own studios.',
            ], 403);
        }

        $discountRule->update([
            'name' => $validatedData['name'],
            'percentage' => $validatedData['percentage'],
            'description' => $validatedData['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Discount rule updated successfully.',
            'redirect' => route('owner.discounts.index'),
        ]);
    }

    /**
     * Soft delete a discount rule.
     */
    public function destroy($rule)
    {
        $user = Auth::user();
        $discountRule = DiscountRuleModel::where('id', $rule)
            ->whereHas('studio', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$discountRule) {
            return response()->json([
                'success' => false,
                'message' => 'You can only manage discount rules for your own studios.',
            ], 403);
        }

        $discountRule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Discount rule removed successfully.',
            'redirect' => route('owner.discounts.index'),
        ]);
    }

    /**
     * Find a rule belonging to one of the owner's studios.
     */
    private function resolveOwnedRule($rule, int $userId, int $studioId): ?DiscountRuleModel
    {
        return DiscountRuleModel::where('id', $rule)
            ->where('studio_id', $studioId)
            ->whereHas('studio', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->first();
    }
}
