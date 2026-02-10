<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('produto');
            $table->decimal('valor_mensal', 12, 2);
            $table->string('status', 20)->default('DRAFT');
            $table->string('origem', 20)->default('API');
            $table->unsignedInteger('versao')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propostas');
    }
};
