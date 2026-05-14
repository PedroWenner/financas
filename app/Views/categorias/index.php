<div class="d-flex justify-between align-center mb-3">
    <div></div>
    <button class="btn btn-primary" data-modal="modal-criar">
        + Nova Categoria
    </button>
</div>

<?php if (empty($categorias)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            <h3>Nenhuma categoria cadastrada</h3>
            <p>Crie categorias para organizar suas receitas e despesas.</p>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Cor</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td>
                        <div class="color-dot" style="background:<?= e($cat['cor'] ?: '#6c757d') ?>;width:16px;height:16px;border-radius:4px;"></div>
                    </td>
                    <td class="fw-600"><?= e($cat['nome']) ?></td>
                    <td>
                        <?php
                        $tipos = ['receita' => 'Receita', 'despesa' => 'Despesa', 'ambos' => 'Ambos'];
                        $classes = ['receita' => 'badge-success', 'despesa' => 'badge-danger', 'ambos' => 'badge-info'];
                        ?>
                        <span class="badge <?= $classes[$cat['tipo']] ?? 'badge-secondary' ?>">
                            <?= $tipos[$cat['tipo']] ?? e($cat['tipo']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="td-actions">
                            <button class="btn btn-secondary btn-sm"
                                onclick="openEditModal('modal-editar', {
                                    id: '<?= $cat['id'] ?>',
                                    nome: '<?= addslashes(e($cat['nome'])) ?>',
                                    tipo: '<?= $cat['tipo'] ?>',
                                    cor: '<?= $cat['cor'] ?>'
                                })">
                                ✏️ Editar
                            </button>
                            <form method="POST" action="/categorias/excluir" style="display:inline;">
                                <?= CsrfHelper::field() ?>
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Tem certeza que deseja remover a categoria '<?= addslashes(e($cat['nome'])) ?>'?">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Modal: Criar Categoria -->
<div class="modal-overlay" id="modal-criar">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Nova Categoria</span>
            <button class="modal-close" type="button">×</button>
        </div>
        <form method="POST" action="/categorias/criar">
            <?= CsrfHelper::field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nome-criar">Nome *</label>
                    <input type="text" id="nome-criar" name="nome" class="form-control" placeholder="Ex: Alimentação" required maxlength="80">
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="tipo-criar">Tipo *</label>
                        <select id="tipo-criar" name="tipo" class="form-control">
                            <option value="despesa">Despesa</option>
                            <option value="receita">Receita</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cor-criar">Cor</label>
                        <input type="color" id="cor-criar" name="cor" class="form-control" value="#3498db" style="height:42px;padding:4px 8px;cursor:pointer;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Categoria</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Categoria -->
<div class="modal-overlay" id="modal-editar">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Editar Categoria</span>
            <button class="modal-close" type="button">×</button>
        </div>
        <form method="POST" action="/categorias/editar">
            <?= CsrfHelper::field() ?>
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit-nome">Nome *</label>
                    <input type="text" id="edit-nome" name="nome" class="form-control" required maxlength="80">
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="edit-tipo">Tipo *</label>
                        <select id="edit-tipo" name="tipo" class="form-control">
                            <option value="despesa">Despesa</option>
                            <option value="receita">Receita</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-cor">Cor</label>
                        <input type="color" id="edit-cor" name="cor" class="form-control" style="height:42px;padding:4px 8px;cursor:pointer;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Sobrescreve openEditModal para mapear campos corretamente
function openEditModal(id, dados) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    document.getElementById('edit-id').value   = dados.id;
    document.getElementById('edit-nome').value = dados.nome;
    document.getElementById('edit-tipo').value = dados.tipo;
    document.getElementById('edit-cor').value  = dados.cor || '#3498db';
    overlay.classList.add('open');
}
</script>
