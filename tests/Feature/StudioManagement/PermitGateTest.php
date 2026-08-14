<?php

namespace Tests\Feature\StudioManagement;

use App\Http\Controllers\StudioOwner\StudioController;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PermitGateTest extends TestCase
{
    private UserModel $owner;

    private array $uploadedPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createOwner();

        Route::middleware('permit.verified')->get('/_test/gate', fn () => 'passed');

        Route::get('/_test/permit-notice', [StudioController::class, 'permitNotice'])->name('owner.studio.permit.notice');
        Route::post('/_test/studio/{id}/permit/resubmit', [StudioController::class, 'resubmitPermit']);
        Route::get('/_test/owner/studios', [StudioController::class, 'index']);
        Route::delete('/_test/studio/{id}', [StudioController::class, 'destroy']);
    }

    protected function tearDown(): void
    {
        foreach ($this->uploadedPaths as $path) {
            Storage::disk('public')->delete($path);
        }

        parent::tearDown();
    }

    public function test_owner_with_no_studio_passes_the_gate(): void
    {
        $this->actingAs($this->owner)
            ->get('/_test/gate')
            ->assertOk()
            ->assertSee('passed');
    }

    public function test_owner_with_verified_studio_and_valid_permit_passes_the_gate(): void
    {
        $this->createStudio('verified', Carbon::today()->addYear());

        $this->actingAs($this->owner)
            ->get('/_test/gate')
            ->assertOk()
            ->assertSee('passed');
    }

    public function test_owner_with_active_studio_and_valid_permit_passes_the_gate(): void
    {
        $this->createStudio('active', Carbon::today()->addMonths(6));

        $this->actingAs($this->owner)
            ->get('/_test/gate')
            ->assertOk()
            ->assertSee('passed');
    }

    public function test_owner_with_verified_studio_without_expiry_date_passes_the_gate(): void
    {
        $this->createStudio('verified', null);

        $this->actingAs($this->owner)
            ->get('/_test/gate')
            ->assertOk()
            ->assertSee('passed');
    }

    public function test_owner_with_pending_studio_is_redirected_to_the_permit_notice(): void
    {
        $this->createStudio('pending', Carbon::today()->addYear());

        $this->actingAs($this->owner)
            ->get('/_test/gate')
            ->assertRedirect(route('owner.studio.permit.notice'));
    }

    public function test_owner_with_expired_permit_is_redirected_to_the_permit_notice(): void
    {
        $this->createStudio('verified', Carbon::today()->subDay());

        $this->actingAs($this->owner)
            ->get('/_test/gate')
            ->assertRedirect(route('owner.studio.permit.notice'));
    }

    public function test_owner_with_one_usable_studio_passes_even_when_another_is_pending(): void
    {
        $this->createStudio('pending', Carbon::today()->addYear());
        $this->createStudio('verified', Carbon::today()->addYear());

        $this->actingAs($this->owner)
            ->get('/_test/gate')
            ->assertOk()
            ->assertSee('passed');
    }

    public function test_soft_deleted_studio_is_excluded_from_the_gate_and_the_list(): void
    {
        $studio = $this->createStudio('pending', Carbon::today()->addYear());

        $this->actingAs($this->owner)
            ->delete('/_test/studio/'.$studio->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull(StudiosModel::withTrashed()->find($studio->id)->deleted_at);
        $this->assertNull(StudiosModel::find($studio->id));

        // The owner studio list excludes soft-deleted records.
        $this->actingAs($this->owner)
            ->get('/_test/owner/studios')
            ->assertOk()
            ->assertDontSee('Permit Studio');

        // A deleted studio no longer counts towards the gate; the owner passes.
        $this->actingAs($this->owner)
            ->get('/_test/gate')
            ->assertOk()
            ->assertSee('passed');
    }

    public function test_resubmit_permit_resets_status_increments_count_and_uploads_the_file(): void
    {
        $studio = $this->createStudio('verified', Carbon::today()->subDay());
        $studio->update([
            'business_permit' => 'studio_documents/old-permit.pdf',
            'resubmission_count' => 2,
            'rejection_note' => 'Permit image was blurry.',
        ]);

        $this->actingAs($this->owner)
            ->post('/_test/studio/'.$studio->id.'/permit/resubmit', [
                'business_permit' => UploadedFile::fake()->create('new-permit.pdf', 100, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $fresh = $studio->fresh();

        $this->assertSame('pending', $fresh->status);
        $this->assertSame(3, $fresh->resubmission_count);
        $this->assertStringStartsWith('studio_documents/', $fresh->business_permit);
        $this->assertNotSame('studio_documents/old-permit.pdf', $fresh->business_permit);
        $this->assertTrue(Storage::disk('public')->exists($fresh->business_permit));

        $this->uploadedPaths[] = $fresh->business_permit;
    }

    public function test_resubmit_permit_requires_a_file_upload(): void
    {
        $studio = $this->createStudio('verified', Carbon::today()->subDay());

        $this->actingAs($this->owner)
            ->post('/_test/studio/'.$studio->id.'/permit/resubmit', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('business_permit');

        $this->assertSame('verified', $studio->fresh()->status);
    }

    public function test_resubmit_permit_is_scoped_to_the_studios_owner(): void
    {
        $intruder = $this->createUser('owner', 'intruder@example.com');
        $studio = $this->createStudio('verified', Carbon::today()->subDay());

        $this->actingAs($intruder)
            ->post('/_test/studio/'.$studio->id.'/permit/resubmit', [
                'business_permit' => UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(500);
    }

    public function test_permit_notice_page_shows_status_expiry_rejection_note_and_resubmit_form(): void
    {
        $this->createStudio('verified', Carbon::today()->subDay())->update([
            'rejection_note' => 'The permit document was not readable.',
        ]);

        $this->actingAs($this->owner)
            ->get('/_test/permit-notice')
            ->assertOk()
            ->assertSee('Permit Studio')
            ->assertSee('EXPIRED')
            ->assertSee('The permit document was not readable.')
            ->assertSee('business_permit')
            ->assertSee('Resubmit Permit');
    }

    private function createOwner(): UserModel
    {
        return $this->createUser('owner', 'permit-owner@example.com');
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => 'photographer',
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000006',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(string $status, ?Carbon $expiry): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Permit Studio',
            'status' => $status,
            'permit_expiry_date' => $expiry?->format('Y-m-d'),
        ]);
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
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->string('status');
            $table->date('permit_expiry_date')->nullable();
            $table->unsignedInteger('resubmission_count')->default(0);
            $table->string('business_permit')->nullable();
            $table->text('rejection_note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->string('municipality');
            $table->string('province');
            $table->string('zip_code')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('portal');
            $table->text('description')->nullable();
            $table->string('status');
            $table->boolean('is_system')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('studio_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('pvt_studio_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('category_id');
            $table->timestamps();
        });
        Schema::create('tbl_studio_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->timestamps();
        });
    }
}
