<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionPlanRequest;
use App\Models\SubscriptionPlanModel;
use App\Models\StudioPlanModel;
use App\Models\FreelancerPlanModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index()
    {
        $plans = SubscriptionPlanModel::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.view-subscription-plans', compact('plans'));
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create()
    {
        return view('admin.create-subscription-plans');
    }

    /**
     * Store a newly created subscription plan in storage.
     */
    public function store(SubscriptionPlanRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Create the subscription plan
            $plan = SubscriptionPlanModel::create([
                'user_type' => $validated['user_type'],
                'plan_type' => $validated['plan_type'],
                'billing_cycle' => $validated['billing_cycle'],
                'plan_code' => $validated['plan_code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'commission_rate' => $validated['commission_rate'],
                'trial_days' => $validated['trial_days'] ?? 0,
                'max_booking' => $validated['max_booking'] ?? null,
                'max_studio_photographers' => $validated['max_studio_photographers'] ?? null,
                'max_studios' => $validated['max_studios'] ?? null,
                'staff_limit' => $validated['staff_limit'] ?? null,
                'priority_level' => $validated['priority_level'] ?? 0,
                'features' => $validated['features'],
                'support_level' => $validated['support_level'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan created successfully!',
                'data' => $plan,
                'redirect' => route('admin.subscription.index')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription plan. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified subscription plan.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $plan = SubscriptionPlanModel::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $plan
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan not found.'
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified subscription plan.
     */
    public function edit(string $id)
    {
        $plan = SubscriptionPlanModel::findOrFail($id);
        return view('admin.edit-subscription-plans', compact('plan'));
    }

    /**
     * Update the specified subscription plan in storage.
     */
    public function update(SubscriptionPlanRequest $request, string $id): JsonResponse
    {
        try {
            $plan = SubscriptionPlanModel::findOrFail($id);
            $validated = $request->validated();
            
            $plan->update([
                'user_type' => $validated['user_type'],
                'plan_type' => $validated['plan_type'],
                'billing_cycle' => $validated['billing_cycle'],
                'plan_code' => $validated['plan_code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'commission_rate' => $validated['commission_rate'],
                'trial_days' => $validated['trial_days'] ?? 0,
                'max_booking' => $validated['max_booking'] ?? null,
                'max_studio_photographers' => $validated['max_studio_photographers'] ?? null,
                'max_studios' => $validated['max_studios'] ?? null,
                'staff_limit' => $validated['staff_limit'] ?? null,
                'priority_level' => $validated['priority_level'] ?? 0,
                'features' => $validated['features'],
                'support_level' => $validated['support_level'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan updated successfully!',
                'data' => $plan,
                'redirect' => route('admin.subscription.index')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription plan. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified subscription plan from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $plan = SubscriptionPlanModel::findOrFail($id);
            
            // Check if plan has active subscriptions
            if ($plan->studioSubscriptions()->where('status', 'active')->exists() ||
                $plan->freelancerSubscriptions()->where('status', 'active')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete plan with active subscriptions.'
                ], 400);
            }
            
            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription plan. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get subscription plans data for DataTable.
     */
    public function getPlans(Request $request): JsonResponse
    {
        $query = SubscriptionPlanModel::query();
        
        // Search
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('plan_code', 'like', "%{$search}%")
                ->orWhere('user_type', 'like', "%{$search}%")
                ->orWhere('plan_type', 'like', "%{$search}%");
            });
        }

        // Filter by user type
        if ($request->has('user_type') && !empty($request->user_type)) {
            $query->where('user_type', $request->user_type);
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $totalRecords = $query->count();
        
        // Ordering
        $columns = ['user_type', 'plan_type', 'billing_cycle', 'name', 'price', 'commission_rate', 'max_booking', 'max_studio_photographers', 'support_level', 'status'];
        
        if ($request->has('order')) {
            $orderColumn = $columns[$request->order[0]['column']] ?? 'created_at';
            $orderDirection = $request->order[0]['dir'] ?? 'desc';
            $query->orderBy($orderColumn, $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $plans = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $data = $plans->map(function($plan) {
            return [
                'user_type' => ucfirst($plan->user_type),
                'plan_type' => $plan->formatted_plan_type,
                'billing_cycle' => $plan->formatted_billing_cycle,
                'name' => $plan->name,
                'price' => $plan->formatted_price,
                'commission_rate' => $plan->commission_rate . '%',
                'max_booking' => $plan->max_booking_display,
                'max_photographers' => $plan->user_type === 'studio' ? $plan->max_studio_photographers_display : 'N/A',
                'support_level' => $plan->formatted_support_level,
                'status' => $plan->status === 'active' 
                    ? '<span class="badge badge-soft-success">Active</span>'
                    : '<span class="badge badge-soft-secondary">Inactive</span>',
                'actions' => '
                    <div class="d-flex justify-content-center gap-1">
                        <button class="btn btn-sm btn-view" data-id="' . $plan->id . '" data-bs-toggle="modal" data-bs-target="#viewPlanModal">
                            <i class="ti ti-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-edit" data-id="' . $plan->id . '">
                            <i class="ti ti-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-delete" data-id="' . $plan->id . '">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                '
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }
}