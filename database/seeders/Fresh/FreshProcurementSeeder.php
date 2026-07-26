<?php

namespace Database\Seeders\Fresh;

use App\Models\Procurement\ProcurementDefectReturnModel;
use App\Models\Procurement\ProcurementRequestModel;
use Database\Seeders\Fresh\Concerns\FreshSeedSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Procurement workflow rows and the AI assistant's per-owner configuration.
 *
 * tbl_procurement_documents is deliberately never written: file_name and
 * file_path are NOT NULL, so a row cannot exist without a media reference.
 * Request states are therefore chosen to be ones that do not depend on an
 * attached invoice or receipt file.
 *
 * Chatbot intents are studio knowledge facts, not canned replies — the model
 * consumes them as untrusted context, and the security rules live in code.
 */
class FreshProcurementSeeder
{
    use FreshSeedSupport;

    public function __construct(private ?Command $command = null) {}

    /**
     * @param  array<string, mixed>  $graph
     */
    public function run(array $graph): void
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $studios = $graph['studios'];

        $this->createProcurement($studios, $today, $now);
        $this->createChatbot($studios, $graph['clients'], $now);

        $this->command?->info('Seeded procurement workflow and chatbot configuration for '.count($studios).' studios.');
    }

    /**
     * Three requests per studio at different points in the workflow.
     *
     * @param  array<int, array<string, mixed>>  $studios
     */
    private function createProcurement(array $studios, Carbon $today, Carbon $now): void
    {
        $catalogue = [
            ['name' => 'Prime lens 35mm', 'category' => 'equipment', 'expense' => 'capex', 'unit' => 'piece', 'cost' => 24500.00],
            ['name' => 'Studio strobe head', 'category' => 'equipment', 'expense' => 'capex', 'unit' => 'piece', 'cost' => 18900.00],
            ['name' => 'Seamless backdrop roll', 'category' => 'consumable', 'expense' => 'opex', 'unit' => 'roll', 'cost' => 2450.00],
            ['name' => 'Memory card 128GB', 'category' => 'consumable', 'expense' => 'opex', 'unit' => 'piece', 'cost' => 3200.00],
            ['name' => 'Lens cleaning kit', 'category' => 'consumable', 'expense' => 'opex', 'unit' => 'kit', 'cost' => 850.00],
            ['name' => 'Light stand', 'category' => 'equipment', 'expense' => 'capex', 'unit' => 'piece', 'cost' => 4300.00],
        ];

        $flows = [
            ['status' => ProcurementRequestModel::STATUS_COMPLETED, 'purchase_order' => true, 'asset' => true],
            ['status' => ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL, 'purchase_order' => false, 'asset' => false],
            ['status' => ProcurementRequestModel::STATUS_ORDERED, 'purchase_order' => true, 'asset' => false],
        ];

        $requestSequence = 0;
        $poSequence = 0;
        $auditRows = [];
        $stockRows = [];
        $assetRows = [];
        $defectRows = [];

        foreach ($studios as $studio) {
            $requester = $studio['hr'][1];
            $financeReviewer = $studio['finance'][0];

            foreach ($flows as $flowIndex => $flow) {
                $requestSequence++;
                $reference = sprintf('FS-PR-%05d', $requestSequence);
                $completed = $flow['status'] === ProcurementRequestModel::STATUS_COMPLETED;
                $reviewed = $flow['status'] !== ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL;

                $items = [
                    $catalogue[($studio['index'] * 2 + $flowIndex) % count($catalogue)],
                    $catalogue[($studio['index'] * 2 + $flowIndex + 3) % count($catalogue)],
                ];
                $estimatedTotal = array_sum(array_map(
                    static fn (array $item): float => $item['cost'] * 2,
                    $items
                ));

                $requestId = (int) DB::table('tbl_procurement_requests')->insertGetId([
                    'request_reference' => $reference,
                    'studio_id' => $studio['id'],
                    'requester_id' => $requester['id'],
                    'requester_role' => $requester['scoped_role'],
                    'status' => $flow['status'],
                    'is_urgent' => $flowIndex === 2,
                    'is_high_value' => $estimatedTotal > 40000,
                    'required_date' => $today->copy()->addDays(14 + $flowIndex * 7)->toDateString(),
                    'purpose' => 'Replenishment for the studio equipment and consumable inventory.',
                    'finance_review_note' => 'Costs verified against the current supplier quotations.',
                    'owner_review_note' => $reviewed ? 'Approved within the quarterly equipment budget.' : null,
                    'estimated_total' => $estimatedTotal,
                    'approved_total' => $reviewed ? $estimatedTotal : 0,
                    // invoice_reference and payment_reference are text
                    // identifiers, not file paths; the invoice document itself
                    // would live in tbl_procurement_documents, which stays empty.
                    'invoice_reference' => $completed ? 'INV-'.$reference : null,
                    'invoice_amount' => $completed ? $estimatedTotal : null,
                    'invoice_date' => $completed ? $today->copy()->subDays(9)->toDateString() : null,
                    'payment_reference' => $completed ? 'PAY-'.$reference : null,
                    'payment_note' => $completed ? 'Settled by bank transfer.' : null,
                    'finance_reviewed_by' => $financeReviewer['id'],
                    'finance_reviewed_at' => $now,
                    'owner_reviewed_by' => $reviewed ? $studio['owner_id'] : null,
                    'owner_reviewed_at' => $reviewed ? $now : null,
                    'receipt_confirmed_by' => $completed ? $requester['id'] : null,
                    'delivered_at' => $completed ? $now : null,
                    'receipt_confirmed_at' => $completed ? $now : null,
                    'payment_processed_at' => $completed ? $now : null,
                    'completed_at' => $completed ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $itemIds = [];

                foreach ($items as $item) {
                    $itemIds[] = [
                        'id' => (int) DB::table('tbl_procurement_request_items')->insertGetId([
                            'procurement_request_id' => $requestId,
                            'item_name' => $item['name'],
                            'normalized_item_name' => Str::slug($item['name'], '_'),
                            'description' => 'Standard studio issue, replaced on the usual maintenance cycle.',
                            'category' => $item['category'],
                            'expense_type' => $item['expense'],
                            'quantity' => 2,
                            'unit_of_measure' => $item['unit'],
                            'estimated_unit_cost' => $item['cost'],
                            'estimated_total_cost' => $item['cost'] * 2,
                            'approved_unit_cost' => $reviewed ? $item['cost'] : null,
                            'approved_total_cost' => $reviewed ? $item['cost'] * 2 : null,
                            'received_quantity' => $completed ? 2 : 0,
                            'preferred_supplier' => 'Cavite Imaging Supply',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]),
                        'item' => $item,
                    ];
                }

                foreach ([
                    ['action' => 'submitted', 'from' => ProcurementRequestModel::STATUS_DRAFT, 'to' => ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW, 'actor' => $requester['id']],
                    ['action' => 'finance_reviewed', 'from' => ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW, 'to' => ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL, 'actor' => $financeReviewer['id']],
                    ['action' => 'status_advanced', 'from' => ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL, 'to' => $flow['status'], 'actor' => $studio['owner_id']],
                ] as $trail) {
                    $auditRows[] = [
                        'procurement_request_id' => $requestId,
                        'actor_id' => $trail['actor'],
                        'action' => $trail['action'],
                        'from_status' => $trail['from'],
                        'to_status' => $trail['to'],
                        'note' => 'Recorded by the fresh seed workflow.',
                        'metadata' => json_encode(['request_id' => $requestId], JSON_THROW_ON_ERROR),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($flow['purchase_order']) {
                    $poSequence++;
                    $poId = (int) DB::table('tbl_procurement_purchase_orders')->insertGetId([
                        'procurement_request_id' => $requestId,
                        'po_number' => sprintf('FS-PO-%05d', $poSequence),
                        'supplier_name' => 'Cavite Imaging Supply',
                        'supplier_email' => 'caviteimagingsupply@gmail.com',
                        'supplier_contact_number' => '0918'.str_pad((string) (4300000 + $poSequence), 7, '0', STR_PAD_LEFT),
                        'supplier_address' => 'Aguinaldo Highway, Imus, Cavite',
                        'delivery_address' => sprintf('%s, %s, Cavite', $studio['barangay'], $studio['municipality']),
                        'payment_terms' => '30 days from invoice date',
                        'order_date' => $today->copy()->subDays(12)->toDateString(),
                        'total_amount' => $estimatedTotal,
                        'notes' => 'Consolidated order for the approved request items.',
                        'ordered_by' => $financeReviewer['id'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    foreach ($itemIds as $entry) {
                        DB::table('tbl_procurement_purchase_order_items')->insert([
                            'purchase_order_id' => $poId,
                            'procurement_request_item_id' => $entry['id'],
                            'item_name' => $entry['item']['name'],
                            'quantity' => 2,
                            'unit_of_measure' => $entry['item']['unit'],
                            'unit_price' => $entry['item']['cost'],
                            'total_price' => $entry['item']['cost'] * 2,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }

                if ($flow['asset']) {
                    foreach ($itemIds as $assetIndex => $entry) {
                        if ($entry['item']['category'] !== 'equipment') {
                            continue;
                        }

                        $assetRows[] = [
                            'procurement_request_id' => $requestId,
                            'procurement_request_item_id' => $entry['id'],
                            'studio_id' => $studio['id'],
                            'recorded_by' => $requester['id'],
                            'asset_name' => $entry['item']['name'],
                            'serial_number' => sprintf('FS-SN-%05d-%d', $requestSequence, $assetIndex),
                            'warranty_expires_at' => $today->copy()->addYear()->toDateString(),
                            'acquisition_cost' => $entry['item']['cost'],
                            // A plain text label, not a tbl_locations reference.
                            'location' => sprintf('%s equipment room', $studio['name']),
                            'status' => 'active',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    $defectRows[] = [
                        'procurement_request_id' => $requestId,
                        'procurement_request_item_id' => $itemIds[0]['id'],
                        'reported_by' => $requester['id'],
                        'processed_by' => $financeReviewer['id'],
                        'reported_quantity' => 1,
                        'reason_code' => 'damaged_on_arrival',
                        'requester_note' => 'One unit arrived with a cracked housing.',
                        'finance_note' => 'Replacement requested from the supplier.',
                        'status' => ProcurementDefectReturnModel::STATUS_RESOLVED,
                        'reported_at' => $now,
                        'processed_at' => $now,
                        'resolved_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Standing inventory, independent of any single request.
            foreach (array_slice($catalogue, 0, 4) as $stockIndex => $item) {
                $stockRows[] = [
                    'studio_id' => $studio['id'],
                    'procurement_request_id' => null,
                    'procurement_request_item_id' => null,
                    'created_by' => $studio['owner_id'],
                    'updated_by' => $requester['id'],
                    'item_name' => $item['name'],
                    'normalized_item_name' => Str::slug($item['name'], '_'),
                    'description' => 'Standing stock tracked by the studio inventory.',
                    'unit_of_measure' => $item['unit'],
                    'stock_quantity' => 4 + $stockIndex * 2,
                    'reorder_threshold' => 2,
                    'last_recorded_cost' => $item['cost'],
                    'last_received_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('tbl_procurement_audit_trails')->insert($auditRows);
        DB::table('tbl_procurement_assets')->insert($assetRows);
        DB::table('tbl_procurement_inventory_stocks')->insert($stockRows);
        DB::table('tbl_procurement_defect_returns')->insert($defectRows);
    }

    /**
     * One assistant configuration per studio owner, with studio facts as
     * intents and a short sample conversation.
     *
     * @param  array<int, array<string, mixed>>  $studios
     * @param  array<int, int>  $clients
     */
    private function createChatbot(array $studios, array $clients, Carbon $now): void
    {
        $intentRows = [];
        $conversationRows = [];

        foreach ($studios as $studio) {
            $configId = (int) DB::table('tbl_chatbot_configs')->insertGetId([
                'owner_id' => $studio['owner_id'],
                'config_name' => $studio['name'].' Assistant',
                'welcome_message' => sprintf('Hi, this is the %s booking assistant. Ask about packages, schedules, or coverage areas.', $studio['name']),
                'is_active' => true,
                'bot_name' => $studio['name'].' Assistant',
                // The avatar is a media reference; the assistant runs without one.
                'bot_avatar' => null,
                'settings' => json_encode(['tone' => 'concise', 'locale' => 'en-PH'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $facts = [
                ['operating_hours', sprintf('%s operates %s from %s to %s.', $studio['name'], implode(', ', $studio['operating_days']), $studio['start_time'], $studio['end_time'])],
                ['location', sprintf('The studio is in %s, %s, Cavite.', $studio['barangay'], $studio['municipality'])],
                ['categories', sprintf('Coverage focuses on %s.', implode(', ', array_column($studio['categories'], 'name')))],
                ['packages', sprintf('There are %d packages across three tiers: Basic, Essentials, and Premium.', count($studio['packages']))],
                ['team', sprintf('The studio has %d photographers on its roster.', count($studio['photographers']))],
            ];

            foreach ($facts as $priority => [$name, $text]) {
                $intentRows[] = [
                    'config_id' => $configId,
                    'intent_name' => $name,
                    'response_text' => $text,
                    'priority' => 10 - $priority,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            for ($c = 0; $c < 2; $c++) {
                $conversationRows[] = [
                    'config_id' => $configId,
                    'owner_id' => $studio['owner_id'],
                    'client_id' => $clients[($studio['index'] * 2 + $c) % count($clients)],
                ];
            }
        }

        DB::table('tbl_chatbot_intents')->insert($intentRows);

        $messageRows = [];

        foreach ($conversationRows as $index => $conversation) {
            $conversationId = (int) DB::table('tbl_chatbot_conversations')->insertGetId([
                'session_id' => 'fs-chat-'.Str::uuid(),
                'user_id' => $conversation['client_id'],
                'owner_id' => $conversation['owner_id'],
                'config_id' => $conversation['config_id'],
                'status' => $index % 2 === 0 ? 'ended' : 'active',
                'started_at' => $now,
                'ended_at' => $index % 2 === 0 ? $now : null,
                'message_count' => 4,
                'metadata' => json_encode(['channel' => 'web'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ([
                ['user', 'What packages do you offer for a weekend session?'],
                ['bot', 'There are three tiers: Basic, Essentials, and Premium. Basic covers three hours in-studio.'],
                ['user', 'Do you cover locations outside Cavite?'],
                ['bot', 'Yes, coverage extends to Laguna and Metro Manila for the Essentials and Premium tiers.'],
            ] as [$sender, $message]) {
                $messageRows[] = [
                    'conversation_id' => $conversationId,
                    'sender_type' => $sender,
                    'message' => $message,
                    'intent_id' => null,
                    'was_helpful' => $sender === 'bot' ? true : null,
                    'metadata' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($messageRows, 500) as $chunk) {
            DB::table('tbl_chatbot_messages')->insert($chunk);
        }
    }
}
