<?php

namespace App\Models;

use App\Enums\AuditoriaEvento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropostaAuditoria extends Model
{
    protected $table = 'proposta_auditorias';

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (PropostaAuditoria $model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    protected $fillable = [
        'proposta_id',
        'actor',
        'evento',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'evento' => AuditoriaEvento::class,
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class, 'proposta_id');
    }
}
