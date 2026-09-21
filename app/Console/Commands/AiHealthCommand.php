<?php

namespace App\Console\Commands;

use App\Services\Ai\GroqClient;
use Illuminate\Console\Command;

/**
 * Pings the configured AI provider so an expired or wrong key is obvious
 * before the assistant is used in production.
 *
 * The key itself is never printed or logged; only a short status and the
 * configured model name are shown.
 */
class AiHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check that the configured AI provider key works.';

    /**
     * Execute the console command.
     */
    public function handle(GroqClient $groq): int
    {
        $model = (string) config('services.groq.model');

        if ((string) config('services.groq.api_key') === '') {
            $this->error('Groq key: not configured');
            $this->line('Groq model: '.$model);

            return self::FAILURE;
        }

        $result = $groq->ping();

        if ($result['ok']) {
            $this->info('Groq key: ok');
            $this->line('Groq model: '.$model);

            return self::SUCCESS;
        }

        $this->error('Groq key: '.$this->describeFailure($result));
        $this->line('Groq model: '.$model);

        return self::FAILURE;
    }

    /**
     * Turn the client's reason code and status into a short, safe label.
     *
     * @param  array{ok: bool, status: int|null, reason: string|null}  $result
     */
    protected function describeFailure(array $result): string
    {
        $status = $result['status'];

        return match ($result['reason']) {
            'invalid_credentials' => 'invalid ('.$status.')',
            'not_configured' => 'not configured',
            'provider_rate_limited' => 'rate limited ('.$status.')',
            'transport_error' => 'unreachable',
            default => 'error'.($status !== null ? ' ('.$status.')' : ''),
        };
    }
}
