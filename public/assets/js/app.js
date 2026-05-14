/**
 * AlcatechFinanças — JavaScript Principal
 */

'use strict';

// ──────────────────────────────────────────────
// Tema (dark/light mode)
// ──────────────────────────────────────────────
(function () {
    const tema = localStorage.getItem('tema') || 'light';
    document.documentElement.setAttribute('data-theme', tema);
})();

function toggleTema() {
    const atual = document.documentElement.getAttribute('data-theme');
    const novo   = atual === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', novo);
    localStorage.setItem('tema', novo);

    const icon = document.getElementById('tema-icon');
    if (icon) icon.textContent = novo === 'dark' ? '☀️' : '🌙';
}

// ──────────────────────────────────────────────
// Sidebar (mobile toggle)
// ──────────────────────────────────────────────
function toggleSidebar() {
    const sidebar  = document.querySelector('.sidebar');
    const overlay  = document.querySelector('.sidebar-overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
}

document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.getElementById('hamburger');
    const overlay   = document.querySelector('.sidebar-overlay');

    if (hamburger) hamburger.addEventListener('click', toggleSidebar);
    if (overlay)   overlay.addEventListener('click', toggleSidebar);

    // ──────────────────────────────────────────
    // Fechar alertas
    // ──────────────────────────────────────────
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.alert').remove();
        });
    });

    // Auto-fechar alertas de sucesso após 4s
    const alertSucc = document.querySelector('.alert-success');
    if (alertSucc) {
        setTimeout(() => alertSucc.style.transition = 'opacity .4s', 3600);
        setTimeout(() => alertSucc.style.opacity   = '0',  4000);
        setTimeout(() => alertSucc.remove(),                4500);
    }

    // ──────────────────────────────────────────
    // Máscara de moeda
    // ──────────────────────────────────────────
    document.querySelectorAll('[data-mask="money"]').forEach(applyMoneyMask);

    // ──────────────────────────────────────────
    // Confirmação de exclusão
    // ──────────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Confirmar ação?')) {
                e.preventDefault();
            }
        });
    });

    // ──────────────────────────────────────────
    // Modais
    // ──────────────────────────────────────────
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => openModal(btn.dataset.modal));
    });

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').classList.remove('open');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    // Fecha modal com Esc
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
        }
    });

    // ──────────────────────────────────────────
    // Marcar item ativo na sidebar
    // ──────────────────────────────────────────
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(item => {
        const href = item.getAttribute('href') || '';
        if (href && href !== '/' && currentPath.startsWith(href)) {
            item.classList.add('active');
        } else if (href === '/' && currentPath === '/') {
            item.classList.add('active');
        }
    });

    // ──────────────────────────────────────────
    // Atualizar ícone do tema ao carregar
    // ──────────────────────────────────────────
    const temaIcon = document.getElementById('tema-icon');
    if (temaIcon) {
        const tema = localStorage.getItem('tema') || 'light';
        temaIcon.textContent = tema === 'dark' ? '☀️' : '🌙';
    }
});

// ──────────────────────────────────────────────
// Funções de Modal
// ──────────────────────────────────────────────
function openModal(id) {
    const overlay = document.getElementById(id);
    if (overlay) overlay.classList.add('open');
}

function closeModal(id) {
    const overlay = document.getElementById(id);
    if (overlay) overlay.classList.remove('open');
}

function openEditModal(id, dados) {
    const overlay = document.getElementById(id);
    if (!overlay) return;

    // Preenche campos do modal com os dados
    Object.entries(dados).forEach(([campo, valor]) => {
        const input = overlay.querySelector(`[name="${campo}"]`);
        if (input) input.value = valor;
    });

    overlay.classList.add('open');
}

// ──────────────────────────────────────────────
// Máscara de Moeda Brasileira
// ──────────────────────────────────────────────
function applyMoneyMask(input) {
    input.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '');
        if (!v) { this.value = ''; return; }
        v = (parseInt(v, 10) / 100).toFixed(2);
        this.value = v
            .replace('.', ',')
            .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    });

    input.addEventListener('focus', function () {
        if (this.value === '0,00') this.value = '';
    });
}

// ──────────────────────────────────────────────
// Utilitários
// ──────────────────────────────────────────────
function formatMoeda(valor) {
    return 'R$ ' + parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function parseMoedaBR(str) {
    return parseFloat(
        str.replace(/\./g, '').replace(',', '.')
    ) || 0;
}

// Cálculo automático de parcelas
function calcularParcelas() {
    const total    = parseMoedaBR(document.getElementById('valor_total')?.value || '0');
    const nParc    = parseInt(document.getElementById('numero_parcelas')?.value || '1');
    const preview  = document.getElementById('parcela-preview');

    if (preview && total > 0 && nParc > 0) {
        const valorParc = (total / nParc).toFixed(2);
        preview.textContent = nParc + 'x de ' + formatMoeda(valorParc);
    }
}
