<?php

namespace App\Actions\Proposta;

use App\Contracts\PropostaAuditoriaInterface;
use App\Contracts\PropostaRepositoryInterface;
use App\Enums\AuditoriaEvento;
use App\Enums\PropostaStatus;
use App\Models\Proposta;
use Illuminate\Http\Request;

class AtualizarPropostaAction
{
    public function __construct(
        private readonly PropostaRepositoryInterface $propostaRepository,
        private readonly PropostaAuditoriaInterface $auditoria
    ) {}

    public function execute(Proposta $proposta, Request $request): Proposta
    {
        $versaoInformada = (int) $request->input('versao');
        if ($proposta->versao !== $versaoInformada) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(409, 'Conflito de versão. A proposta foi alterada por outro processo.');
        }

        if ($proposta->status !== PropostaStatus::DRAFT) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                422,
                'Apenas propostas em rascunho (DRAFT) podem ser editadas.'
            );
        }

        $payloadAntes = $proposta->only(['produto', 'valor_mensal']);
        $data = array_filter($request->only(['produto', 'valor_mensal']), fn ($v) => $v !== null);

        if (empty($data)) {
            return $proposta;
        }

        $proposta = $this->propostaRepository->update($proposta, array_merge($data, ['versao' => $proposta->versao + 1]));

        $this->auditoria->registrar(
            $proposta,
            AuditoriaEvento::UPDATED_FIELDS,
            ['antes' => $payloadAntes, 'depois' => $proposta->only(['produto', 'valor_mensal'])],
            'system'
        );

        return $proposta;
    }
}
