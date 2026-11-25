<?php

namespace App\adms\Controllers;

use App\adms\Models\historico\AdmsHistoricoEmissao as Emissao;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * VERIFICAÇÃO PÚBLICA do histórico (E6b; rota 402, lib_pub — SEM login). Endereço curto:
 * <site>/verificar/<código> (o index.php da raiz redireciona para cá).
 *
 * Mostra o MÍNIMO: situação, tipo, data de emissão, instituição, curso, nome do aluno, CPF
 * mascarado e o hash. Recalcula o hash de snapshot+HTML gravados (acusa banco alterado) e
 * confere o PDF enviado pelo sha256 (o arquivo é só lido, nunca gravado). Código inexistente:
 * mensagem neutra. Sem listagem e sem busca por nome.
 */
class Verificar
{
    public const MAX_PDF = 10485760;

    public function codigo($dadosId = null): void
    {
        $digitado = filter_input(INPUT_GET, 'c');
        if ($digitado !== null && $digitado !== false) {
            $c = Emissao::normalizarCodigo((string) $digitado);
            /* E8c (A18): o formulário leva ao endereço curto <site>/verificar/<código> */
            header('Location: ' . ($c !== '' ? URL . 'verificar/' . $c : URLADM . 'verificar/codigo?invalido=1'));
            return;
        }
        $codigo = Emissao::normalizarCodigo((string) $dadosId);
        $d = ['informado' => (string) $dadosId !== '' || filter_input(INPUT_GET, 'invalido'), 'codigo' => $codigo, 'e' => null, 'conferencia' => null];
        /* E7 (D-73): cada consulta com código (e cada envio de PDF) conta por IP. Passado o limite, a resposta é
           a MESMA de código inexistente — não vira oráculo de códigos nem diz que houve bloqueio. */
        $e = null;
        if ((string) $dadosId !== '') {
            $espera = \App\adms\Models\helper\AdmsLimiteTentativas::espera('verificacao', null);
            if ($espera > 0) {
                \App\adms\Models\helper\AdmsLimiteTentativas::logBloqueio('verificacao', null, $espera);
            } else {
                \App\adms\Models\helper\AdmsLimiteTentativas::registrar('verificacao', null);
                $e = $codigo !== '' ? Emissao::porCodigo($codigo) : null;
            }
        }
        if ($e) {
            $snap = json_decode((string) $e['snapshot'], true) ?: [];
            $d['e'] = [
                'valida' => (int) $e['situacao'] === 1, 'canceladaEm' => $e['cancelado_em'], 'tipo' => $e['tipo'], 'emitidoEm' => $e['emitido_em'],
                'instituicao' => $snap['vida']['instituicao']['nome'] ?? '—', 'curso' => $snap['vida']['matricula']['curso'] ?? '—',
                'aluno' => $snap['vida']['aluno']['nome'] ?? '—', 'cpf' => Emissao::cpfMascarado($snap['vida']['aluno']['cpf'] ?? null),
                'hash' => $e['hash_conteudo'], 'integra' => Emissao::integra($e), 'codigo' => $e['codigo'], 'ambiente' => (string) ($e['ambiente'] ?? 'producao'),
            ];
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_FILES['pdf'])) {
                $f = $_FILES['pdf'];
                if ((int) $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $f['tmp_name'])) {
                    $d['conferencia'] = ['ok' => false, 'msg' => 'O arquivo não foi recebido (até 10 MB).'];
                } elseif (filesize((string) $f['tmp_name']) > self::MAX_PDF) {
                    $d['conferencia'] = ['ok' => false, 'msg' => 'O arquivo passa de 10 MB.'];
                } else {
                    $qual = Emissao::conferirPdf($e, hash_file('sha256', (string) $f['tmp_name']));
                    $d['conferencia'] = $qual
                        ? ['ok' => true, 'msg' => 'O arquivo enviado é idêntico ' . $qual . ' emitido(a) com este código.']
                        : ['ok' => false, 'msg' => 'O arquivo enviado NÃO é idêntico a nenhum documento emitido com este código: ele pode ter sido alterado.'];
                }
            }
        }
        header('X-Robots-Tag: noindex, nofollow');
        header('X-Content-Type-Options: nosniff');
        /* E7: a verificação pública NÃO é embutível (frame-ancestors 'none' + X-Frame-Options DENY, de Core\Seguranca) */
        /* E8c (A18): o topo mostra a instituição DONA da plataforma (logo e nome), com o logo embutido (sem rede) */
        $doc = \App\adms\Models\historico\AdmsHistoricoDocumento::class;
        $d['instituicaoNome'] = \App\adms\Models\helper\AdmsInstituicao::nome();
        $d['instituicaoLogo'] = $doc::dataUri($doc::arquivoLogo());
        $this->dados = $d;
        include 'app/adms/Views/verificar/verificar.php';
    }

    private $dados = [];
}
