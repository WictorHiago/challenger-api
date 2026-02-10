<?php

namespace App\Exceptions;

use App\Enums\PropostaStatus;
use Exception;

class PropostaStatusTransitionException extends Exception
{
    public static function transicaoInvalida(PropostaStatus $atual, PropostaStatus $novo): self
    {
        $permitidos = array_map(
            fn (PropostaStatus $s) => $s->value,
            $atual->transicoesPermitidas()
        );

        $mensagem = $atual->isFinal()
            ? 'Proposta em estado final (' . $atual->value . '). Alteração não permitida.'
            : sprintf(
                'Transição inválida de %s para %s. Transições permitidas: %s',
                $atual->value,
                $novo->value,
                implode(', ', $permitidos ?: ['nenhuma'])
            );

        return new self($mensagem);
    }
}
