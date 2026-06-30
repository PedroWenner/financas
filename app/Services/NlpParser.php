<?php
declare(strict_types=1);

class NlpParser
{
    // keyword normalizado => prefixo do nome da categoria do usuário
    private const KEYWORD_CATEGORIA = [
        // Alimentação
        'mercado'        => 'alimenta',
        'supermercado'   => 'alimenta',
        'feira'          => 'alimenta',
        'hortifruti'     => 'alimenta',
        'mercearia'      => 'alimenta',
        'padaria'        => 'alimenta',
        'restaurante'    => 'alimenta',
        'lanche'         => 'alimenta',
        'pizza'          => 'alimenta',
        'hamburguer'     => 'alimenta',
        'hamburger'      => 'alimenta',
        'ifood'          => 'alimenta',
        'rappi'          => 'alimenta',
        'comida'         => 'alimenta',
        'almoco'         => 'alimenta',
        'jantar'         => 'alimenta',
        'cafe'           => 'alimenta',
        'cafeteria'      => 'alimenta',
        'salgado'        => 'alimenta',
        'marmita'        => 'alimenta',
        'acougue'        => 'alimenta',
        'churrasco'      => 'alimenta',
        // Transporte
        'uber'           => 'transport',
        'taxi'           => 'transport',
        'onibus'         => 'transport',
        'metro'          => 'transport',
        'combustivel'    => 'transport',
        'gasolina'       => 'transport',
        'etanol'         => 'transport',
        'diesel'         => 'transport',
        'posto'          => 'transport',
        'estacionamento' => 'transport',
        'pedagio'        => 'transport',
        'passagem'       => 'transport',
        'trem'           => 'transport',
        // Saúde
        'farmacia'       => 'saude',
        'remedio'        => 'saude',
        'medico'         => 'saude',
        'consulta'       => 'saude',
        'exame'          => 'saude',
        'hospital'       => 'saude',
        'dentista'       => 'saude',
        'academia'       => 'saude',
        'psicologo'      => 'saude',
        'fisioterapia'   => 'saude',
        'laboratorio'    => 'saude',
        'clinica'        => 'saude',
        // Moradia
        'aluguel'        => 'moradia',
        'condominio'     => 'moradia',
        'iptu'           => 'moradia',
        // Lazer
        'cinema'         => 'lazer',
        'teatro'         => 'lazer',
        'bar'            => 'lazer',
        'balada'         => 'lazer',
        'festa'          => 'lazer',
        'viagem'         => 'lazer',
        'hotel'          => 'lazer',
        'passeio'        => 'lazer',
        'praia'          => 'lazer',
        'parque'         => 'lazer',
        // Assinaturas / Streaming
        'netflix'        => 'assinatura',
        'spotify'        => 'assinatura',
        'disney'         => 'assinatura',
        'hbo'            => 'assinatura',
        'assinatura'     => 'assinatura',
        // Educação
        'escola'         => 'educacao',
        'faculdade'      => 'educacao',
        'curso'          => 'educacao',
        'livro'          => 'educacao',
        'mensalidade'    => 'educacao',
        'uniforme'       => 'educacao',
        'udemy'          => 'educacao',
        'alura'          => 'educacao',
        'material'       => 'educacao',
        // Compras
        'roupa'          => 'compra',
        'roupas'         => 'compra',
        'calcado'        => 'compra',
        'sapato'         => 'compra',
        'tenis'          => 'compra',
        'camisa'         => 'compra',
        'calca'          => 'compra',
        'celular'        => 'compra',
        'notebook'       => 'compra',
        'shein'          => 'compra',
        'shopee'         => 'compra',
        // Contas / Utilidades
        'luz'            => 'conta',
        'energia'        => 'conta',
        'agua'           => 'conta',
        'gas'            => 'conta',
        'internet'       => 'conta',
        'telefone'       => 'conta',
        'fatura'         => 'conta',
        // Investimentos / Receitas
        'investimento'   => 'investimento',
        'dividendo'      => 'investimento',
        'rendimento'     => 'investimento',
        'salario'        => 'salario',
        'freelance'      => 'renda',
        'freela'         => 'renda',
    ];

    private const FORMAS_PAGAMENTO = [
        'dinheiro' => 'Dinheiro',
        'debito'   => 'Débito',
        'credito'  => 'Crédito',
        'pix'      => 'Pix',
        'ted'      => 'TED',
        'boleto'   => 'Boleto',
        'outro'    => 'Outro',
    ];

    public function parsear(string $mensagem, array $categorias, array $cartoes): array
    {
        $tipo     = $this->detectarTipo($mensagem);
        $parcelas = ($tipo === 'parcelamento') ? $this->extrairParcelas($mensagem) : null;
        $valor    = $this->extrairValor($mensagem, $tipo, $parcelas);
        $data     = $this->extrairData($mensagem);
        $descricao = $this->extrairDescricao($mensagem);
        $categoria = $this->detectarCategoria($mensagem, $categorias, $tipo);
        $diaVenc  = ($tipo === 'despesa_fixa') ? $this->extrairDiaVencimento($mensagem) : null;
        $dataPrimParcela = $parcelas
            ? date('Y-m-d', strtotime('first day of next month'))
            : null;

        if ($valor === null) {
            return [
                'tipo'           => 'pergunta',
                'preview'        => 'Qual foi o valor da transação?',
                'descricao'      => '',
                'valor'          => 0,
                'data'           => $data,
                'categoria_id'   => null,
                'categoria_nome' => null,
            ];
        }

        return [
            'tipo'                  => $tipo,
            'descricao'             => $descricao,
            'valor'                 => $valor,
            'data'                  => $data,
            'categoria_id'          => $categoria['id']   ?? null,
            'categoria_nome'        => $categoria['nome'] ?? null,
            'cartao_id'             => null,
            'parcelas'              => $parcelas,
            'dia_vencimento'        => $diaVenc,
            'data_primeira_parcela' => $dataPrimParcela,
            'preview'               => $this->gerarPreview($tipo, $valor, $descricao, $categoria['nome'] ?? null, $data, $parcelas),
            // Campos extras para o fluxo de etapas no frontend (ignorados pelo confirmar)
            'cartoes_disponiveis'   => $cartoes,
            'formas_pagamento'      => self::FORMAS_PAGAMENTO,
        ];
    }

    private function detectarTipo(string $texto): string
    {
        // Parcelamento (mais específico — verifica primeiro)
        if (preg_match('/\d+\s*[xX×](?:\s|$)|em\s+\d+\s+(?:vezes|parcelas?|prestac[oõ]es?)|parcelei|parcelado/iu', $texto)) {
            return 'parcelamento';
        }

        // Despesa fixa
        if (preg_match('/todo[s]?\s+(?:os\s+)?m[eê]s|mensal(?:mente)?|todo\s+m[eê]s/iu', $texto)) {
            return 'despesa_fixa';
        }

        // Receita
        if (preg_match('/\b(recebi|ganhei|entrou|caiu\s+na\s+conta|salario|salário|freelance|freela|vendi|transferencia\s+recebida)\b/iu', $texto)) {
            return 'receita';
        }

        return 'despesa';
    }

    private function extrairValor(string $texto, string $tipo, ?int $parcelas): ?float
    {
        $valor = null;
        $matchStr = '';

        // R$1.500,00 ou R$ 1.500,00
        if (preg_match('/R?\$\s*(\d{1,3}(?:\.\d{3})*,\d{2})/i', $texto, $m)) {
            $valor    = (float) str_replace(['.', ','], ['', '.'], $m[1]);
            $matchStr = $m[0];
        }
        // R$50 ou R$ 50,50
        elseif (preg_match('/R?\$\s*(\d+(?:[.,]\d{1,2})?)/i', $texto, $m)) {
            $valor    = (float) str_replace(',', '.', $m[1]);
            $matchStr = $m[0];
        }
        // "50 reais", "50,50 reais"
        elseif (preg_match('/(\d{1,3}(?:\.\d{3})*(?:,\d{1,2})?|\d+(?:,\d{1,2})?)\s*(?:reais?|conto|pila)/i', $texto, $m)) {
            $valor    = (float) str_replace(['.', ','], ['', '.'], $m[1]);
            $matchStr = $m[0];
        }
        // Número avulso como último recurso
        elseif (preg_match('/\b(\d{1,5}(?:[.,]\d{1,2})?)\b/', $texto, $m)) {
            $valor    = (float) str_replace(',', '.', $m[1]);
            $matchStr = $m[0];
        }

        if ($valor === null) return null;

        // Para parcelamento: se o valor é total (não por parcela), divide pelo nº de parcelas
        if ($tipo === 'parcelamento' && $parcelas > 1 && !$this->valorEhPorParcela($texto, $matchStr)) {
            $valor = round($valor / $parcelas, 2);
        }

        return $valor;
    }

    // Retorna true se o valor encontrado vem logo após "Nx de" (indicando valor por parcela)
    private function valorEhPorParcela(string $texto, string $matchStr): bool
    {
        $pos = strpos($texto, $matchStr);
        if ($pos === false) return false;
        $before = substr($texto, 0, $pos);
        return (bool) preg_match('/\d+\s*[xX×]\s+(?:de\s+)?$|\d+\s+(?:vezes|parcelas?)\s+(?:de\s+)?$/iu', $before);
    }

    private function extrairParcelas(string $texto): int
    {
        if (preg_match('/(\d+)\s*[xX×](?:\s|$)/u', $texto, $m))                             return max(2, (int) $m[1]);
        if (preg_match('/(\d+)\s+(?:vezes|parcelas?|prestac[oõ]es?)/iu', $texto, $m))        return max(2, (int) $m[1]);
        if (preg_match('/em\s+(\d+)/iu', $texto, $m))                                         return max(2, (int) $m[1]);
        return 2;
    }

    private function extrairData(string $texto): string
    {
        $hoje = date('Y-m-d');
        $tn   = $this->normalizar($texto);

        if (str_contains($tn, 'hoje'))           return $hoje;
        if (str_contains($tn, 'ontem'))          return date('Y-m-d', strtotime('-1 day'));
        if (str_contains($tn, 'anteontem'))      return date('Y-m-d', strtotime('-2 days'));
        if (str_contains($tn, 'semana passada')) return date('Y-m-d', strtotime('-7 days'));

        // DD/MM/YYYY explícito
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/', $texto, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if (checkdate($mo, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
        }

        // DD/MM sem ano — usa o ano corrente
        if (preg_match('/\b(\d{1,2})\/(\d{1,2})\b/', $texto, $m)) {
            [$d, $mo] = [(int) $m[1], (int) $m[2]];
            $ano = (int) date('Y');
            if (checkdate($mo, $d, $ano)) {
                return sprintf('%04d-%02d-%02d', $ano, $mo, $d);
            }
        }

        // "dia 27" / "no dia 27"
        if (preg_match('/(?:no\s+)?dia\s+(\d{1,2})/u', $tn, $m)) {
            $dia = (int) $m[1];
            $mes = (int) date('n');
            $ano = (int) date('Y');
            if ($dia > (int) date('j')) { // dia futuro = mês passado
                $mes--;
                if ($mes < 1) { $mes = 12; $ano--; }
            }
            $maxDia = (int) date('t', mktime(0, 0, 0, $mes, 1, $ano));
            return sprintf('%04d-%02d-%02d', $ano, $mes, min($dia, $maxDia));
        }

        $diasSemana = [
            'segunda' => 'Monday',  'terca'  => 'Tuesday',  'quarta'  => 'Wednesday',
            'quinta'  => 'Thursday', 'sexta' => 'Friday',   'sabado'  => 'Saturday',
            'domingo' => 'Sunday',
        ];
        foreach ($diasSemana as $pt => $en) {
            if (str_contains($tn, $pt)) {
                $d = date('Y-m-d', strtotime("last {$en}"));
                return ($d === $hoje) ? date('Y-m-d', strtotime("last {$en} -1 week")) : $d;
            }
        }

        return $hoje;
    }

    private function extrairDiaVencimento(string $texto): int
    {
        // "vencimento em 27", "vence em 27", "vencimento em 27/07"
        if (preg_match('/(?:vence|vencimento)\s+(?:em\s+)?(\d{1,2})/iu', $texto, $m)) {
            return min(28, max(1, (int) $m[1]));
        }
        if (preg_match('/\bdia\s+(\d{1,2})/iu', $texto, $m)) {
            return min(28, max(1, (int) $m[1]));
        }
        // Fallback: extrai o dia do padrão DD/MM
        if (preg_match('/\b(\d{1,2})\/\d{1,2}\b/', $texto, $m)) {
            return min(28, max(1, (int) $m[1]));
        }
        return (int) date('d');
    }

    private function extrairDescricao(string $texto): string
    {
        $t = $texto;

        // Remove verbos de intenção
        $t = preg_replace('/\b(gastei|paguei|comprei|adquiri|recebi|ganhei|entrou|caiu|fui\s+ao?|comi|almocei|jantei|assinei|parcelei|transferi)\b/iu', '', $t);

        // Remove "será (de)?" e "com vencimento (em)?" / "vencimento (em)?"
        $t = preg_replace('/\bser[aá](?:\s+de)?\s*/iu', '', $t);
        $t = preg_replace('/\bcom\s+vencimento\s+(?:em\s+)?/iu', '', $t);
        $t = preg_replace('/\bvencimento\s+(?:em\s+)?/iu', '', $t);

        // Remove datas no formato DD/MM/YYYY e DD/MM
        $t = preg_replace('/\b\d{1,2}\/\d{1,2}(?:\/\d{2,4})?\b/', '', $t);

        // Remove padrões de valor (incluindo "de R$X")
        $t = preg_replace('/(?:de\s+)?R?\$\s*\d{1,3}(?:\.\d{3})*(?:,\d{2})?/i', '', $t);
        $t = preg_replace('/\d{1,3}(?:\.\d{3})*(?:,\d{1,2})?\s*(?:reais?|conto|pila)/iu', '', $t);

        // Remove padrões de parcelamento
        $t = preg_replace('/\d+\s*[xX×](?:\s|$)|\b\d+\s+(?:vezes|parcelas?|prestac[oõ]es?)\b/iu', '', $t);
        $t = preg_replace('/\bem\s+\d+\b/iu', '', $t);

        // Remove referências de data
        $t = preg_replace('/\b(?:hoje|ontem|anteontem|semana\s+passada|dia\s+\d{1,2}|segunda(?:-feira)?|ter[cç]a(?:-feira)?|quarta(?:-feira)?|quinta(?:-feira)?|sexta(?:-feira)?|s[aá]bado|domingo)\b/iu', '', $t);

        // Remove números avulsos remanescentes
        $t = preg_replace('/\b\d+(?:[.,]\d{1,2})?\b/', '', $t);

        // Remove preposições/artigos no início e conjunções soltas no fim
        $t = preg_replace('/^\s*(?:no?|na?|em|de|do|da|para|pro|pra|pelo|pela|num|numa|por|ao?|aos?|[aà]s?|uns?|uma?|com)\s+/iu', '', trim($t));
        $t = preg_replace('/\s+(?:no?|na?|em|de|do|da|para|pro|pra|com|por|ao?)$/iu', '', $t);

        $t = trim(preg_replace('/\s+/', ' ', $t));

        return $t ?: 'Lançamento via chat';
    }

    private function detectarCategoria(string $texto, array $categorias, string $tipo): ?array
    {
        if (empty($categorias)) return null;
        $norm = $this->normalizar($texto);

        foreach (self::KEYWORD_CATEGORIA as $keyword => $prefixo) {
            if (str_contains($norm, $keyword)) {
                $cat = $this->buscarCategoriaPorPrefixo($prefixo, $categorias, $tipo);
                if ($cat) return $cat;
            }
        }
        return null;
    }

    private function buscarCategoriaPorPrefixo(string $prefixo, array $categorias, string $tipo): ?array
    {
        $filtro = ($tipo === 'receita') ? ['receita', 'ambos'] : ['despesa', 'ambos'];
        foreach ($categorias as $cat) {
            if (!in_array($cat['tipo'], $filtro, true)) continue;
            if (str_contains($this->normalizar($cat['nome']), $prefixo)) return $cat;
        }
        return null;
    }

    private function gerarPreview(string $tipo, float $valor, string $desc, ?string $catNome, string $data, ?int $parcelas): string
    {
        $dataFmt  = date('d/m', strtotime($data));
        $valorFmt = 'R$ ' . number_format($valor, 2, ',', '.');
        $cat      = $catNome ? " ({$catNome})" : '';

        return match ($tipo) {
            'despesa'      => "Despesa de {$valorFmt} — {$desc}{$cat}, em {$dataFmt}",
            'receita'      => "Receita de {$valorFmt} — {$desc}{$cat}, em {$dataFmt}",
            'parcelamento' => "Parcelamento: {$desc} em " . ($parcelas ?? 2) . "x de {$valorFmt}",
            'despesa_fixa' => "Despesa fixa: {$desc}{$cat} de {$valorFmt}/mês",
            default        => "Registrar {$valorFmt} — {$desc}",
        };
    }

    private function normalizar(string $texto): string
    {
        $mapa = [
            'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c','ñ'=>'n',
            'Á'=>'a','À'=>'a','Â'=>'a','Ã'=>'a','Ä'=>'a',
            'É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
            'Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i',
            'Ó'=>'o','Ò'=>'o','Ô'=>'o','Õ'=>'o','Ö'=>'o',
            'Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u',
            'Ç'=>'c','Ñ'=>'n',
        ];
        return mb_strtolower(strtr($texto, $mapa), 'UTF-8');
    }
}
