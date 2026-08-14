<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_client_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('studio_id')->constrained('tbl_studios')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['client_id', 'studio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_client_favorites');
    }
};
