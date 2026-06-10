<?php
declare(strict_types=1);

class ChatController
{
    private CategoriaModel $categorias;
    private CartaoModel    $cartoes;

    public function __construct()
    {
        Auth::requireLogin();
        $this->categorias = new CategoriaModel();
        $this->cartoes    = new CartaoModel();
    }

    /** POST /api/chat */
    public function send(): void
    {
        $uid     = Auth::id();
        $message = trim(Request::post('message', ''));

        if ($message === '') {
            Response::json(['erro' => 'Mensagem vazia.'], 400);
            return;
        }

        // [GROQ] Verificação da chave de API — substituída pelo NlpParser local
        // $apiKey = GROQ_API_KEY;
        // if (empty($apiKey)) {
        //     Response::json(['erro' => 'API de IA não configurada. Adicione GROQ_API_KEY no .env'], 503);
        //     return;
        // }

        $categorias = $this->categorias->listar($uid);
        $cartoes    = $this->cartoes->listar($uid);

        // [GROQ] Chamada à API do Groq — substituída pelo parser local abaixo
        // $prompt    = $this->buildPrompt($message, $categorias, $cartoes);
        // try {
        //     $resultado = $this->chamarIA($apiKey, $prompt);
        //     Response::json($resultado);
        // } catch (\Exception $e) {
        //     Response::json(['erro' => 'Erro ao consultar IA: ' . $e->getMessage()], 500);
        // }

        $parser    = new NlpParser();
        $resultado = $parser->parsear($message, $categorias, $cartoes);
        Response::json($resultado);
    }

    /** POST /api/chat/confirmar */
    public function confirmar(): void
    {
        $token = Request::post('_csrf_token', '');
        if (!CsrfHelper::validate($token)) {
            Response::json(['erro' => 'Token inválido. Recarregue a página.'], 403);
            return;
        }

        $uid   = Auth::id();
        $raw   = Request::post('dados', '{}');
        $dados = json_decode($raw, true);

        if (!is_array($dados) || empty($dados['tipo'])) {
            Response::json(['erro' => 'Dados inválidos.'], 400);
            return;
        }

        try {
            $id = $this->salvar($uid, $dados);
            Response::json(['sucesso' => true, 'id' => $id, 'mensagem' => $this->mensagemSucesso($dados['tipo'])]);
        } catch (\Exception $e) {
            Response::json(['erro' => 'Erro ao salvar: ' . $e->getMessage()], 500);
        }
    }

    private function salvar(int $uid, array $d): int
    {
        return match ($d['tipo']) {
            'despesa' => (new DespesaModel())->criar($uid, [
                'categoria_id'    => $d['categoria_id']    ?: null,
                'cartao_id'       => $d['cartao_id']       ?: null,
                'descricao'       => $d['descricao']       ?? 'Lançamento via chat',
                'valor'           => (float) ($d['valor']  ?? 0),
                'data'            => $d['data']            ?? date('Y-m-d'),
                'tipo'            => 'variavel',
                // Usa forma_pagamento escolhida pelo usuário; fallback para lógica anterior se ausente
                'forma_pagamento' => $d['forma_pagamento'] ?? (!empty($d['cartao_id']) ? 'credito' : 'outro'),
                'pago'            => (int) ($d['pago'] ?? 0),
                'data_pagamento'  => !empty($d['pago']) ? ($d['data_pagamento'] ?? $d['data'] ?? date('Y-m-d')) : null,
                'observacoes'     => $d['observacoes'] ?: null,
            ]),

            'receita' => (new ReceitaModel())->criar($uid, [
                'categoria_id' => $d['categoria_id'] ?: null,
                'descricao'    => $d['descricao']    ?? 'Receita via chat',
                'valor'        => (float) ($d['valor'] ?? 0),
                'data'         => $d['data']         ?? date('Y-m-d'),
                'tipo_receita' => 'outra',
                'observacoes'  => $d['observacoes'] ?: null,
            ]),

            'parcelamento' => (new ParcelamentoModel())->criarComParcelas($uid, [
                'cartao_id'             => $d['cartao_id']    ?: null,
                'categoria_id'          => $d['categoria_id'] ?: null,
                'descricao'             => $d['descricao']    ?? 'Parcelamento via chat',
                // valor = por parcela (NlpParser já converte); valor_total = valor * parcelas
                'valor_total'           => round((float) ($d['valor'] ?? 0) * (int) ($d['parcelas'] ?? 2), 2),
                'numero_parcelas'       => (int) ($d['parcelas'] ?? 2),
                'data_compra'           => $d['data']         ?? date('Y-m-d'),
                'data_primeira_parcela' => $d['data_primeira_parcela']
                    ?? date('Y-m-d', strtotime('first day of next month')),
                'observacoes'           => $d['observacoes'] ?: null,
            ]),

            'despesa_fixa' => (new DespesaFixaModel())->criar($uid, [
                'categoria_id'   => $d['categoria_id']   ?: null,
                'descricao'      => $d['descricao']      ?? 'Despesa fixa via chat',
                'valor'          => (float) ($d['valor'] ?? 0),
                'dia_vencimento' => (int) ($d['dia_vencimento'] ?? (int) date('d')),
                'data_inicio'    => date('Y-m-01'),
                'data_fim'       => null,
                'observacoes'    => $d['observacoes'] ?: null,
            ]),

            default => throw new \Exception("Tipo desconhecido: {$d['tipo']}")
        };
    }

    private function mensagemSucesso(string $tipo): string
    {
        return match ($tipo) {
            'despesa'      => 'Despesa registrada com sucesso!',
            'receita'      => 'Receita registrada com sucesso!',
            'parcelamento' => 'Parcelamento criado com sucesso!',
            'despesa_fixa' => 'Despesa fixa cadastrada com sucesso!',
            default        => 'Registrado com sucesso!',
        };
    }

    // ──────────────────────────────────────────────────────────────────
    // [GROQ] Métodos abaixo são da integração com Groq — mantidos para referência
    // ──────────────────────────────────────────────────────────────────

    // private function buildPrompt(string $mensagem, array $categorias, array $cartoes): string
    // {
    //     $hoje  = date('Y-m-d');
    //     $ontem = date('Y-m-d', strtotime('-1 day'));
    //
    //     $listaCats = empty($categorias)
    //         ? '  (nenhuma categoria cadastrada)'
    //         : implode("\n", array_map(
    //             fn($c) => "  - id={$c['id']}, nome=\"{$c['nome']}\", tipo={$c['tipo']}",
    //             $categorias
    //         ));
    //
    //     $listaCartoes = empty($cartoes)
    //         ? '  (nenhum cartão cadastrado)'
    //         : implode("\n", array_map(
    //             fn($c) => "  - id={$c['id']}, nome=\"{$c['nome']}\"",
    //             $cartoes
    //         ));
    //
    //     return <<<PROMPT
    // Você é um assistente financeiro pessoal. O usuário vai descrever uma transação em linguagem natural em português.
    // Sua ÚNICA tarefa é retornar um JSON válido, sem markdown, sem blocos de código, sem texto extra.
    //
    // Data de hoje: {$hoje}
    // Ontem: {$ontem}
    //
    // Categorias disponíveis:
    // {$listaCats}
    //
    // Cartões disponíveis:
    // {$listaCartoes}
    //
    // Schema JSON de resposta (todos os campos são obrigatórios):
    // {
    //   "tipo": "despesa" | "receita" | "parcelamento" | "despesa_fixa" | "pergunta",
    //   "descricao": string,
    //   "valor": number (valor de CADA PARCELA se parcelamento; 0 se for pergunta),
    //   "data": "YYYY-MM-DD",
    //   "categoria_id": number | null,
    //   "categoria_nome": string | null,
    //   "cartao_id": number | null,
    //   "parcelas": number | null,
    //   "dia_vencimento": number | null,
    //   "data_primeira_parcela": "YYYY-MM-DD" | null,
    //   "preview": string
    // }
    //
    // Regras:
    // - "despesa": compras pontuais (mercado, restaurante, combustível, farmácia...)
    // - "receita": entradas de dinheiro (salário, freelance, venda, transferência recebida...)
    // - "parcelamento": quando mencionar parcelas, prestações, "vezes", "x de"
    // - "despesa_fixa": contas mensais recorrentes (aluguel, luz, internet, academia...)
    // - "pergunta": SOMENTE se faltar valor ou tipo da transação — peça apenas o que falta
    // - Para "parcelamento": "valor" = valor de CADA parcela; calcule "data_primeira_parcela" como primeiro dia do mês seguinte à "data"
    // - Escolha categoria da lista pelo nome mais parecido; null se nenhuma se encaixa
    // - Identifique cartão pelo nome; null se não mencionado ou não encontrado
    // - "preview" deve ser amigável, ex: "Registrar despesa de R$ 80,00 em Alimentação, em 07/06?" ou "Criar parcelamento de TV (12x R$ 350,00) no Nubank, início em 01/07?"
    //
    // Mensagem do usuário: {$mensagem}
    // PROMPT;
    // }

    // private function chamarIA(string $apiKey, string $prompt): array
    // {
    //     $url  = 'https://api.groq.com/openai/v1/chat/completions';
    //     $body = json_encode([
    //         'model'       => 'llama-3.3-70b-versatile',
    //         'messages'    => [['role' => 'user', 'content' => $prompt]],
    //         'temperature' => 0.1,
    //         'max_tokens'  => 512,
    //     ]);
    //
    //     $ch = curl_init($url);
    //     curl_setopt_array($ch, [
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_POST           => true,
    //         CURLOPT_POSTFIELDS     => $body,
    //         CURLOPT_HTTPHEADER     => [
    //             'Content-Type: application/json',
    //             'Authorization: Bearer ' . $apiKey,
    //         ],
    //         CURLOPT_TIMEOUT        => 20,
    //         CURLOPT_SSL_VERIFYPEER => true,
    //     ]);
    //
    //     $response = curl_exec($ch);
    //     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //     $curlErr  = curl_error($ch);
    //     curl_close($ch);
    //
    //     if ($curlErr) {
    //         throw new \Exception("cURL: {$curlErr}");
    //     }
    //
    //     $json = json_decode($response, true);
    //
    //     if ($httpCode !== 200 || !isset($json['choices'][0]['message']['content'])) {
    //         $msg = $json['error']['message'] ?? "HTTP {$httpCode} — resposta inesperada";
    //         throw new \Exception($msg);
    //     }
    //
    //     $text = trim($json['choices'][0]['message']['content']);
    //     // Remover possível markdown code block
    //     $text = preg_replace('/^```(?:json)?\s*\n?/', '', $text);
    //     $text = preg_replace('/\n?```\s*$/', '', trim($text));
    //
    //     $dados = json_decode($text, true);
    //     if (!is_array($dados)) {
    //         throw new \Exception("Resposta da IA não é JSON válido.");
    //     }
    //
    //     return $dados;
    // }
}
