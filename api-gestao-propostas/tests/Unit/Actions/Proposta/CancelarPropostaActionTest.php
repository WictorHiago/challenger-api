<?php

namespace Tests\Unit\Actions\Proposta;

use App\Actions\Proposta\CancelarPropostaAction;
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
class CancelarPropostaActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_proposta_submitted_cancela_com_sucesso(): void
    {
        $proposta = $this->criarProposta(versao: 1, status: PropostaStatus::SUBMITTED);
        $propostaCancelada = $this->criarProposta(id: 1, versao: 2, status: PropostaStatus::CANCELED);
        $propostaCancelada = $this->mockLoadCliente($propostaCancelada);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->status === PropostaStatus::SUBMITTED), [
                'status' => PropostaStatus::CANCELED,
                'versao' => 2,
            ])
            ->andReturn($propostaCancelada);

        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldReceive('registrar')
            ->once()
            ->with(
                $propostaCancelada,
                AuditoriaEvento::STATUS_CHANGED,
                ['de' => 'SUBMITTED', 'para' => 'CANCELED'],
                'system'
            );

        $action = new CancelarPropostaAction($repository, $auditoria);

        $resultado = $action->execute($proposta);

        $this->assertSame($propostaCancelada, $resultado);
    }

    public function test_proposta_draft_lanca_exception_ao_cancelar(): void
    {
        $proposta = $this->criarProposta(status: PropostaStatus::DRAFT);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldNotReceive('update');
        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldNotReceive('registrar');

        $action = new CancelarPropostaAction($repository, $auditoria);

        $this->expectException(PropostaStatusTransitionException::class);
        $this->expectExceptionMessage('Transição inválida de DRAFT para CANCELED');

        $action->execute($proposta);
    }

    public function test_proposta_approved_lanca_exception_ao_cancelar(): void
    {
        $proposta = $this->criarProposta(status: PropostaStatus::APPROVED);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldNotReceive('update');
        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldNotReceive('registrar');

        $action = new CancelarPropostaAction($repository, $auditoria);

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
