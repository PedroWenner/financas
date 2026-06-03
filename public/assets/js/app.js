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
// Toast (SweetAlert2)
// ──────────────────────────────────────────────
const _toast = typeof Swal !== 'undefined' ? Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    didOpen: (el) => {
        el.addEventListener('mouseenter', Swal.stopTimer);
        el.addEventListener('mouseleave', Swal.resumeTimer);
    }
}) : null;

function showToast(tipo, mensagem) {
    if (!_toast) return;
    // PHP usa 'error', Swal usa 'error' também — mapeamento direto
    _toast.fire({ icon: tipo, title: mensagem });
}

// ──────────────────────────────────────────────
// Sidebar (mobile toggle)
// ──────────────────────────────────────────────
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
}

document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.getElementById('hamburger');
    const overlay   = document.querySelector('.sidebar-overlay');

    if (hamburger) hamburger.addEventListener('click', toggleSidebar);
    if (overlay)   overlay.addEventListener('click', toggleSidebar);

    // ──────────────────────────────────────────
    // Flash message como toast
    // ──────────────────────────────────────────
    if (window.__flash) {
        showToast(window.__flash.tipo, window.__flash.msg);
    }

    // ──────────────────────────────────────────
    // Fechar alertas inline (se existirem)
    // ──────────────────────────────────────────
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.alert').remove());
    });

    // ──────────────────────────────────────────
    // Máscara de moeda
    // ──────────────────────────────────────────
    document.querySelectorAll('[data-mask="money"]').forEach(applyMoneyMask);

    // ──────────────────────────────────────────
    // Confirmação de exclusão via SweetAlert2
    // ──────────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const btn  = this;
            const form = btn.closest('form');
            const msg  = btn.dataset.confirm || 'Confirmar ação?';

            Swal.fire({
                title: 'Tem certeza?',
                text: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true,
            }).then(result => {
                if (result.isConfirmed && form) {
                    form.submit();
                }
            });
        });
    });

    // ──────────────────────────────────────────
    // Confirmação de exclusão permanente (danger)
    // ──────────────────────────────────────────
    document.querySelectorAll('[data-confirm-danger]').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const btn  = this;
            const form = btn.closest('form');
            const msg  = btn.dataset.confirmDanger || 'Esta ação não pode ser desfeita.';

            Swal.fire({
                title: 'Excluir permanentemente?',
                text: msg,
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true,
            }).then(result => {
                if (result.isConfirmed && form) {
                    form.submit();
                }
            });
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
    // Ordenação de tabelas
    // ──────────────────────────────────────────
    initTableSort();

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

// ──────────────────────────────────────────────
// Ordenação de Tabelas
// ──────────────────────────────────────────────
function initTableSort() {
    document.querySelectorAll('.table-wrapper table').forEach(table => {
        table.querySelectorAll('thead th').forEach((th, colIndex) => {
            const label = th.textContent.trim().toLowerCase();
            if (label === 'ações' || label === 'ação' || th.dataset.noSort !== undefined) return;

            th.classList.add('sortable');
            th.addEventListener('click', () => _sortTable(table, th, colIndex));
        });
    });
}

function _parseCellValue(td) {
    const text = td.textContent.trim();

    // Moeda: R$ 1.234,56
    if (/^R\$\s*[\d.,]+$/.test(text)) {
        return { type: 'number', value: parseFloat(text.replace(/[R$\s.]/g, '').replace(',', '.')) };
    }

    // Data: dd/mm/yyyy
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(text)) {
        const [d, m, y] = text.split('/');
        return { type: 'date', value: new Date(+y, +m - 1, +d).getTime() };
    }

    // Número simples
    const num = parseFloat(text.replace(',', '.'));
    if (!isNaN(num) && text !== '') {
        return { type: 'number', value: num };
    }

    return { type: 'text', value: text.toLowerCase() };
}

function _sortTable(table, clickedTh, colIndex) {
    const tbody   = table.querySelector('tbody');
    if (!tbody) return;
    const rows    = Array.from(tbody.querySelectorAll('tr'));
    const isAsc   = clickedTh.classList.contains('sort-asc');

    table.querySelectorAll('thead th').forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
    clickedTh.classList.add(isAsc ? 'sort-desc' : 'sort-asc');

    rows.sort((a, b) => {
        const cellA = a.querySelectorAll('td')[colIndex];
        const cellB = b.querySelectorAll('td')[colIndex];
        if (!cellA || !cellB) return 0;

        const vA = _parseCellValue(cellA);
        const vB = _parseCellValue(cellB);

        let cmp = 0;
        if (vA.type !== 'text' && vB.type !== 'text') {
            cmp = vA.value - vB.value;
        } else {
            cmp = String(vA.value).localeCompare(String(vB.value), 'pt-BR');
        }

        return isAsc ? -cmp : cmp;
    });

    rows.forEach(row => tbody.appendChild(row));
}

function calcularParcelas() {
    const total   = parseMoedaBR(document.getElementById('valor_total')?.value || '0');
    const nParc   = parseInt(document.getElementById('numero_parcelas')?.value || '1');
    const preview = document.getElementById('parcela-preview');

    if (preview && total > 0 && nParc > 0) {
        const valorParc = (total / nParc).toFixed(2);
        preview.textContent = nParc + 'x de ' + formatMoeda(valorParc);
    }
}
