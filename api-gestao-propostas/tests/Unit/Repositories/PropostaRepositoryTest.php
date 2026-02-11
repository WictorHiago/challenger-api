<?php

namespace Tests\Unit\Repositories;

use App\Enums\PropostaOrigem;
use App\Enums\PropostaStatus;
use App\Models\Cliente;
use App\Models\Proposta;
use App\Repositories\PropostaRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('busca-filtros')]
class PropostaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PropostaRepository $repository;

    private Cliente $cliente1;

    private Cliente $cliente2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PropostaRepository();

        $this->cliente1 = Cliente::query()->create([
            'nome' => 'Maria Silva',
            'email' => 'maria@example.com',
            'documento' => '52998224725',
        ]);

        $this->cliente2 = Cliente::query()->create([
            'nome' => 'João Santos',
            'email' => 'joao@example.com',
            'documento' => '01242576231',
        ]);
    }

    public function test_filtro_por_status_retorna_apenas_propostas_com_status_informado(): void
    {
        $this->criarProposta(clienteId: $this->cliente1->id, status: PropostaStatus::DRAFT, produto: 'Seguro A');
        $this->criarProposta(clienteId: $this->cliente1->id, status: PropostaStatus::SUBMITTED, produto: 'Seguro B');
        $this->criarProposta(clienteId: $this->cliente1->id, status: PropostaStatus::DRAFT, produto: 'Seguro C');

        $resultado = $this->repository->list(['status' => 'DRAFT'], 15);

        $this->assertCount(2, $resultado->items());
        $this->assertEquals(2, $resultado->total());
        foreach ($resultado as $proposta) {
            $this->assertSame(PropostaStatus::DRAFT, $proposta->status);
        }
    }

    public function test_filtro_por_cliente_id_retorna_apenas_propostas_do_cliente(): void
    {
        $this->criarProposta(clienteId: $this->cliente1->id, produto: 'Seguro 1');
        $this->criarProposta(clienteId: $this->cliente1->id, produto: 'Seguro 2');
        $this->criarProposta(clienteId: $this->cliente2->id, produto: 'Seguro 3');

        $resultado = $this->repository->list(['cliente_id' => $this->cliente1->id], 15);

        $this->assertCount(2, $resultado->items());
        $this->assertEquals(2, $resultado->total());
        foreach ($resultado as $proposta) {
            $this->assertEquals($this->cliente1->id, $proposta->cliente_id);
        }
    }

    public function test_filtro_por_produto_retorna_propostas_com_produto_contendo_termo(): void
    {
        $this->criarProposta(clienteId: $this->cliente1->id, produto: 'Seguro Residencial');
        $this->criarProposta(clienteId: $this->cliente1->id, produto: 'Seguro Automóvel');
        $this->criarProposta(clienteId: $this->cliente1->id, produto: 'Seguro Vida');

        $resultado = $this->repository->list(['produto' => 'Seguro'], 15);

        $this->assertCount(3, $resultado->items());

        $resultado = $this->repository->list(['produto' => 'Residencial'], 15);
        $this->assertCount(1, $resultado->items());
        $this->assertStringContainsString('Residencial', $resultado->first()->produto);
    }

    public function test_filtro_por_valor_min_e_valor_max(): void
    {
        $this->criarProposta(clienteId: $this->cliente1->id, valorMensal: 50);
        $this->criarProposta(clienteId: $this->cliente1->id, valorMensal: 100);
        $this->criarProposta(clienteId: $this->cliente1->id, valorMensal: 150);
        $this->criarProposta(clienteId: $this->cliente1->id, valorMensal: 200);

        $resultado = $this->repository->list([
            'valor_min' => 75,
            'valor_max' => 175,
        ], 15);

        $this->assertCount(2, $resultado->items());
        $this->assertEquals(2, $resultado->total());
        $valores = $resultado->pluck('valor_mensal')->map(fn ($v) => (float) $v)->toArray();
        $this->assertContains(100.0, $valores);
        $this->assertContains(150.0, $valores);
    }

    public function test_ordenacao_por_valor_mensal_ascendente(): void
    {
        $this->criarProposta(clienteId: $this->cliente1->id, valorMensal: 300, produto: 'C');
        $this->criarProposta(clienteId: $this->cliente1->id, valorMensal: 100, produto: 'A');
        $this->criarProposta(clienteId: $this->cliente1->id, valorMensal: 200, produto: 'B');

        $resultado = $this->repository->list([
            'ordenar_por' => 'valor_mensal',
            'direcao' => 'asc',
        ], 15);

        $itens = $resultado->items();
        $this->assertEquals(100, (float) $itens[0]->valor_mensal);
        $this->assertEquals(200, (float) $itens[1]->valor_mensal);
        $this->assertEquals(300, (float) $itens[2]->valor_mensal);
    }

    public function test_ordenacao_por_created_at_decrescente_por_padrao(): void
    {
        $p1 = $this->criarProposta(clienteId: $this->cliente1->id, produto: 'Primeiro');
        $this->travel(1)->seconds();
        $p2 = $this->criarProposta(clienteId: $this->cliente1->id, produto: 'Segundo');
        $this->travel(1)->seconds();
        $p3 = $this->criarProposta(clienteId: $this->cliente1->id, produto: 'Terceiro');

        $resultado = $this->repository->list([], 15);

        $itens = $resultado->items();
        $this->assertCount(3, $itens);
        $this->assertEquals($p3->id, $itens[0]->id);
        $this->assertEquals($p2->id, $itens[1]->id);
        $this->assertEquals($p1->id, $itens[2]->id);

        $this->travelBack();
    }

    public function test_paginacao_respeita_per_page(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->criarProposta(clienteId: $this->cliente1->id, produto: "Produto $i");
        }

        $resultado = $this->repository->list([], 2);

        $this->assertCount(2, $resultado->items());
        $this->assertEquals(5, $resultado->total());
        $this->assertEquals(3, $resultado->lastPage());
    }

    public function test_paginacao_retorna_estrutura_correta_links_e_meta(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->criarProposta(clienteId: $this->cliente1->id, produto: "Produto $i");
        }

        $resultado = $this->repository->list([], 2);

        $this->assertEquals(2, $resultado->perPage());
        $this->assertEquals(1, $resultado->currentPage());
        $this->assertEquals(3, $resultado->total());
        $this->assertEquals(2, $resultado->lastPage());
        $this->assertCount(2, $resultado->items());
    }

    public function test_listagem_inclui_relacionamento_cliente(): void
    {
        $this->criarProposta(clienteId: $this->cliente1->id, produto: 'Seguro');

        $resultado = $this->repository->list([], 15);

        $proposta = $resultado->first();
        $this->assertTrue($proposta->relationLoaded('cliente'));
        $this->assertEquals($this->cliente1->id, $proposta->cliente->id);
    }

    private function criarProposta(
        int $clienteId,
        string $produto = 'Seguro Teste',
        float $valorMensal = 100.0,
        PropostaStatus $status = PropostaStatus::DRAFT,
        PropostaOrigem $origem = PropostaOrigem::API
    ): Proposta {
        return Proposta::query()->create([
            'cliente_id' => $clienteId,
            'produto' => $produto,
            'valor_mensal' => $valorMensal,
            'status' => $status,
            'origem' => $origem,
            'versao' => 1,
        ]);
    }
}
