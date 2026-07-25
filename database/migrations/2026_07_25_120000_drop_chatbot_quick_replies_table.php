<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-intent quick replies belonged to the fixed-response chatbot. The AI
     * assistant generates its own replies and reads suggestion chips from the
     * config `settings` JSON, so the table has no remaining reader.
     */
    public function up(): void
    {
        Schema::dropIfExists('tbl_chatbot_quick_replies');
    }

    /**
     * No down path: the data was only consumed by the removed keyword matcher.
     */
    public function down(): void
    {
        // ponytail: intentionally irreversible, nothing reads this table anymore.
    }
};
