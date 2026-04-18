<?php

namespace Tests\Unit\Procurement;

use App\Models\Procurement\ProcurementRequestModel;
use App\Services\ProcurementWorkflowService;
use Tests\TestCase;

class ProcurementRequestModelTest extends TestCase
{
    /**
     * It allows requester edits only for editable workflow states.
     */
    public function test_requester_can_only_edit_draft_or_returned_requests(): void
    {
        $draftRequest = new ProcurementRequestModel(['status' => ProcurementRequestModel::STATUS_DRAFT]);
        $returnedRequest = new ProcurementRequestModel(['status' => ProcurementRequestModel::STATUS_RETURNED_FOR_REVISION]);
        $approvedRequest = new ProcurementRequestModel(['status' => ProcurementRequestModel::STATUS_APPROVED]);

        $this->assertTrue($draftRequest->canBeEditedByRequester());
        $this->assertTrue($returnedRequest->canBeEditedByRequester());
        $this->assertFalse($approvedRequest->canBeEditedByRequester());
    }

    /**
     * It exposes human-readable labels for procurement statuses.
     */
    public function test_status_label_accessor_formats_statuses(): void
    {
        $procurementRequest = new ProcurementRequestModel([
            'status' => ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL,
        ]);

        $this->assertSame('Pending Owner Approval', $procurementRequest->status_label);

        $procurementRequest->status = ProcurementRequestModel::STATUS_DEFECT_REPORTED;
        $this->assertSame('Defect Reported', $procurementRequest->status_label);
    }

    /**
     * It only allows requester cancellation while the request is still in-flight.
     */
    public function test_requester_cannot_cancel_completed_requests(): void
    {
        $pendingRequest = new ProcurementRequestModel(['status' => ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW]);
        $completedRequest = new ProcurementRequestModel(['status' => ProcurementRequestModel::STATUS_COMPLETED]);

        $this->assertTrue($pendingRequest->canBeCancelledByRequester());
        $this->assertFalse($completedRequest->canBeCancelledByRequester());
    }

    /**
     * It exposes the fixed defect reason list, including Other.
     */
    public function test_defect_reason_options_include_other(): void
    {
        $this->assertArrayHasKey('other', ProcurementWorkflowService::DEFECT_REASON_OPTIONS);
        $this->assertSame('Other', ProcurementWorkflowService::DEFECT_REASON_OPTIONS['other']);
    }
}
