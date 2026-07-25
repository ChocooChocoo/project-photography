<?php

namespace Tests\Feature;

use App\Models\Chatbot\ChatbotMessageModel;
use App\Services\ChatbotService;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsChatbotSchema;
use Tests\TestCase;

class ChatbotFeatureTest extends TestCase
{
    use BuildsChatbotSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootChatbotDatabase();
        $this->configureFakeGroq();
        Http::preventStrayRequests();
    }

    /**
     * Fake a successful Groq completion.
     */
    private function fakeGroqReply(string $text, int $tokens = 120): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => $text]]],
                'usage' => ['total_tokens' => $tokens],
            ], 200),
        ]);
    }

    /**
     * The assistant bootstraps seeded defaults for an owner without a config.
     */
    public function test_assistant_bootstraps_seeded_defaults_for_owner_without_config(): void
    {
        $ownerId = $this->createOwnerWithStudio('bootstrap-owner@example.com', 'Bootstrap Studio');

        $config = app(ChatbotService::class)->forOwner($ownerId)->getActiveConfig();

        $this->assertNotNull($config);
        $this->assertSame('Photography Assistant', $config->config_name);
        $this->assertDatabaseHas('tbl_chatbot_intents', [
            'config_id' => $config->id,
            'intent_name' => 'Booking Information',
        ]);
    }

    /**
     * A photography question is answered by the model, not a stored template.
     */
    public function test_assistant_returns_model_generated_reply(): void
    {
        $ownerId = $this->createOwnerWithStudio('ai-owner@example.com', 'AI Studio');
        $this->fakeGroqReply('Our wedding coverage starts with a consultation, then we lock your date.');

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();

        $response = $service->processMessage('How do I book a wedding shoot?');

        $this->assertTrue($response['success']);
        $this->assertSame('ai', $response['metadata']['source']);
        $this->assertStringContainsString('consultation', $response['message']);
        $this->assertNotEmpty($response['quick_replies']);
        Http::assertSentCount(1);
    }

    /**
     * The system prompt carries the studio's live active packages, and pricing
     * questions also return the structured package payload for the UI.
     */
    public function test_assistant_prompt_includes_only_active_packages(): void
    {
        $ownerId = $this->createOwnerWithStudio('package-owner@example.com', 'Package Studio');
        $this->createStudioPackage($ownerId, [
            'package_name' => 'Wedding Essential',
            'package_price' => 15000,
            'package_inclusions' => ['4 hours coverage', '150 edited photos'],
            'status' => 'active',
        ]);
        $this->createStudioPackage($ownerId, [
            'package_name' => 'Luxury Wedding',
            'package_price' => 45000,
            'status' => 'inactive',
        ]);

        $this->fakeGroqReply('Wedding Essential is PHP 15,000.00 and covers four hours.');

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();

        $response = $service->processMessage('What are your package rates?');

        $this->assertCount(1, $response['metadata']['packages']);
        $this->assertSame('Wedding Essential', $response['metadata']['packages'][0]['name']);

        Http::assertSent(function ($request) {
            $systemPrompt = $request['messages'][0]['content'];

            return str_contains($systemPrompt, 'Wedding Essential')
                && str_contains($systemPrompt, 'PHP 15,000.00')
                && ! str_contains($systemPrompt, 'Luxury Wedding');
        });
    }

    /**
     * Non-pricing questions do not attach the package payload.
     */
    public function test_assistant_omits_package_payload_for_non_pricing_questions(): void
    {
        $ownerId = $this->createOwnerWithStudio('nonpricing-owner@example.com', 'Non Pricing Studio');
        $this->createStudioPackage($ownerId, ['package_name' => 'Portrait Basic']);
        $this->fakeGroqReply('We shoot on weekdays and weekends by appointment.');

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();

        $response = $service->processMessage('Which days do you shoot?');

        $this->assertArrayNotHasKey('packages', $response['metadata']);

        // Full package rows cost tokens, so a non-pricing question only gets a
        // one-line summary in the prompt.
        Http::assertSent(function ($request) {
            $systemPrompt = $request['messages'][0]['content'];

            return str_contains($systemPrompt, '1 active packages, priced from PHP')
                && ! str_contains($systemPrompt, '- Portrait Basic --');
        });
    }

    /**
     * Owner-maintained knowledge entries reach the prompt as untrusted data.
     */
    public function test_assistant_prompt_wraps_studio_knowledge_as_untrusted_data(): void
    {
        $ownerId = $this->createOwnerWithStudio('faq-owner@example.com', 'FAQ Studio');
        $this->fakeGroqReply('Booking starts by sharing your preferred date.');

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();
        $service->processMessage('How do I book?');

        Http::assertSent(function ($request) {
            $messages = $request['messages'];
            $systemPrompt = $messages[0]['content'];
            $userMessage = $messages[count($messages) - 1]['content'];

            return str_contains($systemPrompt, '<untrusted_data source="studio_faq">')
                && str_contains($systemPrompt, 'HARD SECURITY RULES')
                && str_contains($userMessage, '<untrusted_data source="user_message">');
        });
    }

    /**
     * Profanity and spam are stopped before any provider call is made.
     */
    public function test_assistant_moderates_blocked_and_spammy_messages(): void
    {
        $ownerId = $this->createOwnerWithStudio('moderation-owner@example.com', 'Moderation Studio');
        Http::fake();

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();

        $blockedResponse = $service->processMessage('fuck your service');
        $spamResponse = $service->processMessage('helooooooooooooo');

        $this->assertSame('blocked_language', $blockedResponse['metadata']['guard']);
        $this->assertSame('spam_or_repetition', $spamResponse['metadata']['guard']);
        $this->assertSame('guard', $blockedResponse['metadata']['source']);
        Http::assertNothingSent();

        $lastTwoUserMessages = ChatbotMessageModel::query()
            ->where('sender_type', 'user')
            ->latest('id')
            ->take(2)
            ->get();

        $this->assertTrue($lastTwoUserMessages->contains(
            fn (ChatbotMessageModel $message) => ($message->metadata['moderation']['type'] ?? null) === 'blocked_language'
        ));

        $this->assertTrue($lastTwoUserMessages->contains(
            fn (ChatbotMessageModel $message) => ($message->metadata['moderation']['type'] ?? null) === 'spam_or_repetition'
        ));
    }

    /**
     * Recent turns are replayed to the model so follow-up questions have context.
     */
    public function test_assistant_sends_recent_history_to_the_model(): void
    {
        $ownerId = $this->createOwnerWithStudio('history-owner@example.com', 'History Studio');
        $this->fakeGroqReply('Yes, that package includes an online gallery.');

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();
        $service->processMessage('Do you shoot christening events?');
        $service->processMessage('Does it include an online gallery?');

        Http::assertSent(function ($request) {
            $roles = array_column($request['messages'], 'role');

            return $roles[0] === 'system'
                && in_array('assistant', $roles, true)
                && count($request['messages']) > 2;
        });
    }
}
