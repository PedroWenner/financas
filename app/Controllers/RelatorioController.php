<?php
declare(strict_types=1);

class RelatorioController
{
    private RelatorioModel $model;

    public function __construct()
    {
        Auth::requireLogin();
        $this->model = new RelatorioModel();
    }

    /** GET /relatorios */
    public function index(): void
    {
        $uid      = Auth::id();
        $anoAtual = (int) date('Y');
        $mesAtual = (int) date('n');

        $modo = Request::getString('modo') ?: 'anual';
        if (!in_array($modo, ['anual', 'mensal', 'periodo'])) {
            $modo = 'anual';
        }

        // Resolve period bounds
        switch ($modo) {
            case 'mensal':
                $anoIni = $anoFim = max(2000, min(2100, Request::getInt('ano', $anoAtual)));
                $mesIni = $mesFim = max(1, min(12, Request::getInt('mes', $mesAtual)));
                break;

            case 'periodo':
                $anoIni = max(2000, min(2100, Request::getInt('ano_inicio', $anoAtual)));
                $mesIni = max(1, min(12, Request::getInt('mes_inicio', 1)));
                $anoFim = max(2000, min(2100, Request::getInt('ano_fim', $anoAtual)));
                $mesFim = max(1, min(12, Request::getInt('mes_fim', $mesAtual)));
                // Ensure chronological order
                if ($anoIni * 100 + $mesIni > $anoFim * 100 + $mesFim) {
                    [$anoIni, $mesIni, $anoFim, $mesFim] = [$anoFim, $mesFim, $anoIni, $mesIni];
                }
                break;

            default: // anual
                $anoIni = $anoFim = max(2000, min(2100, Request::getInt('ano', $anoAtual)));
                $mesIni = 1;
                $mesFim = 12;
                break;
        }

        // Tipo filter
        $tiposParam = isset($_GET['tipos']) && is_array($_GET['tipos'])
            ? $_GET['tipos']
            : ['fixa', 'variavel', 'parcelada'];
        $tipos = array_values(array_intersect($tiposParam, ['fixa', 'variavel', 'parcelada']));
        if (empty($tipos)) {
            $tipos = ['fixa', 'variavel', 'parcelada'];
        }

        // Card filter
        $modoCartao = Request::getString('cartao') ?: 'todos';
        if (!in_array($modoCartao, ['todos', 'apenas_cartao', 'sem_cartao'])) {
            $modoCartao = 'todos';
        }

        // Fetch
        $despesasMes  = $this->model->despesasPorMes($uid, $anoIni, $mesIni, $anoFim, $mesFim, $tipos, $modoCartao);
        $receitasMes  = $this->model->receitasPorMes($uid, $anoIni, $mesIni, $anoFim, $mesFim);
        $porCategoria = $this->model->despesasPorCategoria($uid, $anoIni, $mesIni, $anoFim, $mesFim, $tipos, $modoCartao);
        $porTipo      = $this->model->despesasPorTipo($uid, $anoIni, $mesIni, $anoFim, $mesFim, $modoCartao);

        $series        = $this->buildSeries($anoIni, $mesIni, $anoFim, $mesFim, $receitasMes, $despesasMes);
        $totalReceitas = array_sum(array_column($receitasMes, 'total'));
        $totalDespesas = array_sum(array_column($despesasMes, 'total'));
        $saldo         = $totalReceitas - $totalDespesas;

        Response::view('relatorios/index', [
            'titulo'        => 'Relatórios',
            'modo'          => $modo,
            'anoIni'        => $anoIni,
            'mesIni'        => $mesIni,
            'anoFim'        => $anoFim,
            'mesFim'        => $mesFim,
            'tipos'         => $tipos,
            'modoCartao'    => $modoCartao,
            'series'        => $series,
            'porCategoria'  => $porCategoria,
            'porTipo'       => $porTipo,
            'totalReceitas' => $totalReceitas,
            'totalDespesas' => $totalDespesas,
            'saldo'         => $saldo,
            'anoAtual'      => $anoAtual,
            'mesAtual'      => $mesAtual,
        ]);
    }

    /** Merges income and expense data into a complete month-by-month series. */
    private function buildSeries(
        int $anoIni, int $mesIni,
        int $anoFim, int $mesFim,
        array $receitas,
        array $despesas
    ): array {
        $recIdx  = [];
        foreach ($receitas as $r) {
            $recIdx[(int)$r['ano'] * 100 + (int)$r['mes']] = (float) $r['total'];
        }
        $despIdx = [];
        foreach ($despesas as $d) {
            $despIdx[(int)$d['ano'] * 100 + (int)$d['mes']] = (float) $d['total'];
        }

        $series = [];
        $cur    = $anoIni * 100 + $mesIni;
        $end    = $anoFim * 100 + $mesFim;

        while ($cur <= $end) {
            $ano     = intdiv($cur, 100);
            $mes     = $cur % 100;
            $rec     = $recIdx[$cur]  ?? 0.0;
            $desp    = $despIdx[$cur] ?? 0.0;
            $series[] = [
                'label'    => nomeMes($mes) . '/' . $ano,
                'receitas' => $rec,
                'despesas' => $desp,
                'saldo'    => $rec - $desp,
            ];
            $mes++;
            if ($mes > 12) { $mes = 1; $ano++; }
            $cur = $ano * 100 + $mes;
        }
        return $series;
    }
}
