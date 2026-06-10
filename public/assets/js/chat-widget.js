(function () {
    'use strict';

    var fab, modal, messagesEl, inputEl, sendBtn;

    // Máquina de estados do fluxo de lançamento
    var ESTADO = {
        INICIAL:          'inicial',
        FORMA_PAGAMENTO:  'forma_pagamento',
        CARTAO:           'cartao',
        OBSERVACAO:       'observacao',
        PAGO:             'pago',
        CONFIRMACAO:      'confirmacao',
    };

    var estadoAtual = ESTADO.INICIAL;
    var pendingData  = null;   // dados parseados + escolhas do usuário acumuladas

    // ─── Inicialização ────────────────────────────────────────────────

    function init() {
        fab        = document.getElementById('chat-fab');
        modal      = document.getElementById('chat-modal');
        messagesEl = document.getElementById('chat-messages');
        inputEl    = document.getElementById('chat-input');
        sendBtn    = document.getElementById('chat-send');

        if (!fab || !modal) return;

        fab.addEventListener('click', toggleModal);
        document.getElementById('chat-close').addEventListener('click', hideModal);
        sendBtn.addEventListener('click', sendMessage);
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideModal();
        });

        addBotMessage('Olá! Descreva uma transação e eu registro para você.\nEx: "gastei 80 no mercado ontem" ou "recebi salário de 5000".');
    }

    // ─── Modal ────────────────────────────────────────────────────────

    function toggleModal() {
        modal.getAttribute('aria-hidden') === 'true' ? showModal() : hideModal();
    }

    function showModal() {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('chat-modal--open');
        fab.classList.add('chat-fab--open');
        if (estadoAtual === ESTADO.INICIAL) inputEl.focus();
    }

    function hideModal() {
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('chat-modal--open');
        fab.classList.remove('chat-fab--open');
    }

    // ─── Envio da mensagem inicial ────────────────────────────────────

    function sendMessage() {
        if (estadoAtual !== ESTADO.INICIAL) return;

        var msg = inputEl.value.trim();
        if (!msg) return;

        addUserMessage(msg);
        inputEl.value = '';
        setInputDisabled(true);
        pendingData = null;
        showTyping();

        fetch('/api/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(msg),
        })
        .then(function (r) { return r.json(); })
        .then(handleParseResponse)
        .catch(function () {
            removeTyping();
            addBotMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            setInputDisabled(false);
        });
    }

    // ─── Resposta do parser ───────────────────────────────────────────

    function handleParseResponse(data) {
        removeTyping();

        if (data.erro) {
            addBotMessage('Erro: ' + data.erro);
            setInputDisabled(false);
            return;
        }

        if (data.tipo === 'pergunta') {
            addBotMessage(data.preview || 'Pode me dar mais detalhes?');
            setInputDisabled(false);
            return;
        }

        pendingData = data;
        addBotMessage(data.preview);

        // Avança para a primeira etapa conforme o tipo detectado
        switch (data.tipo) {
            case 'despesa':      return etapaFormaPagamento();
            case 'parcelamento': return etapaCartao();
            case 'receita':
            case 'despesa_fixa': return etapaObservacao();
        }
    }

    // ─── Etapas do fluxo ─────────────────────────────────────────────

    function etapaFormaPagamento() {
        estadoAtual = ESTADO.FORMA_PAGAMENTO;
        var formas  = pendingData.formas_pagamento || {};
        var opcoes  = Object.keys(formas).map(function (k) { return { valor: k, label: formas[k] }; });

        addBotSteps('Como foi o pagamento?', opcoes, function (op) {
            pendingData.forma_pagamento = op.valor;
            op.valor === 'credito' ? etapaCartao() : etapaObservacao();
        });
    }

    function etapaCartao() {
        estadoAtual = ESTADO.CARTAO;
        var cartoes = (pendingData.cartoes_disponiveis || []);

        if (cartoes.length === 0) {
            pendingData.cartao_id = null;
            etapaObservacao();
            return;
        }

        var opcoes = cartoes.map(function (c) { return { valor: c.id, label: c.nome }; });
        opcoes.push({ valor: null, label: 'Sem cartão' });

        addBotSteps('Qual cartão?', opcoes, function (op) {
            pendingData.cartao_id = op.valor;
            etapaObservacao();
        });
    }

    function etapaObservacao() {
        estadoAtual = ESTADO.OBSERVACAO;

        addBotObservacao(function (obs) {
            pendingData.observacoes = obs || null;
            // Somente despesa tem campo "pago"
            pendingData.tipo === 'despesa' ? etapaPago() : etapaConfirmacao();
        });
    }

    function etapaPago() {
        estadoAtual = ESTADO.PAGO;

        addBotSteps('Já foi pago?', [
            { valor: 1, label: 'Sim' },
            { valor: 0, label: 'Não' },
        ], function (op) {
            pendingData.pago = op.valor;
            pendingData.data_pagamento = op.valor
                ? (pendingData.data || new Date().toISOString().slice(0, 10))
                : null;
            etapaConfirmacao();
        });
    }

    function etapaConfirmacao() {
        estadoAtual = ESTADO.CONFIRMACAO;
        addBotConfirmacao(montarResumo());
    }

    // ─── Confirmação final ────────────────────────────────────────────

    function confirmarLancamento() {
        if (!pendingData) return;

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrf     = csrfMeta ? csrfMeta.getAttribute('content') : '';

        showTyping();

        fetch('/api/chat/confirmar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf_token=' + encodeURIComponent(csrf) +
                  '&dados='      + encodeURIComponent(JSON.stringify(pendingData)),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            removeTyping();
            addBotMessage(data.sucesso
                ? '✅ ' + data.mensagem
                : '❌ Erro: ' + (data.erro || 'Falha ao salvar.'));
            reset();
        })
        .catch(function () {
            removeTyping();
            addBotMessage('❌ Erro de conexão ao salvar.');
            reset();
        });
    }

    function cancelarFluxo() {
        reset();
        addBotMessage('Cancelado! Pode me dizer outra coisa.');
    }

    function reset() {
        estadoAtual = ESTADO.INICIAL;
        pendingData = null;
        setInputDisabled(false);
        inputEl.focus();
    }

    // ─── Montagem do resumo final ─────────────────────────────────────

    function montarResumo() {
        var d      = pendingData;
        var linhas = [d.preview];

        var labelsForma = {
            dinheiro: 'Dinheiro', debito: 'Débito', credito: 'Crédito',
            pix: 'Pix', ted: 'TED', boleto: 'Boleto', outro: 'Outro',
        };
        if (d.forma_pagamento) {
            linhas.push('Pagamento: ' + (labelsForma[d.forma_pagamento] || d.forma_pagamento));
        }
        if (d.cartao_id && d.cartoes_disponiveis) {
            var cartao = d.cartoes_disponiveis.find(function (c) { return c.id == d.cartao_id; });
            if (cartao) linhas.push('Cartão: ' + cartao.nome);
        }
        if (d.observacoes) {
            linhas.push('Obs: ' + d.observacoes);
        }
        if (d.tipo === 'despesa') {
            linhas.push('Status: ' + (d.pago ? 'Pago' : 'Não pago'));
        }
        return linhas.join('\n');
    }

    // ─── Construtores de UI ───────────────────────────────────────────

    function addBotSteps(pergunta, opcoes, callback) {
        var bolha = criarBolhaBot();

        var p = document.createElement('p');
        p.textContent = pergunta;
        bolha.appendChild(p);

        var wrap = document.createElement('div');
        wrap.className = 'chat-step-buttons';

        opcoes.forEach(function (op) {
            var btn = document.createElement('button');
            btn.className   = 'chat-step-btn';
            btn.textContent = op.label;
            btn.addEventListener('click', function () {
                desabilitarBotoes(wrap);
                btn.classList.add('chat-step-btn--selected');
                addUserMessage(op.label);
                callback(op);
            });
            wrap.appendChild(btn);
        });

        // Botão de cancelar fluxo
        var cancelBtn = document.createElement('button');
        cancelBtn.className   = 'chat-step-btn chat-step-btn--danger';
        cancelBtn.textContent = 'Cancelar';
        cancelBtn.addEventListener('click', function () {
            desabilitarBotoes(wrap);
            cancelarFluxo();
        });
        wrap.appendChild(cancelBtn);

        bolha.appendChild(wrap);
        scrollBottom();
    }

    function addBotObservacao(callback) {
        var bolha = criarBolhaBot();

        var p = document.createElement('p');
        p.textContent = 'Alguma observação? (opcional)';
        bolha.appendChild(p);

        var area = document.createElement('textarea');
        area.className   = 'chat-obs-input';
        area.placeholder = 'Ex: nota fiscal, reembolso...';
        area.rows        = 2;
        bolha.appendChild(area);

        var wrap = document.createElement('div');
        wrap.className = 'chat-step-buttons';

        var btnAdd = document.createElement('button');
        btnAdd.className   = 'chat-step-btn chat-step-btn--primary';
        btnAdd.textContent = 'Adicionar';
        btnAdd.addEventListener('click', function () {
            var obs = area.value.trim();
            desabilitarBotoes(wrap);
            area.disabled = true;
            addUserMessage(obs || '(sem observação)');
            callback(obs);
        });

        var btnSkip = document.createElement('button');
        btnSkip.className   = 'chat-step-btn';
        btnSkip.textContent = 'Pular';
        btnSkip.addEventListener('click', function () {
            desabilitarBotoes(wrap);
            area.disabled = true;
            addUserMessage('(sem observação)');
            callback(null);
        });

        var cancelBtn = document.createElement('button');
        cancelBtn.className   = 'chat-step-btn chat-step-btn--danger';
        cancelBtn.textContent = 'Cancelar';
        cancelBtn.addEventListener('click', function () {
            desabilitarBotoes(wrap);
            area.disabled = true;
            cancelarFluxo();
        });

        wrap.appendChild(btnAdd);
        wrap.appendChild(btnSkip);
        wrap.appendChild(cancelBtn);
        bolha.appendChild(wrap);

        setTimeout(function () { area.focus(); }, 50);
        scrollBottom();
    }

    function addBotConfirmacao(resumo) {
        var bolha = criarBolhaBot();

        var pre = document.createElement('pre');
        pre.className   = 'chat-resumo';
        pre.textContent = resumo;
        bolha.appendChild(pre);

        var wrap = document.createElement('div');
        wrap.className = 'chat-confirm-buttons';

        var btnOk = document.createElement('button');
        btnOk.className   = 'chat-btn-confirm';
        btnOk.textContent = 'Confirmar';
        btnOk.addEventListener('click', function () {
            wrap.remove();
            confirmarLancamento();
        });

        var btnNo = document.createElement('button');
        btnNo.className   = 'chat-btn-cancel';
        btnNo.textContent = 'Cancelar';
        btnNo.addEventListener('click', function () {
            wrap.remove();
            cancelarFluxo();
        });

        wrap.appendChild(btnOk);
        wrap.appendChild(btnNo);
        bolha.appendChild(wrap);
        scrollBottom();
    }

    // ─── Utilitários de UI ────────────────────────────────────────────

    function criarBolhaBot() {
        var div = document.createElement('div');
        div.className = 'chat-message chat-message--bot';
        messagesEl.appendChild(div);
        return div;
    }

    function addUserMessage(text) {
        var div = document.createElement('div');
        div.className   = 'chat-message chat-message--user';
        div.textContent = text;
        messagesEl.appendChild(div);
        scrollBottom();
    }

    function addBotMessage(text) {
        var bolha = criarBolhaBot();
        text.split('\n').forEach(function (linha, i) {
            if (i > 0) bolha.appendChild(document.createElement('br'));
            bolha.appendChild(document.createTextNode(linha));
        });
        scrollBottom();
    }

    function desabilitarBotoes(container) {
        container.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
    }

    function setInputDisabled(disabled) {
        inputEl.disabled = disabled;
        sendBtn.disabled = disabled;
    }

    function showTyping() {
        if (document.getElementById('chat-typing')) return;
        var div = document.createElement('div');
        div.id        = 'chat-typing';
        div.className = 'chat-message chat-message--bot chat-typing';
        div.innerHTML = '<span></span><span></span><span></span>';
        messagesEl.appendChild(div);
        scrollBottom();
    }

    function removeTyping() {
        var el = document.getElementById('chat-typing');
        if (el) el.remove();
    }

    function scrollBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    // ─── Bootstrap ───────────────────────────────────────────────────

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
