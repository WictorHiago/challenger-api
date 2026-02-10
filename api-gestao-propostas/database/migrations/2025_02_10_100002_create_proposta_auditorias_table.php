<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposta_auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->constrained('propostas')->cascadeOnDelete();
            $table->string('actor');
            $table->string('evento', 30);
            $table->json('payload')->nullable();
            $table->timestamp('created_at');

            $table->index(['proposta_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposta_auditorias');
    }
};
