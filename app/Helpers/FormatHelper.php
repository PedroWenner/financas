<?php
/**
 * FormatHelper — Funções de formatação e escape.
 * Todas as funções são globais para uso direto nas views.
 */

declare(strict_types=1);

/** Escapa HTML para exibição segura (anti-XSS). */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Formata valor como moeda BRL. Ex: 1234.56 → R$ 1.234,56 */
function moeda(float|string $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/** Formata data do banco (Y-m-d) para exibição (d/m/Y). */
function dataFmt(string $data): string
{
    if (empty($data) || $data === '0000-00-00') return '—';
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $data);
    return $d ? $d->format('d/m/Y') : $data;
}

/** Formata data e hora do banco para exibição. */
function dataHoraFmt(string $dataHora): string
{
    if (empty($dataHora)) return '—';
    $d = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dataHora);
    return $d ? $d->format('d/m/Y H:i') : $dataHora;
}

/** Retorna nome do mês em português. */
function nomeMes(int $mes): string
{
    $meses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
        4 => 'Abril',   5 => 'Maio',      6 => 'Junho',
        7 => 'Julho',   8 => 'Agosto',    9 => 'Setembro',
        10=> 'Outubro', 11=> 'Novembro',  12=> 'Dezembro',
    ];
    return $meses[$mes] ?? '';
}

/** Mês/Ano formatado. Ex: nomeMesAno(5, 2026) → "Maio 2026" */
function nomeMesAno(int $mes, int $ano): string
{
    return nomeMes($mes) . ' ' . $ano;
}

/** Retorna badge HTML para tipo de despesa. */
function badgeTipo(string $tipo): string
{
    $map = [
        'fixa'      => ['Fixa',      'badge-warning'],
        'variavel'  => ['Variável',  'badge-info'],
        'parcelada' => ['Parcelada', 'badge-primary'],
    ];
    [$label, $class] = $map[$tipo] ?? [$tipo, 'badge-secondary'];
    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

/** Retorna badge HTML para status pago/pendente. */
function badgePago(bool $pago): string
{
    return $pago
        ? '<span class="badge badge-success">Pago</span>'
        : '<span class="badge badge-danger">Pendente</span>';
}

/** Retorna ícone SVG da bandeira do cartão. */
function iconeBandeira(string $bandeira): string
{
    $icons = [
        'visa'       => '💳',
        'mastercard' => '💳',
        'elo'        => '💳',
        'amex'       => '💳',
        'hipercard'  => '💳',
        'outro'      => '💳',
    ];
    return $icons[$bandeira] ?? '💳';
}

/** Formata tipo de receita para exibição. */
function tipoReceitaLabel(string $tipo): string
{
    $map = [
        'salario'         => 'Salário',
        'salario_conjuge' => 'Salário do Cônjuge',
        'renda_extra'     => 'Renda Extra',
        'investimento'    => 'Investimento',
        'outro'           => 'Outro',
    ];
    return $map[$tipo] ?? $tipo;
}

/** Trunca texto longo. */
function truncate(string $texto, int $limite = 40): string
{
    return mb_strlen($texto) > $limite
        ? mb_substr($texto, 0, $limite) . '…'
        : $texto;
}

/** Gera classe CSS de cor com base no percentual (para barras de progresso). */
function corProgresso(float $percentual): string
{
    if ($percentual >= 90) return 'danger';
    if ($percentual >= 70) return 'warning';
    return 'success';
}

/** Só usuários cujo e-mail está em SQL_CONSOLE_ADMINS podem acessar o console SQL (/admin/sql). */
function podeAcessarSqlConsole(): bool
{
    if (!Auth::check()) return false;
    return in_array(mb_strtolower(Auth::email()), array_map('mb_strtolower', SQL_CONSOLE_ADMINS), true);
}
