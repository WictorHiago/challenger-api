<?php

namespace Tests\Unit\Enums;

use App\Enums\PropostaStatus;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('transicao-status')]
class PropostaStatusTest extends TestCase
{
    public function test_draft_transiciona_para_submitted(): void
    {
        $this->assertTrue(PropostaStatus::DRAFT->podeTransicionarPara(PropostaStatus::SUBMITTED));
    }

    public function test_draft_nao_transiciona_para_approved(): void
    {
        $this->assertFalse(PropostaStatus::DRAFT->podeTransicionarPara(PropostaStatus::APPROVED));
    }

    public function test_draft_nao_transiciona_para_rejected(): void
    {
        $this->assertFalse(PropostaStatus::DRAFT->podeTransicionarPara(PropostaStatus::REJECTED));
    }

    public function test_draft_nao_transiciona_para_canceled(): void
    {
        $this->assertFalse(PropostaStatus::DRAFT->podeTransicionarPara(PropostaStatus::CANCELED));
    }

    public function test_submitted_transiciona_para_approved(): void
    {
        $this->assertTrue(PropostaStatus::SUBMITTED->podeTransicionarPara(PropostaStatus::APPROVED));
    }

    public function test_submitted_transiciona_para_rejected(): void
    {
        $this->assertTrue(PropostaStatus::SUBMITTED->podeTransicionarPara(PropostaStatus::REJECTED));
    }

    public function test_submitted_transiciona_para_canceled(): void
    {
        $this->assertTrue(PropostaStatus::SUBMITTED->podeTransicionarPara(PropostaStatus::CANCELED));
    }

    public function test_submitted_nao_transiciona_para_draft(): void
    {
        $this->assertFalse(PropostaStatus::SUBMITTED->podeTransicionarPara(PropostaStatus::DRAFT));
    }

    public function test_approved_nao_transiciona_para_nenhum(): void
    {
        $this->assertFalse(PropostaStatus::APPROVED->podeTransicionarPara(PropostaStatus::DRAFT));
        $this->assertFalse(PropostaStatus::APPROVED->podeTransicionarPara(PropostaStatus::SUBMITTED));
        $this->assertFalse(PropostaStatus::APPROVED->podeTransicionarPara(PropostaStatus::APPROVED));
        $this->assertFalse(PropostaStatus::APPROVED->podeTransicionarPara(PropostaStatus::REJECTED));
        $this->assertFalse(PropostaStatus::APPROVED->podeTransicionarPara(PropostaStatus::CANCELED));
    }

    public function test_rejected_nao_transiciona_para_nenhum(): void
    {
        $this->assertFalse(PropostaStatus::REJECTED->podeTransicionarPara(PropostaStatus::SUBMITTED));
        $this->assertFalse(PropostaStatus::REJECTED->podeTransicionarPara(PropostaStatus::APPROVED));
    }

    public function test_canceled_nao_transiciona_para_nenhum(): void
    {
        $this->assertFalse(PropostaStatus::CANCELED->podeTransicionarPara(PropostaStatus::SUBMITTED));
        $this->assertFalse(PropostaStatus::CANCELED->podeTransicionarPara(PropostaStatus::APPROVED));
    }

    public function test_is_final_retorna_true_para_approved_rejected_canceled(): void
    {
        $this->assertTrue(PropostaStatus::APPROVED->isFinal());
        $this->assertTrue(PropostaStatus::REJECTED->isFinal());
        $this->assertTrue(PropostaStatus::CANCELED->isFinal());
    }

    public function test_is_final_retorna_false_para_draft_e_submitted(): void
    {
        $this->assertFalse(PropostaStatus::DRAFT->isFinal());
        $this->assertFalse(PropostaStatus::SUBMITTED->isFinal());
    }

    public function test_transicoes_permitidas_draft_retorna_apenas_submitted(): void
    {
        $transicoes = PropostaStatus::DRAFT->transicoesPermitidas();

        $this->assertCount(1, $transicoes);
        $this->assertContains(PropostaStatus::SUBMITTED, $transicoes);
    }

    public function test_transicoes_permitidas_submitted_retorna_approved_rejected_canceled(): void
    {
        $transicoes = PropostaStatus::SUBMITTED->transicoesPermitidas();

        $this->assertCount(3, $transicoes);
        $this->assertContains(PropostaStatus::APPROVED, $transicoes);
        $this->assertContains(PropostaStatus::REJECTED, $transicoes);
        $this->assertContains(PropostaStatus::CANCELED, $transicoes);
    }

    public function test_transicoes_permitidas_estados_finais_retorna_vazio(): void
    {
        $this->assertEmpty(PropostaStatus::APPROVED->transicoesPermitidas());
        $this->assertEmpty(PropostaStatus::REJECTED->transicoesPermitidas());
        $this->assertEmpty(PropostaStatus::CANCELED->transicoesPermitidas());
    }

    public function test_labels(): void
    {
        $this->assertSame('Rascunho', PropostaStatus::DRAFT->label());
        $this->assertSame('Submetida', PropostaStatus::SUBMITTED->label());
        $this->assertSame('Aprovada', PropostaStatus::APPROVED->label());
        $this->assertSame('Rejeitada', PropostaStatus::REJECTED->label());
        $this->assertSame('Cancelada', PropostaStatus::CANCELED->label());
    }
}
