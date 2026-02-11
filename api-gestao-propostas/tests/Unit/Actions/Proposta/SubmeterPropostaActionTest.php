<?php

namespace Tests\Unit\Actions\Proposta;

use App\Actions\Proposta\SubmeterPropostaAction;
use App\Contracts\PropostaAuditoriaInterface;
use App\Contracts\PropostaRepositoryInterface;
use App\Enums\AuditoriaEvento;
use App\Enums\PropostaOrigem;
use App\Enums\PropostaStatus;
use App\Exceptions\PropostaStatusTransitionException;
use App\Models\Proposta;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('transicao-status')]
#[Group('idempotencia')]
class SubmeterPropostaActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_proposta_draft_submete_com_sucesso(): void
    {
        $proposta = $this->criarProposta(versao: 1, status: PropostaStatus::DRAFT);
        $request = Request::create('/test', 'POST');
        $request->headers->set('Idempotency-Key', '');

        $propostaSubmetida = $this->criarProposta(id: 1, versao: 2, status: PropostaStatus::SUBMITTED);
        $propostaSubmetida = $this->mockLoadCliente($propostaSubmetida);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldReceive('findByIdempotencyKey')->never();
        $repository->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->status === PropostaStatus::DRAFT), [
                'status' => PropostaStatus::SUBMITTED,
                'versao' => 2,
            ])
            ->andReturn($propostaSubmetida);
        $repository->shouldReceive('storeIdempotencyKey')->never();

        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldReceive('registrar')
            ->once()
            ->with(
                $propostaSubmetida,
                AuditoriaEvento::STATUS_CHANGED,
                ['de' => 'DRAFT', 'para' => 'SUBMITTED'],
                'system'
            );

        $action = new SubmeterPropostaAction($repository, $auditoria);

        $resultado = $action->execute($proposta, $request);

        $this->assertSame($propostaSubmetida, $resultado);
    }

    public function test_proposta_approved_lanca_exception_ao_submeter(): void
    {
        $proposta = $this->criarProposta(status: PropostaStatus::APPROVED);
        $request = Request::create('/test', 'POST');

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldNotReceive('update');
        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldNotReceive('registrar');

        $action = new SubmeterPropostaAction($repository, $auditoria);

        $this->expectException(PropostaStatusTransitionException::class);
        $this->expectExceptionMessage('Proposta em estado final');

        $action->execute($proposta, $request);
    }

    public function test_proposta_submitted_nao_pode_ser_submetida_novamente(): void
    {
        $proposta = $this->criarProposta(status: PropostaStatus::SUBMITTED);
        $request = Request::create('/test', 'POST');

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldNotReceive('update');
        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldNotReceive('registrar');

        $action = new SubmeterPropostaAction($repository, $auditoria);

        $this->expectException(PropostaStatusTransitionException::class);

        $action->execute($proposta, $request);
    }

    public function test_retorna_proposta_existente_quando_idempotency_key_ja_processada(): void
    {
        $proposta = $this->criarProposta(versao: 1, status: PropostaStatus::DRAFT);
        $request = Request::create('/test', 'POST');
        $request->headers->set('Idempotency-Key', 'chave-submit-789');

        $propostaExistente = $this->criarProposta(id: 1, versao: 2, status: PropostaStatus::SUBMITTED);
        $propostaExistente = $this->mockLoadCliente($propostaExistente);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldReceive('findByIdempotencyKey')
            ->once()
            ->with('chave-submit-789')
            ->andReturn($propostaExistente);
        $repository->shouldNotReceive('update');
        $repository->shouldNotReceive('storeIdempotencyKey');

        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldNotReceive('registrar');

        $action = new SubmeterPropostaAction($repository, $auditoria);

        $resultado = $action->execute($proposta, $request);

        $this->assertSame($propostaExistente, $resultado);
    }

    public function test_armazena_idempotency_key_apos_submeter_com_sucesso(): void
    {
        $proposta = $this->criarProposta(versao: 1, status: PropostaStatus::DRAFT);
        $request = Request::create('/test', 'POST');
        $request->headers->set('Idempotency-Key', 'chave-submit-nova');

        $propostaSubmetida = $this->criarProposta(id: 1, versao: 2, status: PropostaStatus::SUBMITTED);
        $propostaSubmetida = $this->mockLoadCliente($propostaSubmetida);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldReceive('findByIdempotencyKey')
            ->once()
            ->with('chave-submit-nova')
            ->andReturn(null);
        $repository->shouldReceive('update')->once()->andReturn($propostaSubmetida);
        $repository->shouldReceive('storeIdempotencyKey')
            ->once()
            ->with('chave-submit-nova', 1);

        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldReceive('registrar')->once();

        $action = new SubmeterPropostaAction($repository, $auditoria);

        $resultado = $action->execute($proposta, $request);

        $this->assertSame($propostaSubmetida, $resultado);
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
