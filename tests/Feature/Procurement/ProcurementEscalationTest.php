<?php

namespace Tests\Feature\Procurement;

use App\Models\NotificationModel;
use App\Models\Procurement\ProcurementAuditTrailModel;
use App\Models\Procurement\ProcurementRequestModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProcurementEscalationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        Carbon::setTestNow('2026-08-03 10:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_overdue_procurement_requests_are_escalated_with_audit_and_notification(): void
    {
        $owner = $this->createUser('owner', 'esc-owner@example.com');
        $hr = $this->createUser('studio-hr', 'esc-hr@example.com');
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Escalation Studio',
            'status' => 'verified',
        ]);

        $overdue = $this->createRequest($studio, $hr, [
            'status' => ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL,
            'created_at' => now()->subDays(3),
        ]);
        $recent = $this->createRequest($studio, $hr, [
            'status' => ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW,
            'created_at' => now()->subHours(10),
        ]);
        $alreadyEscalated = $this->createRequest($studio, $hr, [
            'status' => ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL,
            'created_at' => now()->subDays(5),
            'escalated_at' => now()->subDay(),
        ]);

        $this->artisan('procurement:escalate-overdue')->assertSuccessful();

        $this->assertNotNull($overdue->fresh()->escalated_at);
        $this->assertNull($recent->fresh()->escalated_at);
        $this->assertSame($alreadyEscalated->fresh()->escalated_at->toDateTimeString(), now()->subDay()->toDateTimeString());

        $this->assertDatabaseHas('tbl_procurement_audit_trails', [
            'procurement_request_id' => $overdue->id,
            'action' => 'overdue_escalated',
        ]);
        $this->assertDatabaseMissing('tbl_procurement_audit_trails', [
            'procurement_request_id' => $recent->id,
            'action' => 'overdue_escalated',
        ]);

        $notification = NotificationModel::where('type', 'procurement_overdue')->sole();
        $this->assertSame($owner->id, $notification->user_id);
        $this->assertSame($overdue->id, $notification->data['procurement_request_id']);
    }

    public function test_no_owner_studio_means_no_escalation_notification(): void
    {
        $owner = $this->createUser('owner', 'orphan-owner@example.com');
        $hr = $this->createUser('studio-hr', 'orphan-hr@example.com');
        $studio = StudiosModel::create([
            'user_id' => $owner->id,
            'studio_name' => 'Orphan Studio',
            'status' => 'verified',
        ]);

        $request = $this->createRequest($studio, $hr, [
            'status' => ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL,
            'created_at' => now()->subDays(3),
        ]);

        $owner->delete();

        $this->artisan('procurement:escalate-overdue')->assertSuccessful();

        $this->assertNotNull($request->fresh()->escalated_at);
        $this->assertDatabaseCount('tbl_notifications', 0);
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => 'Escalation',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000001',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createRequest(StudiosModel $studio, UserModel $hr, array $overrides = []): ProcurementRequestModel
    {
        $timestamps = [
            'created_at' => $overrides['created_at'] ?? null,
            'escalated_at' => $overrides['escalated_at'] ?? null,
        ];
        unset($overrides['created_at'], $overrides['escalated_at']);

        $request = ProcurementRequestModel::create(array_merge([
            'request_reference' => 'PR-'.str()->upper(str()->random(8)),
            'studio_id' => $studio->id,
            'requester_id' => $hr->id,
            'requester_role' => 'studio-hr',
            'status' => ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW,
            'is_urgent' => false,
            'is_high_value' => false,
            'purpose' => 'Replace worn-out lighting equipment.',
            'estimated_total' => 5000,
        ], $overrides));

        $request->created_at = $timestamps['created_at'];
        $request->escalated_at = $timestamps['escalated_at'];
        $request->save();

        return $request;
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
        Schema::create('tbl_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('icon')->default('bell');
            $table->string('color')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
