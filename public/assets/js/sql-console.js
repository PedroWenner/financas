/**
 * Console SQL (/admin/sql) — troca de abas e construtor visual de consulta.
 */

'use strict';

// ──────────────────────────────────────────────
// Abas
// ──────────────────────────────────────────────
(function () {
    const app = document.getElementById('sql-console-app');
    if (!app) return;

    const botoes  = app.querySelectorAll('.sql-tab-btn');
    const paineis = app.querySelectorAll('.sql-tab-panel');

    function ativar(aba) {
        botoes.forEach(b => b.classList.toggle('active', b.dataset.aba === aba));
        paineis.forEach(p => p.classList.toggle('active', p.dataset.aba === aba));
    }

    botoes.forEach(b => b.addEventListener('click', () => ativar(b.dataset.aba)));

    ativar(app.dataset.abaAtiva || 'livre');
})();

// ──────────────────────────────────────────────
// Construtor de Consulta
// ──────────────────────────────────────────────
(function () {
    const treeEl = document.getElementById('qb-tree');
    const sqlEl  = document.getElementById('qb-sql');
    if (!treeEl || !sqlEl) return;

    const esquema  = window.__sqlEsquema || { tabelas: {}, fks: [] };
    const tabelas  = esquema.tabelas || {};
    const fks      = esquema.fks || [];

    // Índices de relacionamento: por tabela de origem e por tabela referenciada
    const fksPorTabela     = {};
    const referenciadoPor  = {};
    fks.forEach(fk => {
        if (!fksPorTabela[fk.tabela])       fksPorTabela[fk.tabela] = [];
        if (!referenciadoPor[fk.ref_tabela]) referenciadoPor[fk.ref_tabela] = [];
        fksPorTabela[fk.tabela].push(fk);
        referenciadoPor[fk.ref_tabela].push(fk);
    });

    const nomesTabelas = Object.keys(tabelas).sort();

    nomesTabelas.forEach(nomeTabela => {
        const colunas = tabelas[nomeTabela];
        const saida   = fksPorTabela[nomeTabela]    || [];
        const entrada = referenciadoPor[nomeTabela] || [];

        const details = document.createElement('details');
        details.className = 'qb-table';

        const summary = document.createElement('summary');

        const chkTabela = document.createElement('input');
        chkTabela.type = 'checkbox';
        chkTabela.className = 'qb-chk-tabela';

        summary.appendChild(chkTabela);
        summary.appendChild(document.createTextNode(' ' + nomeTabela));

        if (saida.length || entrada.length) {
            const badge = document.createElement('span');
            badge.className = 'qb-fk-count';
            badge.textContent = (saida.length + entrada.length) + ' relação(ões)';
            summary.appendChild(badge);
        }
        details.appendChild(summary);

        const colunasWrap = document.createElement('div');
        colunasWrap.className = 'qb-colunas';

        colunas.forEach(col => {
            const fkSaida = saida.find(f => f.coluna === col.nome);

            const row = document.createElement('label');
            row.className = 'qb-coluna';

            const chk = document.createElement('input');
            chk.type = 'checkbox';
            chk.className = 'qb-chk-coluna';
            chk.dataset.tabela = nomeTabela;
            chk.dataset.coluna = col.nome;
            row.appendChild(chk);

            const nome = document.createElement('span');
            nome.textContent = col.nome;
            row.appendChild(nome);

            if (col.pk) {
                const tag = document.createElement('span');
                tag.className = 'qb-tag qb-tag-pk';
                tag.textContent = 'PK';
                row.appendChild(tag);
            }
            if (fkSaida) {
                const tag = document.createElement('span');
                tag.className = 'qb-tag qb-tag-fk';
                tag.textContent = 'FK → ' + fkSaida.ref_tabela + '.' + fkSaida.ref_coluna;
                row.appendChild(tag);
            }

            const tipo = document.createElement('span');
            tipo.className = 'qb-tipo';
            tipo.textContent = col.tipo;
            row.appendChild(tipo);

            colunasWrap.appendChild(row);
        });

        details.appendChild(colunasWrap);

        if (entrada.length) {
            const relDiv = document.createElement('div');
            relDiv.className = 'qb-referenciado-por';
            relDiv.textContent = 'Referenciado por: ' + entrada.map(f => f.tabela + '.' + f.coluna).join(', ');
            details.appendChild(relDiv);
        }

        treeEl.appendChild(details);

        chkTabela.addEventListener('change', () => {
            colunasWrap.querySelectorAll('.qb-chk-coluna').forEach(c => { c.checked = chkTabela.checked; });
            gerarScript();
        });

        colunasWrap.querySelectorAll('.qb-chk-coluna').forEach(chk => {
            chk.addEventListener('change', () => {
                const todos    = colunasWrap.querySelectorAll('.qb-chk-coluna');
                const marcados = colunasWrap.querySelectorAll('.qb-chk-coluna:checked');
                chkTabela.checked       = marcados.length === todos.length;
                chkTabela.indeterminate = marcados.length > 0 && marcados.length < todos.length;
                gerarScript();
            });
        });
    });

    function gerarScript() {
        const porTabela = {};
        treeEl.querySelectorAll('.qb-chk-coluna:checked').forEach(chk => {
            const t = chk.dataset.tabela;
            if (!porTabela[t]) porTabela[t] = [];
            porTabela[t].push(chk.dataset.coluna);
        });

        const tabelasSelecionadas = Object.keys(porTabela);
        if (tabelasSelecionadas.length === 0) {
            sqlEl.value = '';
            return;
        }

        const incluidas = [tabelasSelecionadas[0]];
        const joins = [];

        for (let i = 1; i < tabelasSelecionadas.length; i++) {
            const alvo = tabelasSelecionadas[i];
            let condicao = null;

            for (const jaIncluida of incluidas) {
                const direta  = (fksPorTabela[alvo] || []).find(f => f.ref_tabela === jaIncluida);
                const inversa = (fksPorTabela[jaIncluida] || []).find(f => f.ref_tabela === alvo);
                if (direta)  { condicao = alvo + '.' + direta.coluna + ' = ' + jaIncluida + '.' + direta.ref_coluna; break; }
                if (inversa) { condicao = alvo + '.' + inversa.ref_coluna + ' = ' + jaIncluida + '.' + inversa.coluna; break; }
            }

            if (condicao) {
                joins.push('INNER JOIN ' + alvo + ' ON ' + condicao);
            } else {
                joins.push('CROSS JOIN ' + alvo + ' -- sem relação direta encontrada; ajuste o JOIN manualmente');
            }
            incluidas.push(alvo);
        }

        const listaColunas = tabelasSelecionadas
            .map(t => porTabela[t].map(c => t + '.' + c).join(',\n    '))
            .join(',\n    ');

        let script = 'SELECT\n    ' + listaColunas + '\nFROM ' + tabelasSelecionadas[0];
        if (joins.length) script += '\n' + joins.join('\n');
        script += ';';

        sqlEl.value = script;
    }
})();

// ──────────────────────────────────────────────
// Carregar script salvo
// ──────────────────────────────────────────────
(function () {
    const sqlEl = document.getElementById('qb-sql');
    if (!sqlEl) return;

    document.querySelectorAll('.qb-carregar-script').forEach(btn => {
        btn.addEventListener('click', () => {
            sqlEl.value = btn.dataset.script || '';
        });
    });
})();

// ──────────────────────────────────────────────
// Exportar resultado para Excel (CSV)
// ──────────────────────────────────────────────
(function () {
    function celulaCsv(texto) {
        texto = texto.replace(/"/g, '""');
        return /[;"\n]/.test(texto) ? '"' + texto + '"' : texto;
    }

    function exportarTabela(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const linhas = [];
        table.querySelectorAll('tr').forEach(tr => {
            const celulas = Array.from(tr.querySelectorAll('th,td')).map(td => celulaCsv(td.textContent.trim()));
            linhas.push(celulas.join(';'));
        });

        const csv  = '\uFEFF' + linhas.join('\r\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = tableId + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    document.querySelectorAll('.qb-exportar-excel').forEach(btn => {
        btn.addEventListener('click', () => exportarTabela(btn.dataset.tableId));
    });
})();
