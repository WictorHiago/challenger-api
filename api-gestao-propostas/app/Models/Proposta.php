<?php

namespace App\Models;

use App\Enums\PropostaOrigem;
use App\Enums\PropostaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposta extends Model
{
    use SoftDeletes;

    protected $table = 'propostas';

    protected $fillable = [
        'cliente_id',
        'produto',
        'valor_mensal',
        'status',
        'origem',
        'versao',
    ];

    protected function casts(): array
    {
        return [
            'status' => PropostaStatus::class,
            'origem' => PropostaOrigem::class,
            'valor_mensal' => 'decimal:2',
            'versao' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(PropostaAuditoria::class, 'proposta_id');
    }
}
