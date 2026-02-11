<?php

namespace Tests\Unit\Actions\Proposta;

use App\Actions\Proposta\AtualizarPropostaAction;
use App\Contracts\PropostaAuditoriaInterface;
use App\Contracts\PropostaRepositoryInterface;
use App\Enums\AuditoriaEvento;
use App\Enums\PropostaOrigem;
use App\Enums\PropostaStatus;
use App\Exceptions\ConflitoVersaoException;
use App\Models\Proposta;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

#[Group('conflito-versao')]
class AtualizarPropostaActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_lanca_conflito_versao_exception_quando_versao_desatualizada(): void
    {
        $proposta = $this->criarProposta(versao: 2, status: PropostaStatus::DRAFT);
        $request = Request::create('/test', 'PATCH', ['versao' => 1, 'produto' => 'Novo Produto']);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);

        $repository->shouldNotReceive('update');
        $auditoria->shouldNotReceive('registrar');

        $action = new AtualizarPropostaAction($repository, $auditoria);

        $this->expectException(ConflitoVersaoException::class);

        $action->execute($proposta, $request);
    }

    public function test_atualiza_proposta_quando_versao_correta(): void
    {
        $proposta = $this->criarProposta(versao: 1, status: PropostaStatus::DRAFT);
        $request = Request::create('/test', 'PATCH', [
            'versao' => 1,
            'produto' => 'Produto Atualizado',
            'valor_mensal' => 149.90,
        ]);

        $propostaAtualizada = $this->criarProposta(
            id: 1,
            versao: 2,
            status: PropostaStatus::DRAFT,
            produto: 'Produto Atualizado',
            valorMensal: 149.90
        );

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->versao === 1), Mockery::on(function (array $data) {
                return $data['produto'] === 'Produto Atualizado'
                    && (float) $data['valor_mensal'] === 149.90
                    && $data['versao'] === 2;
            }))
            ->andReturn($propostaAtualizada);

        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldReceive('registrar')
            ->once()
            ->with(
                $propostaAtualizada,
                AuditoriaEvento::UPDATED_FIELDS,
                Mockery::type('array'),
                'system'
            );

        $action = new AtualizarPropostaAction($repository, $auditoria);

        $resultado = $action->execute($proposta, $request);

        $this->assertSame($propostaAtualizada, $resultado);
        $this->assertSame(2, $resultado->versao);
    }

    public function test_lanca_exception_quando_proposta_nao_esta_em_draft(): void
    {
        $proposta = $this->criarProposta(versao: 1, status: PropostaStatus::SUBMITTED);
        $request = Request::create('/test', 'PATCH', ['versao' => 1, 'produto' => 'Tentativa']);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);

        $repository->shouldNotReceive('update');
        $auditoria->shouldNotReceive('registrar');

        $action = new AtualizarPropostaAction($repository, $auditoria);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Apenas propostas em rascunho (DRAFT) podem ser editadas.');

        $action->execute($proposta, $request);
    }

    public function test_retorna_proposta_sem_alterar_quando_payload_vazio(): void
    {
        $proposta = $this->criarProposta(versao: 1, status: PropostaStatus::DRAFT);
        $request = Request::create('/test', 'PATCH', ['versao' => 1]);

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);

        $repository->shouldNotReceive('update');
        $auditoria->shouldNotReceive('registrar');

        $action = new AtualizarPropostaAction($repository, $auditoria);

        $resultado = $action->execute($proposta, $request);

        $this->assertSame($proposta, $resultado);
    }

    public function test_atualiza_apenas_produto_quando_valor_mensal_ausente(): void
    {
        $proposta = $this->criarProposta(versao: 1, status: PropostaStatus::DRAFT);
        $request = Request::create('/test', 'PATCH', ['versao' => 1, 'produto' => 'Só Produto']);

        $propostaAtualizada = $this->criarProposta(id: 1, versao: 2, produto: 'Só Produto');

        $repository = Mockery::mock(PropostaRepositoryInterface::class);
        $repository->shouldReceive('update')
            ->once()
            ->andReturn($propostaAtualizada);

        $auditoria = Mockery::mock(PropostaAuditoriaInterface::class);
        $auditoria->shouldReceive('registrar')->once();

        $action = new AtualizarPropostaAction($repository, $auditoria);

        $resultado = $action->execute($proposta, $request);

        $this->assertSame($propostaAtualizada, $resultado);
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
        $proposta->valor_mensal = $valorMensal;
        $proposta->status = $status ?? PropostaStatus::DRAFT;
        $proposta->origem = PropostaOrigem::API;
        $proposta->versao = $versao;

        return $proposta;
    }
}
