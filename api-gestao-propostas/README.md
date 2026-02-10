# API Gestão de Propostas

API REST para gestão de clientes e propostas, construída com Laravel 11.

## Requisitos

- PHP 8.3+
- Composer
- PostgreSQL 16
- Redis 7

## Setup

### Opção 1: Docker (recomendado)

Na raiz do projeto (`challenger-api/`):

```bash
# Subir containers (PostgreSQL, Redis, RedisInsight)
docker-compose up -d postgres redis redisinsight

# O app pode rodar localmente ou via Docker
# Para rodar o app via Docker:
docker-compose up -d app
```

### Opção 2: Local (sem Docker para o app)

1. Suba PostgreSQL e Redis (via Docker ou instalados localmente).

2. Copie o `.env` e configure:
```bash
cp .env.example .env
php artisan key:generate
```

3. Configure no `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=propostas_db
DB_USERNAME=propostas_user
DB_PASSWORD=propostas_pass

CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_CLIENT=predis
```

4. Instale dependências e rode migrations:
```bash
composer install
php artisan migrate
php artisan db:seed
```

5. Inicie o servidor:
```bash
php artisan serve
```

A API estará em `http://localhost:8000`.

## Migrations

```bash
# Executar migrations
php artisan migrate

# Rollback (reverter última migration)
php artisan migrate:rollback

# Status das migrations
php artisan migrate:status
```

## Seeders

Popular o banco com dados de teste:

```bash
php artisan db:seed
```

Isso cria clientes e propostas de exemplo. Use `firstOrCreate` para evitar duplicatas ao reexecutar.

## Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | /api/v1/clientes | Criar cliente (Idempotency-Key opcional) |
| GET | /api/v1/clientes/{id} | Obter cliente |
| POST | /api/v1/propostas | Criar proposta |
| GET | /api/v1/propostas | Listar propostas (filtros, paginação) |
| GET | /api/v1/propostas/{id} | Obter proposta |
| PATCH | /api/v1/propostas/{id} | Atualizar proposta (versão obrigatória) |
| POST | /api/v1/propostas/{id}/submit | Submeter proposta |
| POST | /api/v1/propostas/{id}/approve | Aprovar proposta |
| POST | /api/v1/propostas/{id}/reject | Rejeitar proposta |
| POST | /api/v1/propostas/{id}/cancel | Cancelar proposta |
| GET | /api/v1/propostas/{id}/auditoria | Histórico de auditoria |

### Filtros da listagem de propostas

- `status` — DRAFT, SUBMITTED, APPROVED, REJECTED, CANCELED
- `cliente_id` — ID do cliente
- `produto` — Busca parcial (ILIKE)
- `valor_min` — Valor mensal mínimo
- `valor_max` — Valor mensal máximo
- `ordenar_por` — created_at, valor_mensal, produto, etc.
- `direcao` — asc ou desc
- `per_page` — Itens por página (máx. 100, default 15)

## Documentação Swagger

Com o servidor rodando, acesse:

```
http://localhost:8000/api/documentation
```

## Padrão de erros

Respostas de erro seguem o formato:

```json
{
  "message": "Mensagem descritiva",
  "errors": {}
}
```

Códigos HTTP: 400 (validação), 404 (não encontrado), 409 (conflito de versão), 422 (regra de negócio).
