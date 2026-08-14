<?php

namespace Tests\Feature\Procurement;

use App\Http\Controllers\Finance\ProcurementController as FinanceProcurementController;
use App\Http\Controllers\StudioHR\ProcurementRequestController as HrProcurementRequestController;
use App\Http\Controllers\StudioOwner\ProcurementApprovalController;
use App\Models\Procurement\ProcurementAssetModel;
use App\Models\Procurement\ProcurementAuditTrailModel;
use App\Models\Procurement\ProcurementPurchaseOrderModel;
use App\Models\Procurement\ProcurementRequestItemModel;
use App\Models\Procurement\ProcurementRequestModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcurementWorkflowTest extends TestCase
{
    private UserModel $hr;
    private UserModel $finance;
    private UserModel $owner;
    private StudiosModel $studio;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-03 10:30:00');

        $this->hr = $this->createUser('studio-hr', 'proc-hr@example.com');
        $this->finance = $this->createUser('studio-finance', 'proc-finance@example.com');
        $this->owner = $this->createUser('owner', 'proc-owner@example.com');

        $this->studio = StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Procurement Studio',
            'status' => 'verified',
        ]);

        $this->grantRole($this->hr, 'studio-hr', ['studio-hr.procurement.manage']);
        $this->grantRole($this->finance, 'studio-finance', [
            'studio-finance.procurement.review',
            'studio-finance.procurement.order',
            'studio-finance.procurement.payment',
        ]);
        $this->grantRole($this->owner, 'owner', ['owner.procurement.approve']);

        Storage::fake('public');

        Route::post('/_test/hr/procurement', [HrProcurementRequestController::class, 'store']);
        Route::post('/_test/hr/procurement/{id}/confirm-receipt', [HrProcurementRequestController::class, 'confirmReceipt']);
        Route::post('/_test/finance/procurement/{id}/review', [FinanceProcurementController::class, 'review']);
        Route::post('/_test/finance/procurement/{id}/purchase-order', [FinanceProcurementController::class, 'storePurchaseOrder']);
        Route::post('/_test/finance/procurement/{id}/delivery', [FinanceProcurementController::class, 'recordDelivery']);
        Route::post('/_test/finance/procurement/{id}/payment', [FinanceProcurementController::class, 'recordPayment']);
        Route::post('/_test/finance/procurement/{id}/complete', [FinanceProcurementController::class, 'complete']);
        Route::post('/_test/owner/procurement/{id}/process', [ProcurementApprovalController::class, 'process']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_procurement_full_happy_path(): void
    {
        // 1. HR submits the request.
        Auth::setUser($this->hr);
        $this->postJson('/_test/hr/procurement', [
            'action' => 'submit',
            'purpose' => 'Replace worn-out studio camera lens.',
            'required_date' => '2026-08-20',
            'is_urgent' => false,
            'items' => [[
                'item_name' => 'Camera Lens',
                'description' => '24-70mm f/2.8 zoom lens',
                'category' => 'equipment',
                'quantity' => 1,
                'unit_of_measure' => 'pcs',
                'estimated_unit_cost' => 25000,
                'preferred_supplier' => 'Cameras R Us',
            ]],
        ])->assertOk()->assertJsonPath('data.status', 'pending_finance_review');

        $request = ProcurementRequestModel::firstOrFail();
        $this->assertSame('pending_finance_review', $request->status);
        $this->assertAudit($request->id, 'request_submitted');

        // 2. Finance approves.
        Auth::setUser($this->finance);
        $this->postJson("/_test/finance/procurement/{$request->id}/review", [
            'action' => 'approve',
            'note' => 'Budget verified.',
        ])->assertOk()->assertJsonPath('data.status', 'pending_owner_approval');

        $request->refresh();
        $this->assertSame('pending_owner_approval', $request->status);
        $this->assertAudit($request->id, 'finance_approve');

        // 3. Owner approves.
        Auth::setUser($this->owner);
        $this->postJson("/_test/owner/procurement/{$request->id}/process", [
            'action' => 'approve',
            'note' => 'Approved.',
        ])->assertOk()->assertJsonPath('data.status', 'approved');

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertAudit($request->id, 'owner_approve');

        // 4. Finance generates the purchase order.
        Auth::setUser($this->finance);
        $item = ProcurementRequestItemModel::where('procurement_request_id', $request->id)->firstOrFail();
        $this->postJson("/_test/finance/procurement/{$request->id}/purchase-order", [
            'supplier_name' => 'Cameras R Us',
            'supplier_email' => 'sales@camerasrus.ph',
            'supplier_contact_number' => '09171112222',
            'supplier_address' => '123 Shutter Street, Makati',
            'delivery_address' => 'Procurement Studio, Makati',
            'payment_terms' => 'Net 30',
            'order_date' => '2026-08-04',
            'notes' => 'Replacement lens order.',
            'items' => [[
                'procurement_request_item_id' => $item->id,
                'approved_unit_cost' => 25000,
            ]],
        ])->assertOk()->assertJsonPath('data.status', 'ordered');

        $request->refresh();
        $this->assertSame('ordered', $request->status);
        $this->assertAudit($request->id, 'purchase_order_generated');
        $purchaseOrder = ProcurementPurchaseOrderModel::where('procurement_request_id', $request->id)->firstOrFail();
        $this->assertNotEmpty($purchaseOrder->po_number);
        $this->assertSame('25000.00', (string) $purchaseOrder->total_amount);

        // 5. Finance records delivery.
        $this->post("/_test/finance/procurement/{$request->id}/delivery", [
            'delivered_at' => '2026-08-10 09:00:00',
            'delivery_note' => 'Delivered by courier.',
            'delivery_receipt_files' => [UploadedFile::fake()->create('delivery-receipt.pdf', 100, 'application/pdf')],
        ])->assertOk()->assertJsonPath('data.status', 'delivered');

        $request->refresh();
        $this->assertSame('delivered', $request->status);
        $this->assertAudit($request->id, 'delivery_recorded');

        // 6. HR confirms receipt (equipment asset recorded).
        Auth::setUser($this->hr);
        $this->postJson("/_test/hr/procurement/{$request->id}/confirm-receipt", [
            'receipt_note' => 'Received in perfect condition.',
            'items' => [[
                'procurement_request_item_id' => $item->id,
                'receipt_action' => 'accepted',
                'received_quantity' => 1,
                'condition_notes' => 'Brand new, sealed.',
                'serial_number' => 'SN-LENS-0001',
                'warranty_expires_at' => '2028-08-10',
                'acquisition_cost' => 25000,
                'asset_location' => 'Equipment Room',
            ]],
        ])->assertOk()->assertJsonPath('data.status', 'received');

        $request->refresh();
        $this->assertSame('received', $request->status);
        $this->assertAudit($request->id, 'receipt_confirmed');
        $this->assertTrue(ProcurementAssetModel::where('procurement_request_item_id', $item->id)->exists());

        // 7. Finance records payment (three-way match: PO total = approved total = invoice).
        Auth::setUser($this->finance);
        $this->post("/_test/finance/procurement/{$request->id}/payment", [
            'invoice_reference' => 'INV-2026-0001',
            'invoice_amount' => 25000,
            'invoice_date' => '2026-08-11',
            'payment_reference' => 'PAY-BANK-0001',
            'payment_note' => 'Paid via bank transfer.',
            'supplier_invoice_files' => [UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf')],
            'payment_proof_files' => [UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf')],
        ])->assertOk()->assertJsonPath('data.status', 'payment_processing');

        $request->refresh();
        $this->assertSame('payment_processing', $request->status);
        $this->assertAudit($request->id, 'payment_processing_started');

        // 8. Finance completes the request.
        $this->postJson("/_test/finance/procurement/{$request->id}/complete")
            ->assertOk()->assertJsonPath('data.status', 'completed');

        $request->refresh();
        $this->assertSame('completed', $request->status);
        $this->assertNotNull($request->completed_at);
        $this->assertAudit($request->id, 'payment_completed');
    }

    private function assertAudit(int $requestId, string $action): void
    {
        $this->assertTrue(
            ProcurementAuditTrailModel::where('procurement_request_id', $requestId)
                ->where('action', $action)
                ->exists(),
            "Expected audit action '{$action}' to be recorded."
        );
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Proc',
            'last_name' => ucfirst($role),
            'email' => $email,
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function grantRole(UserModel $user, string $portal, array $permissions): void
    {
        $role = RoleModel::create([
            'name' => $portal.'-procurement',
            'portal' => $portal,
            'status' => 'active',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = PermissionModel::create([
                'name' => $permissionName,
                'portal' => $portal,
                'permission_string' => $permissionName,
                'status' => 'active',
            ]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id, ['studio_id' => $this->studio->id]);
    }

    private function createSchema(): void
    {
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('role');
            $table->string('user_type')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('mobile_number');
            $table->string('password');
            $table->string('status');
            $table->boolean('email_verified')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('portal', 50)->default('studio');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('portal', 50)->default('studio');
            $table->string('permission_string', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });
        Schema::create('tbl_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('studio_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'role_id', 'studio_id']);
        });
        Schema::create('tbl_procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference', 30)->unique();
            $table->unsignedBigInteger('studio_id');
            $table->unsignedBigInteger('requester_id');
            $table->string('requester_role', 50);
            $table->string('status', 50)->default('draft');
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_high_value')->default(false);
            $table->date('required_date')->nullable();
            $table->text('purpose')->nullable();
            $table->text('inventory_bypass_reason')->nullable();
            $table->text('finance_review_note')->nullable();
            $table->text('owner_review_note')->nullable();
            $table->decimal('estimated_total', 12, 2)->default(0);
            $table->decimal('approved_total', 12, 2)->default(0);
            $table->string('invoice_reference', 100)->nullable();
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('payment_note')->nullable();
            $table->unsignedBigInteger('finance_reviewed_by')->nullable();
            $table->timestamp('finance_reviewed_at')->nullable();
            $table->unsignedBigInteger('owner_reviewed_by')->nullable();
            $table->timestamp('owner_reviewed_at')->nullable();
            $table->unsignedBigInteger('receipt_confirmed_by')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('receipt_confirmed_at')->nullable();
            $table->timestamp('payment_processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_procurement_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_request_id');
            $table->string('item_name');
            $table->string('normalized_item_name');
            $table->text('description')->nullable();
            $table->string('category', 20);
            $table->string('expense_type', 20);
            $table->decimal('quantity', 10, 2);
            $table->string('unit_of_measure', 50);
            $table->decimal('estimated_unit_cost', 12, 2)->default(0);
            $table->decimal('estimated_total_cost', 12, 2)->default(0);
            $table->decimal('approved_unit_cost', 12, 2)->nullable();
            $table->decimal('approved_total_cost', 12, 2)->nullable();
            $table->decimal('received_quantity', 10, 2)->default(0);
            $table->text('condition_notes')->nullable();
            $table->string('preferred_supplier')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_procurement_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_request_id');
            $table->string('po_number', 30)->unique();
            $table->string('supplier_name');
            $table->string('supplier_email')->nullable();
            $table->string('supplier_contact_number', 50)->nullable();
            $table->text('supplier_address')->nullable();
            $table->text('delivery_address');
            $table->string('payment_terms', 150);
            $table->date('order_date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('ordered_by')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_procurement_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('procurement_request_item_id');
            $table->string('item_name');
            $table->decimal('quantity', 10, 2);
            $table->string('unit_of_measure', 50);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });
        Schema::create('tbl_procurement_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_request_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('document_type', 50);
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_procurement_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_request_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 100);
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_procurement_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_request_id');
            $table->unsignedBigInteger('procurement_request_item_id');
            $table->unsignedBigInteger('studio_id');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->string('asset_name');
            $table->string('serial_number');
            $table->date('warranty_expires_at')->nullable();
            $table->decimal('acquisition_cost', 12, 2);
            $table->string('location');
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });
        Schema::create('tbl_procurement_inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('studio_id');
            $table->unsignedBigInteger('procurement_request_id')->nullable();
            $table->unsignedBigInteger('procurement_request_item_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('item_name');
            $table->string('normalized_item_name');
            $table->text('description')->nullable();
            $table->string('unit_of_measure', 50);
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('reorder_threshold', 10, 2)->default(0);
            $table->decimal('last_recorded_cost', 12, 2)->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_procurement_defect_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_request_id');
            $table->unsignedBigInteger('procurement_request_item_id');
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->decimal('reported_quantity', 10, 2);
            $table->string('reason_code', 50);
            $table->text('reason_other')->nullable();
            $table->text('requester_note')->nullable();
            $table->text('finance_note')->nullable();
            $table->string('status', 50)->default('reported');
            $table->timestamp('reported_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('replacement_delivered_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }
}
