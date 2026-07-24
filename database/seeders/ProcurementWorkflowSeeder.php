<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studioContexts = $this->resolveStudioContexts();

        if ($studioContexts->isEmpty()) {
            $this->command?->warn('No studio with owner, HR, finance, and photographer users was found. Procurement seeder skipped.');

            return;
        }

        $seedPayloads = $this->buildSeedPayloads($studioContexts);
        $requestReferences = collect($seedPayloads)->pluck('request.request_reference')->all();

        DB::transaction(function () use ($seedPayloads, $requestReferences) {
            $this->purgeExistingSeedData($requestReferences);

            foreach ($seedPayloads as $payload) {
                $this->seedProcurementRequest($payload);
            }
        });

        $this->command?->info('Seeded realistic procurement workflow data across all 8 procurement tables.');
    }

    /**
     * Resolve studio-specific users required by the procurement workflow.
     */
    private function resolveStudioContexts(): Collection
    {
        return DB::table('tbl_studios as studios')
            ->join('tbl_users as owners', 'owners.id', '=', 'studios.user_id')
            ->select(
                'studios.id as studio_id',
                'studios.studio_name',
                'studios.user_id as owner_id',
                'owners.first_name as owner_first_name',
                'owners.last_name as owner_last_name'
            )
            ->whereIn('studios.status', ['verified', 'active'])
            ->orderBy('studios.id')
            ->get()
            ->map(function ($studio) {
                $studioId = (int) $studio->studio_id;

                $financeUser = $this->resolveStudioPortalUser($studioId, 'studio-finance', 'studio-finance');
                $hrUser = $this->resolveStudioPortalUser($studioId, 'studio-hr', 'studio-hr');
                $photographerUser = $this->resolveStudioPortalUser($studioId, 'studio-photographer', 'studio-photographer');

                if (! $financeUser || ! $hrUser || ! $photographerUser) {
                    return null;
                }

                return [
                    'studio_id' => $studioId,
                    'studio_name' => $studio->studio_name,
                    'owner' => [
                        'id' => (int) $studio->owner_id,
                        'name' => trim($studio->owner_first_name.' '.$studio->owner_last_name),
                    ],
                    'finance' => $financeUser,
                    'hr' => $hrUser,
                    'photographer' => $photographerUser,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Resolve a portal user assigned to a specific studio.
     *
     * @return array<string, mixed>|null
     */
    private function resolveStudioPortalUser(int $studioId, string $portal, string $baseRole): ?array
    {
        $user = DB::table('tbl_user_roles as user_roles')
            ->join('tbl_roles as roles', 'roles.id', '=', 'user_roles.role_id')
            ->join('tbl_users as users', 'users.id', '=', 'user_roles.user_id')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.role',
                'roles.name as role_name'
            )
            ->where('user_roles.studio_id', $studioId)
            ->where('roles.portal', $portal)
            ->where('roles.status', 'active')
            ->where('users.status', 'active')
            ->orderByRaw("CASE WHEN roles.name LIKE '%manager%' THEN 0 ELSE 1 END")
            ->orderBy('users.id')
            ->first();

        if (! $user) {
            $user = DB::table('tbl_studio_employee_schedule as schedules')
                ->join('tbl_users as users', 'users.id', '=', 'schedules.user_id')
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.role')
                ->where('schedules.studio_id', $studioId)
                ->where('users.role', $baseRole)
                ->where('users.status', 'active')
                ->orderBy('users.id')
                ->first();
        }

        if (! $user && $portal === 'studio-photographer') {
            $user = DB::table('tbl_studio_photographers as photographers')
                ->join('tbl_users as users', 'users.id', '=', 'photographers.photographer_id')
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.role')
                ->where('photographers.studio_id', $studioId)
                ->where('users.status', 'active')
                ->orderBy('users.id')
                ->first();
        }

        if (! $user) {
            return null;
        }

        return [
            'id' => (int) $user->id,
            'role' => $user->role,
            'name' => trim($user->first_name.' '.$user->last_name),
        ];
    }

    /**
     * Build realistic linked procurement seed payloads.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $studioContexts
     * @return array<int, array<string, mixed>>
     */
    private function buildSeedPayloads(Collection $studioContexts): array
    {
        $primaryStudio = $studioContexts->get(0);
        $secondaryStudio = $studioContexts->get(1, $primaryStudio);
        $thirdStudio = $studioContexts->get(2, $secondaryStudio);

        $seedDate = Carbon::create(2026, 4, 16, 9, 0, 0, config('app.timezone'));

        return [
            $this->makeDraftRequest($primaryStudio, $seedDate),
            $this->makePendingFinanceRequest($secondaryStudio, $seedDate->copy()->subDays(1)),
            $this->makeReturnedRequest($primaryStudio, $seedDate->copy()->subDays(3)),
            $this->makePendingOwnerRequest($thirdStudio, $seedDate->copy()->subDays(4)),
            $this->makeOrderedRequest($secondaryStudio, $seedDate->copy()->subDays(6)),
            $this->makeDeliveredRequest($primaryStudio, $seedDate->copy()->subDays(8)),
            $this->makeReceivedRequest($thirdStudio, $seedDate->copy()->subDays(10)),
            $this->makeCompletedRequest($secondaryStudio, $seedDate->copy()->subDays(13)),
        ];
    }

    /**
     * Create a draft request payload.
     *
     * @param  array<string, mixed>  $studioContext
     * @return array<string, mixed>
     */
    private function makeDraftRequest(array $studioContext, Carbon $baseDate): array
    {
        $items = [
            $this->buildItem('Backdrop Stand Set', 'Heavy-duty backdrop stand for portrait setups.', 'equipment', 2, 'set', 3200.00, null, 0, [
                'created_at' => $baseDate->copy()->addMinutes(8),
                'updated_at' => $baseDate->copy()->addMinutes(8),
            ]),
            $this->buildItem('Color Gel Pack', 'Assorted gels for studio lighting accents.', 'consumable', 6, 'pack', 450.00, null, 0, [
                'created_at' => $baseDate->copy()->addMinutes(8),
                'updated_at' => $baseDate->copy()->addMinutes(8),
            ]),
        ];

        return [
            'request' => $this->buildRequestRecord(
                'PR-20260416-1001',
                $studioContext,
                $studioContext['photographer'],
                'draft',
                $baseDate,
                'Prepare a refreshed portrait corner setup for the upcoming graduation mini sessions.',
                $items,
                [
                    'required_date' => $baseDate->copy()->addDays(12)->toDateString(),
                    'is_urgent' => false,
                ]
            ),
            'items' => $items,
            'documents' => [
                $this->buildDocument(null, 'request_attachment', 'portrait-corner-moodboard.pdf', 'procurement/requests/PR-20260416-1001/moodboard.pdf', 'application/pdf', 264118, 'Initial concept board for the requester.', [
                    'stage' => 'draft',
                ], $studioContext['photographer']['id'], $baseDate->copy()->addMinutes(10)),
            ],
            'audits' => [
                $this->buildAudit($studioContext['photographer']['id'], 'created_draft', null, 'draft', 'Draft request saved with supporting mood board.', $baseDate->copy()->addMinutes(8), [
                    'portal' => 'studio-photographer',
                ]),
            ],
        ];
    }

    /**
     * Create a pending finance review payload.
     *
     * @param  array<string, mixed>  $studioContext
     * @return array<string, mixed>
     */
    private function makePendingFinanceRequest(array $studioContext, Carbon $baseDate): array
    {
        $items = [
            $this->buildItem('Wireless Lavalier Kit', 'Dual-channel wireless microphones for interview shoots.', 'equipment', 1, 'kit', 18500.00, null, 0, [
                'created_at' => $baseDate->copy()->addMinutes(18),
                'updated_at' => $baseDate->copy()->addMinutes(18),
            ]),
            $this->buildItem('AA Rechargeable Batteries', 'Backup batteries for microphones and triggers.', 'consumable', 12, 'piece', 220.00, null, 0, [
                'created_at' => $baseDate->copy()->addMinutes(18),
                'updated_at' => $baseDate->copy()->addMinutes(18),
            ]),
        ];

        return [
            'request' => $this->buildRequestRecord(
                'PR-20260416-1002',
                $studioContext,
                $studioContext['hr'],
                'pending_finance_review',
                $baseDate,
                'Support the studio interview package with cleaner on-set audio and reliable backup power.',
                $items,
                [
                    'required_date' => $baseDate->copy()->addDays(7)->toDateString(),
                    'is_urgent' => true,
                    'inventory_bypass_reason' => 'Existing microphone kit has intermittent signal drops during live client recordings.',
                ]
            ),
            'items' => $items,
            'documents' => [
                $this->buildDocument(null, 'request_attachment', 'audio-kit-justification.pdf', 'procurement/requests/PR-20260416-1002/audio-kit-justification.pdf', 'application/pdf', 193402, 'Technical issue report attached by HR.', [
                    'stage' => 'submission',
                ], $studioContext['hr']['id'], $baseDate->copy()->addMinutes(18)),
            ],
            'audits' => [
                $this->buildAudit($studioContext['hr']['id'], 'created_draft', null, 'draft', 'Initial draft created by HR.', $baseDate->copy()->addMinutes(5), [
                    'portal' => 'studio-hr',
                ]),
                $this->buildAudit($studioContext['hr']['id'], 'submitted', 'draft', 'pending_finance_review', 'Submitted to finance with an urgent replacement note.', $baseDate->copy()->addMinutes(18), [
                    'is_urgent' => true,
                ]),
            ],
        ];
    }

    /**
     * Create a returned-for-revision payload.
     *
     * @param  array<string, mixed>  $studioContext
     * @return array<string, mixed>
     */
    private function makeReturnedRequest(array $studioContext, Carbon $baseDate): array
    {
        $items = [
            $this->buildItem('Portable Fog Machine', 'Atmospheric fog machine for stylized pre-debut sessions.', 'equipment', 1, 'unit', 9800.00, 9400.00, 0, [
                'created_at' => $baseDate->copy()->addMinutes(15),
                'updated_at' => $baseDate->copy()->addDay()->addHours(3),
            ]),
            $this->buildItem('Fog Fluid', 'Compatible fluid for portable fog machine.', 'consumable', 4, 'bottle', 380.00, 360.00, 0, [
                'created_at' => $baseDate->copy()->addMinutes(15),
                'updated_at' => $baseDate->copy()->addDay()->addHours(3),
            ]),
        ];

        return [
            'request' => $this->buildRequestRecord(
                'PR-20260416-1003',
                $studioContext,
                $studioContext['photographer'],
                'returned_for_revision',
                $baseDate,
                'Add controlled atmosphere effects for creative portrait concepts requested by clients.',
                $items,
                [
                    'required_date' => $baseDate->copy()->addDays(10)->toDateString(),
                    'finance_review_note' => 'Please attach a client demand reference and confirm storage procedure before approval.',
                    'finance_reviewed_by' => $studioContext['finance']['id'],
                    'finance_reviewed_at' => $baseDate->copy()->addDay()->addHours(3),
                    'approved_total' => 10840.00,
                    'updated_at' => $baseDate->copy()->addDay()->addHours(3),
                ]
            ),
            'items' => $items,
            'documents' => [
                $this->buildDocument(null, 'request_attachment', 'creative-shoot-deck.pdf', 'procurement/requests/PR-20260416-1003/creative-shoot-deck.pdf', 'application/pdf', 441833, 'Creative peg deck from the photographer.', [
                    'stage' => 'submission',
                ], $studioContext['photographer']['id'], $baseDate->copy()->addMinutes(12)),
            ],
            'audits' => [
                $this->buildAudit($studioContext['photographer']['id'], 'created_draft', null, 'draft', 'Photographer drafted the request.', $baseDate->copy()->addMinutes(4), []),
                $this->buildAudit($studioContext['photographer']['id'], 'submitted', 'draft', 'pending_finance_review', 'Submitted to finance for review.', $baseDate->copy()->addMinutes(15), []),
                $this->buildAudit($studioContext['finance']['id'], 'finance_return', 'pending_finance_review', 'returned_for_revision', 'Returned for more justification and storage details.', $baseDate->copy()->addDay()->addHours(3), [
                    'required_attachment' => 'client-demand-reference',
                ]),
            ],
        ];
    }

    /**
     * Create a pending owner approval payload.
     *
     * @param  array<string, mixed>  $studioContext
     * @return array<string, mixed>
     */
    private function makePendingOwnerRequest(array $studioContext, Carbon $baseDate): array
    {
        $items = [
            $this->buildItem('NAS Backup Server', 'Centralized backup appliance for active project archives.', 'equipment', 1, 'unit', 68500.00, 67200.00, 0, [
                'created_at' => $baseDate->copy()->addMinutes(22),
                'updated_at' => $baseDate->copy()->addDay()->addHours(2),
            ]),
            $this->buildItem('Cat6 Patch Cable Set', 'Structured cabling accessories for the backup rack.', 'consumable', 10, 'piece', 180.00, 175.00, 0, [
                'created_at' => $baseDate->copy()->addMinutes(22),
                'updated_at' => $baseDate->copy()->addDay()->addHours(2),
            ]),
        ];

        return [
            'request' => $this->buildRequestRecord(
                'PR-20260416-1004',
                $studioContext,
                $studioContext['hr'],
                'pending_owner_approval',
                $baseDate,
                'Improve data resiliency for raw files, edited deliverables, and internal finance archives.',
                $items,
                [
                    'required_date' => $baseDate->copy()->addDays(14)->toDateString(),
                    'is_high_value' => true,
                    'finance_review_note' => 'Budget validated. High-value IT asset requires owner approval.',
                    'finance_reviewed_by' => $studioContext['finance']['id'],
                    'finance_reviewed_at' => $baseDate->copy()->addDay()->addHours(2),
                    'approved_total' => 68950.00,
                    'updated_at' => $baseDate->copy()->addDay()->addHours(2),
                ]
            ),
            'items' => $items,
            'documents' => [
                $this->buildDocument(null, 'request_attachment', 'backup-risk-assessment.pdf', 'procurement/requests/PR-20260416-1004/backup-risk-assessment.pdf', 'application/pdf', 318004, 'Risk assessment for the current storage setup.', [
                    'stage' => 'owner_approval',
                ], $studioContext['hr']['id'], $baseDate->copy()->addMinutes(20)),
            ],
            'audits' => [
                $this->buildAudit($studioContext['hr']['id'], 'created_draft', null, 'draft', 'Draft created by HR for IT resilience upgrade.', $baseDate->copy()->addMinutes(5), []),
                $this->buildAudit($studioContext['hr']['id'], 'submitted', 'draft', 'pending_finance_review', 'Submitted to finance for budget validation.', $baseDate->copy()->addMinutes(22), []),
                $this->buildAudit($studioContext['finance']['id'], 'finance_approve', 'pending_finance_review', 'pending_owner_approval', 'Budget checked and endorsed to owner.', $baseDate->copy()->addDay()->addHours(2), [
                    'approved_total' => 68950.00,
                ]),
            ],
        ];
    }

    /**
     * Create an ordered request payload.
     *
     * @param  array<string, mixed>  $studioContext
     * @return array<string, mixed>
     */
    private function makeOrderedRequest(array $studioContext, Carbon $baseDate): array
    {
        $items = [
            $this->buildItem('LED Light Panel', 'Bi-color LED light panel for product and portrait sessions.', 'equipment', 2, 'unit', 12400.00, 11850.00, 0, [
                'created_at' => $baseDate->copy()->addMinutes(16),
                'updated_at' => $baseDate->copy()->addDay()->addHours(4),
            ]),
            $this->buildItem('Diffuser Cloth Roll', 'Replacement diffuser cloth for key light control.', 'consumable', 3, 'roll', 760.00, 720.00, 0, [
                'created_at' => $baseDate->copy()->addMinutes(16),
                'updated_at' => $baseDate->copy()->addDay()->addHours(4),
            ]),
        ];

        return [
            'request' => $this->buildRequestRecord(
                'PR-20260416-1005',
                $studioContext,
                $studioContext['photographer'],
                'ordered',
                $baseDate,
                'Upgrade lighting consistency for catalog and portrait sessions after client feedback on shadows.',
                $items,
                [
                    'required_date' => $baseDate->copy()->addDays(9)->toDateString(),
                    'finance_review_note' => 'Approved within lighting equipment allocation.',
                    'finance_reviewed_by' => $studioContext['finance']['id'],
                    'finance_reviewed_at' => $baseDate->copy()->addHours(6),
                    'owner_review_note' => 'Proceed with supplier confirmed by finance.',
                    'owner_reviewed_by' => $studioContext['owner']['id'],
                    'owner_reviewed_at' => $baseDate->copy()->addDay()->addHours(2),
                    'approved_total' => 25860.00,
                    'updated_at' => $baseDate->copy()->addDay()->addHours(4),
                ]
            ),
            'items' => $items,
            'purchase_order' => [
                'po_number' => 'PO-20260416-2005',
                'supplier_name' => 'Lumicraft Pro Solutions',
                'supplier_email' => 'lumicraftpro@gmail.com',
                'supplier_contact_number' => '+63 917 555 2005',
                'supplier_address' => '24 Aurora Boulevard, Quezon City, Metro Manila',
                'delivery_address' => $studioContext['studio_name'].' Receiving Area',
                'payment_terms' => '50% downpayment, balance in 15 days after delivery',
                'order_date' => $baseDate->copy()->addDay()->toDateString(),
                'total_amount' => 25860.00,
                'notes' => 'Supplier confirmed seven-day lead time for both lighting items.',
                'ordered_by' => $studioContext['finance']['id'],
                'created_at' => $baseDate->copy()->addDay()->addHours(4),
                'updated_at' => $baseDate->copy()->addDay()->addHours(4),
                'items' => [
                    $this->buildPurchaseOrderItem('LED Light Panel', 2, 'unit', 11850.00, $baseDate->copy()->addDay()->addHours(4)),
                    $this->buildPurchaseOrderItem('Diffuser Cloth Roll', 3, 'roll', 720.00, $baseDate->copy()->addDay()->addHours(4)),
                ],
            ],
            'documents' => [
                $this->buildDocument(null, 'request_attachment', 'lighting-feedback-summary.pdf', 'procurement/requests/PR-20260416-1005/lighting-feedback-summary.pdf', 'application/pdf', 221904, 'Summary of recent client lighting feedback.', [
                    'stage' => 'submission',
                ], $studioContext['photographer']['id'], $baseDate->copy()->addMinutes(12)),
                $this->buildDocument('PO-20260416-2005', 'purchase_order_attachment', 'po-led-light-panel.pdf', 'procurement/purchase-orders/PO-20260416-2005/po-led-light-panel.pdf', 'application/pdf', 188704, 'Signed purchase order copy.', [
                    'stage' => 'ordered',
                ], $studioContext['finance']['id'], $baseDate->copy()->addDay()->addHours(4)),
            ],
            'audits' => [
                $this->buildAudit($studioContext['photographer']['id'], 'created_draft', null, 'draft', 'Draft created to address lighting inconsistency.', $baseDate->copy()->addMinutes(4), []),
                $this->buildAudit($studioContext['photographer']['id'], 'submitted', 'draft', 'pending_finance_review', 'Submitted for finance validation.', $baseDate->copy()->addMinutes(14), []),
                $this->buildAudit($studioContext['finance']['id'], 'finance_approve', 'pending_finance_review', 'pending_owner_approval', 'Budget verified and endorsed to owner.', $baseDate->copy()->addHours(6), []),
                $this->buildAudit($studioContext['owner']['id'], 'owner_approve', 'pending_owner_approval', 'approved', 'Owner approved procurement request.', $baseDate->copy()->addDay()->addHours(2), []),
                $this->buildAudit($studioContext['finance']['id'], 'purchase_order_created', 'approved', 'ordered', 'Purchase order issued to Lumicraft Pro Solutions.', $baseDate->copy()->addDay()->addHours(4), [
                    'po_number' => 'PO-20260416-2005',
                ]),
            ],
        ];
    }

    /**
     * Create a delivered request payload.
     *
     * @param  array<string, mixed>  $studioContext
     * @return array<string, mixed>
     */
    private function makeDeliveredRequest(array $studioContext, Carbon $baseDate): array
    {
        $items = [
            $this->buildItem('Wireless Trigger Set', 'Flash trigger set for multi-light off-camera setups.', 'equipment', 2, 'set', 5600.00, 5450.00, 0, [
                'created_at' => $baseDate->copy()->addMinutes(18),
                'updated_at' => $baseDate->copy()->addDays(4)->addHours(3),
            ]),
            $this->buildItem('AA Battery Charger', 'Smart charger dedicated for trigger battery rotation.', 'consumable', 2, 'unit', 890.00, 850.00, 0, [
                'created_at' => $baseDate->copy()->addMinutes(18),
                'updated_at' => $baseDate->copy()->addDays(4)->addHours(3),
            ]),
        ];

        return [
            'request' => $this->buildRequestRecord(
                'PR-20260416-1006',
                $studioContext,
                $studioContext['hr'],
                'delivered',
                $baseDate,
                'Replace unreliable trigger sets that have caused repeated misfires during event coverage.',
                $items,
                [
                    'required_date' => $baseDate->copy()->addDays(5)->toDateString(),
                    'finance_review_note' => 'Replacement approved to stabilize lighting performance.',
                    'finance_reviewed_by' => $studioContext['finance']['id'],
                    'finance_reviewed_at' => $baseDate->copy()->addHours(4),
                    'owner_review_note' => 'Approved as an operational continuity purchase.',
                    'owner_reviewed_by' => $studioContext['owner']['id'],
                    'owner_reviewed_at' => $baseDate->copy()->addDay()->addHour(),
                    'approved_total' => 12600.00,
                    'delivered_at' => $baseDate->copy()->addDays(4)->addHours(3),
                    'updated_at' => $baseDate->copy()->addDays(4)->addHours(3),
                ]
            ),
            'items' => $items,
            'purchase_order' => [
                'po_number' => 'PO-20260416-2006',
                'supplier_name' => 'Flashline Imaging Supply',
                'supplier_email' => 'flashlinesupply@gmail.com',
                'supplier_contact_number' => '+63 908 555 2006',
                'supplier_address' => '188 Rizal Avenue, Makati City, Metro Manila',
                'delivery_address' => $studioContext['studio_name'].' Dispatch Counter',
                'payment_terms' => 'Net 15 days',
                'order_date' => $baseDate->copy()->addDay()->toDateString(),
                'total_amount' => 12600.00,
                'notes' => 'Supplier promised next-batch delivery aligned with weekend bookings.',
                'ordered_by' => $studioContext['finance']['id'],
                'created_at' => $baseDate->copy()->addDay()->addHours(3),
                'updated_at' => $baseDate->copy()->addDays(4)->addHours(3),
                'items' => [
                    $this->buildPurchaseOrderItem('Wireless Trigger Set', 2, 'set', 5450.00, $baseDate->copy()->addDay()->addHours(3)),
                    $this->buildPurchaseOrderItem('AA Battery Charger', 2, 'unit', 850.00, $baseDate->copy()->addDay()->addHours(3)),
                ],
            ],
            'documents' => [
                $this->buildDocument(null, 'request_attachment', 'trigger-replacement-log.pdf', 'procurement/requests/PR-20260416-1006/trigger-replacement-log.pdf', 'application/pdf', 142508, 'Failure log from recent events.', [
                    'stage' => 'submission',
                ], $studioContext['hr']['id'], $baseDate->copy()->addMinutes(18)),
                $this->buildDocument('PO-20260416-2006', 'purchase_order_attachment', 'po-wireless-trigger.pdf', 'procurement/purchase-orders/PO-20260416-2006/po-wireless-trigger.pdf', 'application/pdf', 168840, 'Issued purchase order.', [
                    'stage' => 'ordered',
                ], $studioContext['finance']['id'], $baseDate->copy()->addDay()->addHours(3)),
                $this->buildDocument('PO-20260416-2006', 'delivery_receipt', 'delivery-receipt-trigger-set.pdf', 'procurement/purchase-orders/PO-20260416-2006/delivery-receipt-trigger-set.pdf', 'application/pdf', 133421, 'Signed delivery receipt uploaded by finance.', [
                    'stage' => 'delivered',
                ], $studioContext['finance']['id'], $baseDate->copy()->addDays(4)->addHours(3)),
            ],
            'audits' => [
                $this->buildAudit($studioContext['hr']['id'], 'created_draft', null, 'draft', 'HR drafted the replacement request.', $baseDate->copy()->addMinutes(6), []),
                $this->buildAudit($studioContext['hr']['id'], 'submitted', 'draft', 'pending_finance_review', 'Submitted to finance.', $baseDate->copy()->addMinutes(18), []),
                $this->buildAudit($studioContext['finance']['id'], 'finance_approve', 'pending_finance_review', 'pending_owner_approval', 'Finance endorsed request to owner.', $baseDate->copy()->addHours(4), []),
                $this->buildAudit($studioContext['owner']['id'], 'owner_approve', 'pending_owner_approval', 'approved', 'Owner approved the purchase.', $baseDate->copy()->addDay()->addHour(), []),
                $this->buildAudit($studioContext['finance']['id'], 'purchase_order_created', 'approved', 'ordered', 'PO issued to Flashline Imaging Supply.', $baseDate->copy()->addDay()->addHours(3), [
                    'po_number' => 'PO-20260416-2006',
                ]),
                $this->buildAudit($studioContext['finance']['id'], 'delivery_recorded', 'ordered', 'delivered', 'Delivery receipt uploaded and awaiting requester confirmation.', $baseDate->copy()->addDays(4)->addHours(3), []),
            ],
        ];
    }

    /**
     * Create a received request payload with CAPEX and OPEX inventory records.
     *
     * @param  array<string, mixed>  $studioContext
     * @return array<string, mixed>
     */
    private function makeReceivedRequest(array $studioContext, Carbon $baseDate): array
    {
        $items = [
            $this->buildItem('Camera Tripod', 'Heavy-load tripod for in-studio video interviews.', 'equipment', 1, 'unit', 7200.00, 6950.00, 1, [
                'condition_notes' => 'Tripod delivered sealed and tested for lock stability.',
                'created_at' => $baseDate->copy()->addMinutes(15),
                'updated_at' => $baseDate->copy()->addDays(4)->addHour(),
            ]),
            $this->buildItem('Gaffer Tape', 'Matte black tape used for cable management on active sets.', 'consumable', 8, 'roll', 210.00, 195.00, 8, [
                'condition_notes' => 'All rolls accounted for and stored in the grip cabinet.',
                'created_at' => $baseDate->copy()->addMinutes(15),
                'updated_at' => $baseDate->copy()->addDays(4)->addHour(),
            ]),
        ];

        return [
            'request' => $this->buildRequestRecord(
                'PR-20260416-1007',
                $studioContext,
                $studioContext['photographer'],
                'received',
                $baseDate,
                'Support safer interview and livestream setups with stable support equipment and cable control.',
                $items,
                [
                    'required_date' => $baseDate->copy()->addDays(6)->toDateString(),
                    'finance_review_note' => 'Approved under livestream setup improvement budget.',
                    'finance_reviewed_by' => $studioContext['finance']['id'],
                    'finance_reviewed_at' => $baseDate->copy()->addHours(4),
                    'owner_review_note' => 'Owner approved due to repeated tripod rental costs.',
                    'owner_reviewed_by' => $studioContext['owner']['id'],
                    'owner_reviewed_at' => $baseDate->copy()->addDay()->addHours(2),
                    'approved_total' => 8510.00,
                    'delivered_at' => $baseDate->copy()->addDays(3)->addHours(2),
                    'receipt_confirmed_by' => $studioContext['photographer']['id'],
                    'receipt_confirmed_at' => $baseDate->copy()->addDays(4)->addHour(),
                    'updated_at' => $baseDate->copy()->addDays(4)->addHour(),
                ]
            ),
            'items' => $items,
            'purchase_order' => [
                'po_number' => 'PO-20260416-2007',
                'supplier_name' => 'RigWorks Camera Store',
                'supplier_email' => 'rigworkssupply@gmail.com',
                'supplier_contact_number' => '+63 905 555 2007',
                'supplier_address' => '32 Shaw Boulevard, Mandaluyong City, Metro Manila',
                'delivery_address' => $studioContext['studio_name'].' Equipment Room',
                'payment_terms' => 'Net 30 days',
                'order_date' => $baseDate->copy()->addDay()->toDateString(),
                'total_amount' => 8510.00,
                'notes' => 'Tripod and grip accessories were bundled under a studio operations promo.',
                'ordered_by' => $studioContext['finance']['id'],
                'created_at' => $baseDate->copy()->addDay()->addHours(4),
                'updated_at' => $baseDate->copy()->addDays(4)->addHour(),
                'items' => [
                    $this->buildPurchaseOrderItem('Camera Tripod', 1, 'unit', 6950.00, $baseDate->copy()->addDay()->addHours(4)),
                    $this->buildPurchaseOrderItem('Gaffer Tape', 8, 'roll', 195.00, $baseDate->copy()->addDay()->addHours(4)),
                ],
            ],
            'documents' => [
                $this->buildDocument(null, 'request_attachment', 'livestream-rig-gap-analysis.pdf', 'procurement/requests/PR-20260416-1007/livestream-rig-gap-analysis.pdf', 'application/pdf', 227901, 'Operational gap analysis from livestream sessions.', [
                    'stage' => 'submission',
                ], $studioContext['photographer']['id'], $baseDate->copy()->addMinutes(14)),
                $this->buildDocument('PO-20260416-2007', 'purchase_order_attachment', 'po-tripod-and-tape.pdf', 'procurement/purchase-orders/PO-20260416-2007/po-tripod-and-tape.pdf', 'application/pdf', 184118, 'Approved purchase order copy.', [
                    'stage' => 'ordered',
                ], $studioContext['finance']['id'], $baseDate->copy()->addDay()->addHours(4)),
                $this->buildDocument('PO-20260416-2007', 'delivery_receipt', 'delivery-receipt-tripod.pdf', 'procurement/purchase-orders/PO-20260416-2007/delivery-receipt-tripod.pdf', 'application/pdf', 119884, 'Signed delivery receipt from supplier.', [
                    'stage' => 'delivered',
                ], $studioContext['finance']['id'], $baseDate->copy()->addDays(3)->addHours(2)),
            ],
            'audits' => [
                $this->buildAudit($studioContext['photographer']['id'], 'created_draft', null, 'draft', 'Photographer created the request.', $baseDate->copy()->addMinutes(5), []),
                $this->buildAudit($studioContext['photographer']['id'], 'submitted', 'draft', 'pending_finance_review', 'Submitted for review.', $baseDate->copy()->addMinutes(15), []),
                $this->buildAudit($studioContext['finance']['id'], 'finance_approve', 'pending_finance_review', 'pending_owner_approval', 'Finance approved operational spend.', $baseDate->copy()->addHours(4), []),
                $this->buildAudit($studioContext['owner']['id'], 'owner_approve', 'pending_owner_approval', 'approved', 'Owner approved the procurement request.', $baseDate->copy()->addDay()->addHours(2), []),
                $this->buildAudit($studioContext['finance']['id'], 'purchase_order_created', 'approved', 'ordered', 'Purchase order created and sent to RigWorks Camera Store.', $baseDate->copy()->addDay()->addHours(4), [
                    'po_number' => 'PO-20260416-2007',
                ]),
                $this->buildAudit($studioContext['finance']['id'], 'delivery_recorded', 'ordered', 'delivered', 'Delivery was recorded by finance.', $baseDate->copy()->addDays(3)->addHours(2), []),
                $this->buildAudit($studioContext['photographer']['id'], 'receipt_confirmed', 'delivered', 'received', 'Requester confirmed all items were received in good condition.', $baseDate->copy()->addDays(4)->addHour(), []),
            ],
            'assets' => [
                [
                    'asset_name' => 'Camera Tripod',
                    'serial_number' => 'TRIPOD-RW-260416-01',
                    'warranty_expires_at' => $baseDate->copy()->addYear()->addDays(4)->toDateString(),
                    'acquisition_cost' => 6950.00,
                    'location' => $studioContext['studio_name'].' Interview Corner',
                    'recorded_by' => $studioContext['photographer']['id'],
                    'status' => 'active',
                    'created_at' => $baseDate->copy()->addDays(4)->addHour(),
                    'updated_at' => $baseDate->copy()->addDays(4)->addHour(),
                ],
            ],
            'inventory_stocks' => [
                [
                    'studio_id' => $studioContext['studio_id'],
                    'item_name' => 'Gaffer Tape',
                    'normalized_item_name' => $this->normalizeItemName('Gaffer Tape'),
                    'description' => 'Seeded procurement stock row for cable management materials.',
                    'unit_of_measure' => 'roll',
                    'stock_quantity' => 8,
                    'reorder_threshold' => 3,
                    'last_recorded_cost' => 195.00,
                    'last_received_at' => $baseDate->copy()->addDays(4)->addHour(),
                    'created_by' => $studioContext['photographer']['id'],
                    'updated_by' => $studioContext['photographer']['id'],
                    'created_at' => $baseDate->copy()->addDays(4)->addHour(),
                    'updated_at' => $baseDate->copy()->addDays(4)->addHour(),
                ],
            ],
        ];
    }

    /**
     * Create a completed request payload with final payment documents.
     *
     * @param  array<string, mixed>  $studioContext
     * @return array<string, mixed>
     */
    private function makeCompletedRequest(array $studioContext, Carbon $baseDate): array
    {
        $items = [
            $this->buildItem('Mirrorless Camera Body', 'Primary studio camera body for high-volume client shoots.', 'equipment', 1, 'unit', 92500.00, 89900.00, 1, [
                'condition_notes' => 'Camera body delivered sealed with complete accessories.',
                'created_at' => $baseDate->copy()->addMinutes(25),
                'updated_at' => $baseDate->copy()->addDays(7)->addHours(3),
            ]),
            $this->buildItem('SD Card 128GB', 'High-speed storage cards for same-day event offload.', 'consumable', 6, 'piece', 1450.00, 1325.00, 6, [
                'condition_notes' => 'Cards tested and labeled before storage.',
                'created_at' => $baseDate->copy()->addMinutes(25),
                'updated_at' => $baseDate->copy()->addDays(7)->addHours(3),
            ]),
        ];

        return [
            'request' => $this->buildRequestRecord(
                'PR-20260416-1008',
                $studioContext,
                $studioContext['hr'],
                'completed',
                $baseDate,
                'Replace aging camera body to improve reliability during peak season bookings and reduce emergency rentals.',
                $items,
                [
                    'required_date' => $baseDate->copy()->addDays(8)->toDateString(),
                    'is_urgent' => true,
                    'is_high_value' => true,
                    'finance_review_note' => 'Validated against rental spend and maintenance history.',
                    'finance_reviewed_by' => $studioContext['finance']['id'],
                    'finance_reviewed_at' => $baseDate->copy()->addHours(5),
                    'owner_review_note' => 'Approved due to ROI and current booking volume.',
                    'owner_reviewed_by' => $studioContext['owner']['id'],
                    'owner_reviewed_at' => $baseDate->copy()->addDay()->addHours(2),
                    'approved_total' => 97850.00,
                    'invoice_reference' => 'INV-PS-2026-0416-88',
                    'invoice_amount' => 97850.00,
                    'invoice_date' => $baseDate->copy()->addDays(5)->toDateString(),
                    'payment_reference' => 'PAY-20260416-8801',
                    'payment_note' => 'Final payment settled through bank transfer after three-way match validation.',
                    'delivered_at' => $baseDate->copy()->addDays(4)->addHours(3),
                    'receipt_confirmed_by' => $studioContext['hr']['id'],
                    'receipt_confirmed_at' => $baseDate->copy()->addDays(5)->addHours(2),
                    'payment_processed_at' => $baseDate->copy()->addDays(6)->addHours(4),
                    'completed_at' => $baseDate->copy()->addDays(7)->addHours(3),
                    'updated_at' => $baseDate->copy()->addDays(7)->addHours(3),
                ]
            ),
            'items' => $items,
            'purchase_order' => [
                'po_number' => 'PO-20260416-2008',
                'supplier_name' => 'PixelForge Equipment Hub',
                'supplier_email' => 'pixelforgesupply@gmail.com',
                'supplier_contact_number' => '+63 917 555 2008',
                'supplier_address' => '402 Ortigas Avenue, Pasig City, Metro Manila',
                'delivery_address' => $studioContext['studio_name'].' Production Room',
                'payment_terms' => '30% downpayment, 70% after delivery',
                'order_date' => $baseDate->copy()->addDay()->toDateString(),
                'total_amount' => 97850.00,
                'notes' => 'Supplier bundled original battery kit and priority after-sales calibration.',
                'ordered_by' => $studioContext['finance']['id'],
                'created_at' => $baseDate->copy()->addDay()->addHours(4),
                'updated_at' => $baseDate->copy()->addDays(7)->addHours(3),
                'items' => [
                    $this->buildPurchaseOrderItem('Mirrorless Camera Body', 1, 'unit', 89900.00, $baseDate->copy()->addDay()->addHours(4)),
                    $this->buildPurchaseOrderItem('SD Card 128GB', 6, 'piece', 1325.00, $baseDate->copy()->addDay()->addHours(4)),
                ],
            ],
            'documents' => [
                $this->buildDocument(null, 'request_attachment', 'camera-downtime-analysis.pdf', 'procurement/requests/PR-20260416-1008/camera-downtime-analysis.pdf', 'application/pdf', 354882, 'Downtime and rental cost analysis for the current camera body.', [
                    'stage' => 'submission',
                ], $studioContext['hr']['id'], $baseDate->copy()->addMinutes(25)),
                $this->buildDocument('PO-20260416-2008', 'purchase_order_attachment', 'po-mirrorless-camera.pdf', 'procurement/purchase-orders/PO-20260416-2008/po-mirrorless-camera.pdf', 'application/pdf', 228764, 'Final signed purchase order.', [
                    'stage' => 'ordered',
                ], $studioContext['finance']['id'], $baseDate->copy()->addDay()->addHours(4)),
                $this->buildDocument('PO-20260416-2008', 'delivery_receipt', 'delivery-receipt-camera-body.pdf', 'procurement/purchase-orders/PO-20260416-2008/delivery-receipt-camera-body.pdf', 'application/pdf', 142228, 'Supplier delivery receipt for camera body and SD cards.', [
                    'stage' => 'delivered',
                ], $studioContext['finance']['id'], $baseDate->copy()->addDays(4)->addHours(3)),
                $this->buildDocument('PO-20260416-2008', 'supplier_invoice', 'supplier-invoice-camera-body.pdf', 'procurement/purchase-orders/PO-20260416-2008/supplier-invoice-camera-body.pdf', 'application/pdf', 166020, 'Supplier invoice matching PO total.', [
                    'stage' => 'payment_processing',
                ], $studioContext['finance']['id'], $baseDate->copy()->addDays(6)->addHours(4)),
                $this->buildDocument('PO-20260416-2008', 'payment_proof', 'bank-transfer-proof-camera-body.pdf', 'procurement/purchase-orders/PO-20260416-2008/bank-transfer-proof-camera-body.pdf', 'application/pdf', 119220, 'Finance bank transfer proof.', [
                    'stage' => 'payment_processing',
                ], $studioContext['finance']['id'], $baseDate->copy()->addDays(6)->addHours(4)->addMinutes(10)),
            ],
            'audits' => [
                $this->buildAudit($studioContext['hr']['id'], 'created_draft', null, 'draft', 'HR created the replacement request.', $baseDate->copy()->addMinutes(8), []),
                $this->buildAudit($studioContext['hr']['id'], 'submitted', 'draft', 'pending_finance_review', 'Submitted to finance with downtime analysis.', $baseDate->copy()->addMinutes(25), []),
                $this->buildAudit($studioContext['finance']['id'], 'finance_approve', 'pending_finance_review', 'pending_owner_approval', 'Finance approved based on ROI and service load.', $baseDate->copy()->addHours(5), [
                    'approved_total' => 97850.00,
                ]),
                $this->buildAudit($studioContext['owner']['id'], 'owner_approve', 'pending_owner_approval', 'approved', 'Owner approved major equipment replacement.', $baseDate->copy()->addDay()->addHours(2), []),
                $this->buildAudit($studioContext['finance']['id'], 'purchase_order_created', 'approved', 'ordered', 'PO released to PixelForge Equipment Hub.', $baseDate->copy()->addDay()->addHours(4), [
                    'po_number' => 'PO-20260416-2008',
                ]),
                $this->buildAudit($studioContext['finance']['id'], 'delivery_recorded', 'ordered', 'delivered', 'Items were delivered and receipt was uploaded.', $baseDate->copy()->addDays(4)->addHours(3), []),
                $this->buildAudit($studioContext['hr']['id'], 'receipt_confirmed', 'delivered', 'received', 'Requester confirmed receipt and inventory posting requirements.', $baseDate->copy()->addDays(5)->addHours(2), []),
                $this->buildAudit($studioContext['finance']['id'], 'payment_processing_started', 'received', 'payment_processing', 'Invoice and payment proof uploaded after three-way match.', $baseDate->copy()->addDays(6)->addHours(4), [
                    'invoice_reference' => 'INV-PS-2026-0416-88',
                ]),
                $this->buildAudit($studioContext['finance']['id'], 'completed', 'payment_processing', 'completed', 'Procurement cycle completed successfully.', $baseDate->copy()->addDays(7)->addHours(3), []),
            ],
            'assets' => [
                [
                    'asset_name' => 'Mirrorless Camera Body',
                    'serial_number' => 'CAM-PF-260416-08',
                    'warranty_expires_at' => $baseDate->copy()->addYears(2)->addDays(7)->toDateString(),
                    'acquisition_cost' => 89900.00,
                    'location' => $studioContext['studio_name'].' Main Camera Locker',
                    'recorded_by' => $studioContext['hr']['id'],
                    'status' => 'active',
                    'created_at' => $baseDate->copy()->addDays(5)->addHours(2),
                    'updated_at' => $baseDate->copy()->addDays(5)->addHours(2),
                ],
            ],
            'inventory_stocks' => [
                [
                    'studio_id' => $studioContext['studio_id'],
                    'item_name' => 'SD Card 128GB',
                    'normalized_item_name' => $this->normalizeItemName('SD Card 128GB'),
                    'description' => 'Seeded procurement stock row for production media storage.',
                    'unit_of_measure' => 'piece',
                    'stock_quantity' => 6,
                    'reorder_threshold' => 2,
                    'last_recorded_cost' => 1325.00,
                    'last_received_at' => $baseDate->copy()->addDays(5)->addHours(2),
                    'created_by' => $studioContext['hr']['id'],
                    'updated_by' => $studioContext['finance']['id'],
                    'created_at' => $baseDate->copy()->addDays(5)->addHours(2),
                    'updated_at' => $baseDate->copy()->addDays(6)->addHours(4),
                ],
            ],
        ];
    }

    /**
     * Remove previously seeded procurement dataset.
     *
     * @param  array<int, string>  $requestReferences
     */
    private function purgeExistingSeedData(array $requestReferences): void
    {
        DB::table('tbl_procurement_inventory_stocks')
            ->where('description', 'like', 'Seeded procurement stock row%')
            ->delete();

        DB::table('tbl_procurement_requests')
            ->whereIn('request_reference', $requestReferences)
            ->delete();
    }

    /**
     * Persist one procurement workflow payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function seedProcurementRequest(array $payload): void
    {
        $requestData = $payload['request'];
        $items = $payload['items'] ?? [];
        $documents = $payload['documents'] ?? [];
        $audits = $payload['audits'] ?? [];
        $assets = $payload['assets'] ?? [];
        $inventoryStocks = $payload['inventory_stocks'] ?? [];
        $purchaseOrder = $payload['purchase_order'] ?? null;

        $requestId = DB::table('tbl_procurement_requests')->insertGetId($requestData);
        $itemIdMap = [];

        foreach ($items as $item) {
            $item['procurement_request_id'] = $requestId;
            $itemId = DB::table('tbl_procurement_request_items')->insertGetId($item);
            $itemIdMap[$item['item_name']] = $itemId;
        }

        $purchaseOrderId = null;

        if ($purchaseOrder) {
            $poItems = $purchaseOrder['items'] ?? [];
            unset($purchaseOrder['items']);
            $purchaseOrder['procurement_request_id'] = $requestId;

            $purchaseOrderId = DB::table('tbl_procurement_purchase_orders')->insertGetId($purchaseOrder);

            foreach ($poItems as $poItem) {
                DB::table('tbl_procurement_purchase_order_items')->insert([
                    'purchase_order_id' => $purchaseOrderId,
                    'procurement_request_item_id' => $itemIdMap[$poItem['item_name']],
                    'item_name' => $poItem['item_name'],
                    'quantity' => $poItem['quantity'],
                    'unit_of_measure' => $poItem['unit_of_measure'],
                    'unit_price' => $poItem['unit_price'],
                    'total_price' => $poItem['total_price'],
                    'created_at' => $poItem['created_at'],
                    'updated_at' => $poItem['updated_at'],
                ]);
            }
        }

        foreach ($documents as $document) {
            $purchaseOrderReference = $document['purchase_order_reference'] ?? null;
            unset($document['purchase_order_reference']);

            $document['procurement_request_id'] = $requestId;
            $document['purchase_order_id'] = $purchaseOrderReference && $purchaseOrderId ? $purchaseOrderId : null;

            DB::table('tbl_procurement_documents')->insert($document);
        }

        foreach ($audits as $audit) {
            $audit['procurement_request_id'] = $requestId;
            DB::table('tbl_procurement_audit_trails')->insert($audit);
        }

        foreach ($assets as $asset) {
            $assetName = $asset['asset_name'];

            DB::table('tbl_procurement_assets')->insert([
                'procurement_request_id' => $requestId,
                'procurement_request_item_id' => $itemIdMap[$assetName],
                'studio_id' => $requestData['studio_id'],
                'recorded_by' => $asset['recorded_by'],
                'asset_name' => $assetName,
                'serial_number' => $asset['serial_number'],
                'warranty_expires_at' => $asset['warranty_expires_at'],
                'acquisition_cost' => $asset['acquisition_cost'],
                'location' => $asset['location'],
                'status' => $asset['status'],
                'created_at' => $asset['created_at'],
                'updated_at' => $asset['updated_at'],
            ]);
        }

        foreach ($inventoryStocks as $inventoryStock) {
            $inventoryStock['procurement_request_id'] = $requestId;
            $inventoryStock['procurement_request_item_id'] = $itemIdMap[$inventoryStock['item_name']];

            DB::table('tbl_procurement_inventory_stocks')->insert($inventoryStock);
        }
    }

    /**
     * Build a procurement request row.
     *
     * @param  array<string, mixed>  $studioContext
     * @param  array<string, mixed>  $requester
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function buildRequestRecord(
        string $requestReference,
        array $studioContext,
        array $requester,
        string $status,
        Carbon $createdAt,
        string $purpose,
        array $items,
        array $overrides = []
    ): array {
        $estimatedTotal = collect($items)->sum('estimated_total_cost');
        $approvedTotal = collect($items)->sum(function (array $item) {
            return $item['approved_total_cost'] ?? 0;
        });

        return array_merge([
            'request_reference' => $requestReference,
            'studio_id' => $studioContext['studio_id'],
            'requester_id' => $requester['id'],
            'requester_role' => $requester['role'],
            'status' => $status,
            'is_urgent' => false,
            'is_high_value' => $estimatedTotal >= 50000,
            'required_date' => $createdAt->copy()->addDays(7)->toDateString(),
            'purpose' => $purpose,
            'inventory_bypass_reason' => null,
            'finance_review_note' => null,
            'owner_review_note' => null,
            'estimated_total' => $estimatedTotal,
            'approved_total' => $approvedTotal,
            'invoice_reference' => null,
            'invoice_amount' => null,
            'invoice_date' => null,
            'payment_reference' => null,
            'payment_note' => null,
            'finance_reviewed_by' => null,
            'finance_reviewed_at' => null,
            'owner_reviewed_by' => null,
            'owner_reviewed_at' => null,
            'receipt_confirmed_by' => null,
            'delivered_at' => null,
            'receipt_confirmed_at' => null,
            'payment_processed_at' => null,
            'completed_at' => null,
            'escalated_at' => null,
            'cancelled_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $overrides['updated_at'] ?? $createdAt->copy()->addHour(),
        ], $overrides);
    }

    /**
     * Build a procurement item row.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function buildItem(
        string $itemName,
        string $description,
        string $category,
        float $quantity,
        string $unitOfMeasure,
        float $estimatedUnitCost,
        ?float $approvedUnitCost,
        float $receivedQuantity,
        array $overrides = []
    ): array {
        $approvedTotalCost = $approvedUnitCost !== null ? round($approvedUnitCost * $quantity, 2) : null;
        $createdAt = $overrides['created_at'] ?? now();
        $updatedAt = $overrides['updated_at'] ?? $createdAt;

        return array_merge([
            'item_name' => $itemName,
            'normalized_item_name' => $this->normalizeItemName($itemName),
            'description' => $description,
            'category' => $category,
            'expense_type' => $category === 'equipment' ? 'capex' : 'opex',
            'quantity' => $quantity,
            'unit_of_measure' => $unitOfMeasure,
            'estimated_unit_cost' => $estimatedUnitCost,
            'estimated_total_cost' => round($estimatedUnitCost * $quantity, 2),
            'approved_unit_cost' => $approvedUnitCost,
            'approved_total_cost' => $approvedTotalCost,
            'received_quantity' => $receivedQuantity,
            'condition_notes' => null,
            'preferred_supplier' => null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ], $overrides);
    }

    /**
     * Build a purchase order item row.
     *
     * @return array<string, mixed>
     */
    private function buildPurchaseOrderItem(string $itemName, float $quantity, string $unitOfMeasure, float $unitPrice, Carbon $timestamp): array
    {
        return [
            'item_name' => $itemName,
            'quantity' => $quantity,
            'unit_of_measure' => $unitOfMeasure,
            'unit_price' => $unitPrice,
            'total_price' => round($quantity * $unitPrice, 2),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * Build a procurement document row.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function buildDocument(
        ?string $purchaseOrderReference,
        string $documentType,
        string $fileName,
        string $filePath,
        string $mimeType,
        int $fileSize,
        string $notes,
        array $metadata,
        int $uploadedBy,
        Carbon $timestamp
    ): array {
        return [
            'purchase_order_reference' => $purchaseOrderReference,
            'uploaded_by' => $uploadedBy,
            'document_type' => $documentType,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'notes' => $notes,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * Build a procurement audit trail row.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function buildAudit(
        int $actorId,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        string $note,
        Carbon $timestamp,
        array $metadata
    ): array {
        return [
            'actor_id' => $actorId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * Normalize an item name for duplicate checks and stock rows.
     */
    private function normalizeItemName(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
