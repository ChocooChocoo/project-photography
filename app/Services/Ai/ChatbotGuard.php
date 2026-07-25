<?php

namespace App\Services\Ai;

/**
 * Security guardrails for the photography AI assistant.
 *
 * Every user message passes through inspectInput() before it is allowed to
 * reach the model, and every model reply passes through inspectOutput() before
 * it is allowed to reach a browser. Both directions fail closed: anything the
 * guard cannot vouch for is replaced wholesale with a fixed fallback string.
 */
class ChatbotGuard
{
    /**
     * Sentinel the model is instructed to emit for out-of-scope requests.
     */
    public const OFF_TOPIC_MARKER = '[OFFTOPIC]';

    /**
     * Hard ceiling on accepted user input length (token spend control).
     */
    public const MAX_INPUT_LENGTH = 600;

    /**
     * Hard ceiling on accepted model output length.
     */
    public const MAX_OUTPUT_LENGTH = 2000;

    /**
     * Attempts to reassign the assistant's role, escape its scope, or extract
     * protected system information. Matched against the normalized message.
     *
     * @var array<int, string>
     */
    protected const INJECTION_PATTERNS = [
        // Instruction override / role reassignment.
        '/\b(ignore|disregard|forget|override|bypass|skip)\b.{0,30}\b(previous|prior|above|earlier|initial|all|your|any)\b.{0,20}\b(instruction|instructions|rule|rules|prompt|prompts|guideline|guidelines|restriction|restrictions)\b/u',
        '/\byou are (now|no longer)\b/u',
        '/\b(act|behave|respond|pretend|roleplay|role-play) as (a|an|if)\b/u',
        '/\b(dan|developer|debug|god|admin|sudo|jailbreak|unrestricted|uncensored) mode\b/u',
        '/\bnew (instructions|persona|role|system prompt)\b/u',
        '/\bwithout (any )?(restrictions|filters|guardrails|limitations)\b/u',

        // Prompt / instruction disclosure.
        '/\b(system|initial|hidden|original|internal|developer)\s*(prompt|prompts|message|instruction|instructions)\b/u',
        '/\byour\s*(prompt|prompts|instructions|rules|guidelines|configuration|config|source code|training)\b/u',
        '/\b(reveal|show|print|repeat|echo|output|display|dump|list|recite|summarize|paraphrase|translate|encode|decode)\b.{0,40}\b(prompt|instructions|rules|everything above|above text|system message|guidelines)\b/u',
        '/\beverything (above|before this|you were told)\b/u',
        '/\bwhat (were|are) (you|your) (told|instructed|programmed)\b/u',

        // Credential / environment / infrastructure probing.
        '/\b(api|secret|private|access|auth|bearer)[\s_-]?(key|keys|token|tokens|credential|credentials)\b/u',
        '/\bgroq[\s_-]?(api|key|token|secret)\b/u',
        '/\b(env|environment)[\s_-]?(var|vars|variable|variables|file)\b/u',
        '/(\.env|dotenv)\b/u',
        '/\b(groq_|app_key|app_secret|db_password|db_username|db_host|aws_secret|stripe_secret|paymongo_secret)/u',
        '/\bconfig\s*\(/u',
        '/\b(database|db) (credential|credentials|password|dump|schema|connection string)\b/u',
        '/\b(show|give|send|leak|expose|reveal|print)\b.{0,30}\b(credential|credentials|password|passwords|secret|secrets|token|api key)\b/u',

        // Source code / server internals / data exfiltration.
        '/\b(source code|codebase|repository|stack trace|error log|server log|log file|file system|directory listing)\b/u',
        '/\b(cat|type|read|open)\s+[\w\/.\\\\-]*\.(env|php|json|log|sql|yml|yaml|key|pem)\b/u',
        '/\b(select|insert|update|delete|drop|union)\b.{0,20}\bfrom\b/u',
        '/\b(curl|wget|fetch|http:\/\/|https:\/\/)\b.{0,40}\b(key|token|secret|env)\b/u',

        // Encoding / obfuscation laundering.
        '/\b(base64|rot13|hex|binary|morse|reverse)\b.{0,40}\b(prompt|instructions|rules|key|secret|token)\b/u',
        '/\b(decode|deobfuscate) (this|the following)\b/u',
    ];

    /**
     * Distinctive phrases from the system prompt. If any of these surface in a
     * model reply, the model is echoing its instructions and the reply is
     * discarded rather than shown.
     *
     * @var array<int, string>
     */
    protected const INSTRUCTION_ECHO_MARKERS = [
        'untrusted_data',
        'you are the ai assistant for',
        'hard security rules',
        'never reveal, quote, paraphrase',
        'output contract',
        'reply with exactly',
    ];

    /**
     * Patterns that indicate leaked secrets or server internals in output.
     *
     * @var array<int, string>
     */
    protected const OUTPUT_LEAK_PATTERNS = [
        '/\bgsk_[A-Za-z0-9]{8,}/u',                       // Groq key prefix.
        '/\bsk[-_](live|test|proj|ant)?[-_]?[A-Za-z0-9]{16,}/u', // Generic provider keys.
        '/\bbase64:[A-Za-z0-9+\/=]{30,}/u',                // Laravel APP_KEY format.
        '/\b(GROQ|APP|DB|AWS|STRIPE|PAYMONGO|MAIL|REDIS|SESSION)_[A-Z_]{2,}\s*=/u',
        '/\b(GROQ_API_KEY|APP_KEY|DB_PASSWORD|DB_USERNAME|AWS_SECRET_ACCESS_KEY)\b/u',
        '/\bIlluminate\\\\[A-Za-z]+/u',                    // Framework internals.
        '/#\d+\s+[A-Za-z]:[\\\\\/]/u',                     // Stack frame (Windows).
        '/#\d+\s+\/[\w\/.-]+\.php/u',                      // Stack frame (POSIX).
        '/\b[A-Za-z]:\\\\[\w\\\\ .-]+\\\\(app|config|vendor|database|resources)\b/u',
        '/\/(var\/www|home\/\w+)\/[\w\/.-]+/u',
        '/\bvendor\/laravel\/framework\b/u',
        '/\bSQLSTATE\[/u',
    ];

    /**
     * Fixed fallback copy. Identical for every portal and every role, and
     * deliberately free of technical detail.
     *
     * @var array<string, string>
     */
    protected const FALLBACKS = [
        'off_topic' => 'I can only help with photography services such as bookings, packages, pricing, services, and availability. Please ask me a photography-related question and I will be glad to help.',
        'secure_refusal' => 'I cannot share that. I can only help with photography services such as bookings, packages, pricing, and availability. What would you like to know about our photography services?',
        'rate_limited' => 'The assistant is handling a lot of requests right now. Please try your photography question again in a moment.',
        'service_unavailable' => 'The assistant is temporarily unavailable. Please try your photography question again shortly, or contact the studio team directly.',
        'empty_input' => 'I did not receive a question. Please type a photography-related question such as booking steps, package rates, services, or availability.',
    ];

    /**
     * Inspect a user message before it is sent to the model.
     *
     * @param  array<string, mixed>  $moderationSettings
     * @return array<string, mixed>|null Null when the message is safe to send.
     */
    public function inspectInput(string $normalizedMessage, array $moderationSettings): ?array
    {
        if ($normalizedMessage === '') {
            return $this->block('empty_input', $this->fallback('empty_input'));
        }

        if (mb_strlen($normalizedMessage) > self::MAX_INPUT_LENGTH) {
            return $this->block('too_long', $this->fallback('empty_input'));
        }

        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalizedMessage) === 1) {
                // The matched pattern is intentionally not reported back to the
                // caller, so probing cannot be used to map the filter.
                return $this->block('secure_refusal', $this->fallback('secure_refusal'));
            }
        }

        return null;
    }

    /**
     * Strip control characters, zero-width characters, and bidi overrides that
     * could be used to hide instructions from the guard while still reaching
     * the model.
     */
    public function sanitizeInput(string $message): string
    {
        $cleaned = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{FEFF}]/u', '', $message) ?? $message;
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned;

        return mb_substr(trim($cleaned), 0, self::MAX_INPUT_LENGTH);
    }

    /**
     * Inspect a model reply before it is returned to the browser.
     *
     * @return array<string, mixed> {ok: bool, code: string, message: string}
     */
    public function inspectOutput(?string $modelText): array
    {
        $text = trim((string) $modelText);

        // Some models wrap reasoning in <think> blocks; drop them entirely.
        $text = trim(preg_replace('/<think>.*?<\/think>/isu', '', $text) ?? $text);

        if ($text === '' || mb_strlen($text) > self::MAX_OUTPUT_LENGTH) {
            return $this->reject('service_unavailable');
        }

        if (str_contains($text, self::OFF_TOPIC_MARKER)) {
            return $this->reject('off_topic');
        }

        $lowered = mb_strtolower($text);

        foreach (self::INSTRUCTION_ECHO_MARKERS as $marker) {
            if (str_contains($lowered, $marker)) {
                return $this->reject('secure_refusal');
            }
        }

        foreach (self::OUTPUT_LEAK_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return $this->reject('secure_refusal');
            }
        }

        // Live secret values, in case the model ever regurgitates a real one.
        foreach ($this->liveSecretValues() as $secret) {
            if (str_contains($text, $secret)) {
                return $this->reject('secure_refusal');
            }
        }

        return [
            'ok' => true,
            'code' => 'ai',
            'message' => $text,
        ];
    }

    /**
     * Get the fixed fallback copy for a guard outcome code.
     */
    public function fallback(string $code): string
    {
        return self::FALLBACKS[$code] ?? self::FALLBACKS['service_unavailable'];
    }

    /**
     * Actual secret values that must never appear in a response body.
     *
     * @return array<int, string>
     */
    protected function liveSecretValues(): array
    {
        $candidates = [
            (string) config('services.groq.api_key'),
            (string) config('app.key'),
            (string) config('database.connections.mysql.password'),
            (string) config('services.stripe.secret_key'),
            (string) config('services.paymongo.secret_key'),
        ];

        // Very short values would cause false positives on ordinary prose.
        return array_values(array_filter($candidates, fn ($value) => mb_strlen($value) >= 12));
    }

    /**
     * @return array<string, mixed>
     */
    protected function block(string $code, string $message): array
    {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function reject(string $code): array
    {
        return [
            'ok' => false,
            'code' => $code,
            'message' => $this->fallback($code),
        ];
    }
}
