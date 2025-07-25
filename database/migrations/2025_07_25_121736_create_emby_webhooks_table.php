<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emby_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('item_type')->nullable();
            $table->string('item_name')->nullable();
            $table->string('item_path')->nullable();
            $table->string('user_name')->nullable();
            $table->string('server_name')->nullable();
            $table->json('metadata')->nullable();
            $table->json('raw_payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emby_webhooks');
    }
};
