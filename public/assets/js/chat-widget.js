(function () {
    'use strict';

    var fab, modal, messagesEl, inputEl, sendBtn, pendingData;

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

        addBotMessage('Olá! Descreva uma transação e eu registro para você. Ex: "gastei 80 no mercado ontem" ou "recebi salário de 5000".');
    }

    function toggleModal() {
        var hidden = modal.getAttribute('aria-hidden') === 'true';
        if (hidden) showModal(); else hideModal();
    }

    function showModal() {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('chat-modal--open');
        fab.classList.add('chat-fab--open');
        inputEl.focus();
    }

    function hideModal() {
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('chat-modal--open');
        fab.classList.remove('chat-fab--open');
    }

    function sendMessage() {
        var msg = inputEl.value.trim();
        if (!msg) return;

        addUserMessage(msg);
        inputEl.value = '';
        inputEl.disabled = true;
        sendBtn.disabled = true;
        pendingData = null;
        showTyping();

        fetch('/api/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(msg)
        })
        .then(function (r) { return r.json(); })
        .then(handleAiResponse)
        .catch(function () {
            removeTyping();
            addBotMessage('Erro de conexão. Verifique sua internet e tente novamente.');
        })
        .finally(function () {
            inputEl.disabled = false;
            sendBtn.disabled = false;
            inputEl.focus();
        });
    }

    function handleAiResponse(data) {
        removeTyping();

        if (data.erro) {
            addBotMessage('Erro: ' + data.erro);
            return;
        }

        if (data.tipo === 'pergunta') {
            addBotMessage(data.preview || 'Pode me dar mais detalhes?');
            return;
        }

        pendingData = data;
        addConfirmation(data.preview || 'Confirmar lançamento?');
    }

    function addConfirmation(text) {
        var wrap = document.createElement('div');
        wrap.className = 'chat-message chat-message--bot';

        var p = document.createElement('p');
        p.textContent = text;

        var btns = document.createElement('div');
        btns.className = 'chat-confirm-buttons';

        var btnOk = document.createElement('button');
        btnOk.className = 'chat-btn-confirm';
        btnOk.textContent = 'Confirmar';
        btnOk.addEventListener('click', function () {
            btns.remove();
            confirmarLancamento();
        });

        var btnNo = document.createElement('button');
        btnNo.className = 'chat-btn-cancel';
        btnNo.textContent = 'Cancelar';
        btnNo.addEventListener('click', function () {
            btns.remove();
            pendingData = null;
            addBotMessage('Cancelado! Pode me dizer outra coisa.');
        });

        btns.appendChild(btnOk);
        btns.appendChild(btnNo);
        wrap.appendChild(p);
        wrap.appendChild(btns);
        messagesEl.appendChild(wrap);
        scrollBottom();
    }

    function confirmarLancamento() {
        if (!pendingData) return;

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';

        showTyping();
        inputEl.disabled = true;
        sendBtn.disabled = true;

        fetch('/api/chat/confirmar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf_token=' + encodeURIComponent(csrf) +
                  '&dados='      + encodeURIComponent(JSON.stringify(pendingData))
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            removeTyping();
            if (data.sucesso) {
                addBotMessage('✅ ' + data.mensagem);
            } else {
                addBotMessage('❌ Erro: ' + (data.erro || 'Falha ao salvar.'));
            }
            pendingData = null;
        })
        .catch(function () {
            removeTyping();
            addBotMessage('❌ Erro de conexão ao salvar.');
        })
        .finally(function () {
            inputEl.disabled = false;
            sendBtn.disabled = false;
            inputEl.focus();
        });
    }

    function addUserMessage(text) {
        addMessage(text, 'chat-message--user');
    }

    function addBotMessage(text) {
        addMessage(text, 'chat-message--bot');
    }

    function addMessage(text, cls) {
        var div = document.createElement('div');
        div.className = 'chat-message ' + cls;
        div.textContent = text;
        messagesEl.appendChild(div);
        scrollBottom();
    }

    function showTyping() {
        if (document.getElementById('chat-typing')) return;
        var div = document.createElement('div');
        div.id = 'chat-typing';
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
