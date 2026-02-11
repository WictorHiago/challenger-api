<?php

namespace Tests\Unit\Actions\Cliente;

/**
 * @group idempotencia
 */
use App\Actions\Cliente\CriarClienteAction;
use App\Contracts\ClienteRepositoryInterface;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class CriarClienteActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_retorna_cliente_existente_quando_idempotency_key_ja_processada(): void
    {
        $clienteExistente = $this->criarCliente(id: 1, email: 'joao@example.com');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')->with('Idempotency-Key')->andReturn('chave-idempotencia-123');
        $request->shouldNotReceive('validated');

        $repository = Mockery::mock(ClienteRepositoryInterface::class);
        $repository->shouldReceive('findByIdempotencyKey')
            ->once()
            ->with('chave-idempotencia-123')
            ->andReturn($clienteExistente);
        $repository->shouldNotReceive('create');
        $repository->shouldNotReceive('storeIdempotencyKey');

        $action = new CriarClienteAction($repository);

        $resultado = $action->execute($request);

        $this->assertSame($clienteExistente, $resultado);
    }

    public function test_cria_cliente_quando_sem_idempotency_key(): void
    {
        $data = [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'documento' => '52998224725',
        ];
        $clienteNovo = $this->criarCliente(id: 1, email: 'joao@example.com');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')->with('Idempotency-Key')->andReturn(null);
        $request->shouldReceive('validated')->andReturn($data);

        $repository = Mockery::mock(ClienteRepositoryInterface::class);
        $repository->shouldNotReceive('findByIdempotencyKey');
        $repository->shouldReceive('create')
            ->once()
            ->with([
                'nome' => 'João Silva',
                'email' => 'joao@example.com',
                'documento' => '52998224725',
            ])
            ->andReturn($clienteNovo);
        $repository->shouldNotReceive('storeIdempotencyKey');

        $action = new CriarClienteAction($repository);

        $resultado = $action->execute($request);

        $this->assertSame($clienteNovo, $resultado);
    }

    public function test_cria_cliente_e_armazena_idempotency_key_quando_chave_nova(): void
    {
        $data = [
            'nome' => 'Maria Santos',
            'email' => 'maria@example.com',
            'documento' => '012.425.762-31',
        ];
        $clienteNovo = $this->criarCliente(id: 2, email: 'maria@example.com');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')->with('Idempotency-Key')->andReturn('chave-nova-456');
        $request->shouldReceive('validated')->andReturn($data);

        $repository = Mockery::mock(ClienteRepositoryInterface::class);
        $repository->shouldReceive('findByIdempotencyKey')
            ->once()
            ->with('chave-nova-456')
            ->andReturn(null);
        $repository->shouldReceive('create')
            ->once()
            ->with([
                'nome' => 'Maria Santos',
                'email' => 'maria@example.com',
                'documento' => '01242576231',
            ])
            ->andReturn($clienteNovo);
        $repository->shouldReceive('storeIdempotencyKey')
            ->once()
            ->with('chave-nova-456', 2);

        $action = new CriarClienteAction($repository);

        $resultado = $action->execute($request);

        $this->assertSame($clienteNovo, $resultado);
    }

    public function test_normaliza_documento_removendo_nao_numericos(): void
    {
        $data = [
            'nome' => 'Pedro Costa',
            'email' => 'pedro@example.com',
            'documento' => '123.456.789-01',
        ];
        $clienteNovo = $this->criarCliente(id: 3, email: 'pedro@example.com');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')->with('Idempotency-Key')->andReturn('');
        $request->shouldReceive('validated')->andReturn($data);

        $repository = Mockery::mock(ClienteRepositoryInterface::class);
        $repository->shouldNotReceive('findByIdempotencyKey');
        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $received) {
                return $received['documento'] === '12345678901'
                    && $received['nome'] === 'Pedro Costa'
                    && $received['email'] === 'pedro@example.com';
            }))
            ->andReturn($clienteNovo);

        $action = new CriarClienteAction($repository);

        $resultado = $action->execute($request);

        $this->assertSame($clienteNovo, $resultado);
    }

    private function criarCliente(int $id = 1, string $nome = 'Cliente', string $email = 'cliente@example.com', string $documento = '52998224725'): Cliente
    {
        $cliente = new Cliente();
        $cliente->id = $id;
        $cliente->nome = $nome;
        $cliente->email = $email;
        $cliente->documento = $documento;

        return $cliente;
    }
}
