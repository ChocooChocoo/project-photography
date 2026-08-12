<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns left over from the keyword-matching chatbot. Nothing reads them
     * since the AI assistant replaced the matcher:
     *
     * - `fallback_message` — fallback copy is fixed in `Ai\ChatbotGuard` so an
     *   owner cannot soften a security refusal.
     * - `trigger_keywords` — the assistant understands the question; there is no
     *   keyword list to match against.
     * - `response_type` / `image_url` — replies are generated text, never a
     *   stored image or button payload.
     * - `match_count` — counted matcher hits; nothing increments it.
     */
    public function up(): void
    {
        Schema::table('tbl_chatbot_configs', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_chatbot_configs', 'fallback_message')) {
                $table->dropColumn('fallback_message');
            }
        });

        if (Schema::hasTable('tbl_chatbot_intents')) {
            Schema::table('tbl_chatbot_intents', function (Blueprint $table) {
                $columns = ['trigger_keywords', 'response_type', 'image_url', 'match_count'];
                $existing = array_filter($columns, fn ($c) => Schema::hasColumn('tbl_chatbot_intents', $c));
                if (!empty($existing)) {
                    $table->dropColumn($existing);
                }
            });
        }
    }

    /**
     * Restores the columns empty. The original values belonged to the removed
     * matcher and are not recoverable.
     */
    public function down(): void
    {
        Schema::table('tbl_chatbot_configs', function (Blueprint $table) {
            $table->text('fallback_message')->nullable();
        });

        Schema::table('tbl_chatbot_intents', function (Blueprint $table) {
            $table->json('trigger_keywords')->nullable();
            $table->string('response_type')->default('text');
            $table->string('image_url')->nullable();
            $table->integer('match_count')->default(0);
        });
    }
};
