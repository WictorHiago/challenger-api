<?php

namespace Tests\Unit\Actions\Proposta;

use App\Actions\Proposta\RejeitarPropostaAction;
use App\Contracts\PropostaAuditoriaInterface;
use App\Contracts\PropostaRepositoryInterface;
use App\Enums\AuditoriaEvento;
use App\Enums\PropostaOrigem;
use App\Enums\PropostaStatus;
use App\Exceptions\PropostaStatusTransitionException;
use App\Models\Proposta;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('transicao-status')]
class RejeitarPropostaActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_proposta_submitted_rejeita_com_sucesso(): void
    {
        $proposta = $this->criarProposta(versao: 1, status: PropostaStatus::SUBMITTED);
        $propostaRejeitada = $this->criarProposta(id: 1, versao: 2, status: PropostaStatus::REJECTED);
        $propostaRejeitada = $this->mockLoadCliente($propostaRejeitada);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->status === PropostaStatus::SUBMITTED), [
                'status' => PropostaStatus::REJECTED,
                'versao' => 2,
            ])
            ->andReturn($propostaRejeitada);

        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldReceive('registrar')
            ->once()
            ->with(
                $propostaRejeitada,
                AuditoriaEvento::STATUS_CHANGED,
                ['de' => 'SUBMITTED', 'para' => 'REJECTED'],
                'system'
            );

        $action = new RejeitarPropostaAction($repository, $auditoria);

        $resultado = $action->execute($proposta);

        $this->assertSame($propostaRejeitada, $resultado);
    }

    public function test_proposta_draft_lanca_exception_ao_rejeitar(): void
    {
        $proposta = $this->criarProposta(status: PropostaStatus::DRAFT);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldNotReceive('update');
        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldNotReceive('registrar');

        $action = new RejeitarPropostaAction($repository, $auditoria);

        $this->expectException(PropostaStatusTransitionException::class);
        $this->expectExceptionMessage('Transição inválida de DRAFT para REJECTED');

        $action->execute($proposta);
    }

    public function test_proposta_approved_lanca_exception_ao_rejeitar(): void
    {
        $proposta = $this->criarProposta(status: PropostaStatus::APPROVED);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldNotReceive('update');
        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldNotReceive('registrar');

        $action = new RejeitarPropostaAction($repository, $auditoria);

        $this->expectException(PropostaStatusTransitionException::class);
        $this->expectExceptionMessage('Proposta em estado final');

        $action->execute($proposta);
    }

    private function criarProposta(
        int $id = 1,
        int $versao = 1,
        ?PropostaStatus $status = null,
        string $produto = 'Seguro Teste',
        ?float $valorMensal = 100.0
    ): Proposta {
        $proposta = new Proposta();
        $proposta->id = $id;
        $proposta->cliente_id = 1;
        $proposta->produto = $produto;
        $proposta->valor_mensal = $valorMensal ?? 100.0;
        $proposta->status = $status ?? PropostaStatus::DRAFT;
        $proposta->origem = PropostaOrigem::API;
        $proposta->versao = $versao;

        return $proposta;
    }

    private function mockLoadCliente(Proposta $proposta): Proposta
    {
        $mock = Mockery::mock($proposta)->makePartial();
        $mock->shouldReceive('load')->with('cliente')->andReturn($mock);

        return $mock;
    }
}
