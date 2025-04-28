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
        Schema::create('preseed_configs', function (Blueprint $table) {
            $table->id();
            $table->string('hash_id', 16)->unique(); // Unieke hash voor URL (lengte 16 is ruim voldoende)
            $table->string('original_name')->nullable(); // De naam die de gebruiker invoerde (voor weergave/referentie)
            $table->longText('content'); // De volledige preseed inhoud
            // Optioneel: Koppel aan gebruiker als je authenticatie gebruikt
            // $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            // Index voor snelle lookups
            $table->index('hash_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preseed_configs');
    }
};
