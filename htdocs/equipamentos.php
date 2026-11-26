<?php
// Equipamentos: listar, cadastrar, editar e excluir (somente administradores alteram)
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (!admin()) throw new RuntimeException('Acesso restrito.');

        $acao   = $_POST['acao'] ?? 'criar';
        $eid    = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $nome   = trim($_POST['nome'] ?? '');
        $pat    = filter_var($_POST['patrimonio'] ?? '', FILTER_VALIDATE_INT);
        $cat    = filter_var($_POST['categoria_id'] ?? '', FILTER_VALIDATE_INT);
        $desc   = trim($_POST['descricao'] ?? '');
        $status = $_POST['status'] ?? '';

        if ($acao === 'criar') {
            if (!$nome || !$pat || !$cat) throw new RuntimeException('Preencha nome, patrimônio e categoria.');
            $s = $conn->prepare('INSERT INTO equipamentos(nome,patrimonio,categoria_id,descricao) VALUES(?,?,?,?)');
            $s->bind_param('siis', $nome, $pat, $cat, $desc);
            $s->execute();
            flash('Equipamento cadastrado.');

        } elseif ($acao === 'editar') {
            if (!$eid || !$nome || !$pat || !$cat) throw new RuntimeException('Preencha nome, patrimônio e categoria.');

            // Transação + FOR UPDATE: evita que o status mude no meio da edição (ex.: alguém reservando)
            $conn->begin_transaction();
            $s = $conn->prepare('SELECT status FROM equipamentos WHERE id=? FOR UPDATE');
            $s->bind_param('i', $eid);
            $s->execute();
            $atual = $s->get_result()->fetch_assoc();
            if (!$atual) throw new RuntimeException('Equipamento não encontrado.');

            if ($atual['status'] === 'reservado') {
                // Está ligado a uma reserva em andamento: o status só muda pelo fluxo da reserva
                $novo = 'reservado';
            } else {
                if (!in_array($status, ['disponivel', 'manutencao', 'inativo'], true)) throw new RuntimeException('Status inválido.');
                $novo = $status;
            }

            $s = $conn->prepare('UPDATE equipamentos SET nome=?, patrimonio=?, categoria_id=?, descricao=?, status=? WHERE id=?');
            $s->bind_param('siissi', $nome, $pat, $cat, $desc, $novo, $eid);
            $s->execute();
            $conn->commit();
            flash('Equipamento atualizado.');

        } elseif ($acao === 'excluir') {
            if (!$eid) throw new RuntimeException('Equipamento inválido.');
            // Se já teve reservas, excluir quebraria o histórico (chave estrangeira)
            $s = $conn->prepare('SELECT COUNT(*) FROM reservas WHERE equipamento_id=?');
            $s->bind_param('i', $eid);
            $s->execute();
            $total = (int)$s->get_result()->fetch_row()[0];
            if ($total > 0) throw new RuntimeException("Este equipamento possui $total reserva(s) registrada(s). Em vez de excluir, edite e marque como inativo.");
            $s = $conn->prepare('DELETE FROM equipamentos WHERE id=?');
            $s->bind_param('i', $eid);
            $s->execute();
            flash('Equipamento excluído.');

        } else {
            throw new RuntimeException('Ação inválida.');
        }
    } catch (Throwable $e) {
        $conn->rollback();
        $msg = $e->getMessage();
        if ($e instanceof mysqli_sql_exception) {
            $msg = $e->getCode() === 1062 ? 'já existe um equipamento com esse patrimônio.' : 'falha ao gravar no banco de dados.';
        }
        flash('Erro: ' . $msg);
    }
    go('equipamentos');
}

// Modo edição: carrega o equipamento escolhido (?page=equipamentos&id=3)
$edit = null;
if ($id && admin()) {
    $s = $conn->prepare('SELECT id,nome,patrimonio,categoria_id,descricao,status FROM equipamentos WHERE id=?');
    $s->bind_param('i', $id);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc();
    if (!$edit) { flash('Equipamento não encontrado.'); go('equipamentos'); }
}

$cats = $conn->query('SELECT id,nome FROM categorias ORDER BY nome');
$rows = $conn->query('SELECT e.id,e.nome,e.patrimonio,e.status,c.nome AS categoria FROM equipamentos e INNER JOIN categorias c ON c.id=e.categoria_id ORDER BY e.nome');
$statusLabels = ['disponivel' => 'Disponível', 'manutencao' => 'Em manutenção', 'inativo' => 'Inativo'];
?>
<h1>Equipamentos</h1>

<?php if (admin()): ?>
<form method="post">
  <h2><?= $edit ? 'Editar equipamento' : 'Novo equipamento' ?></h2>
  <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
  <input type="hidden" name="acao" value="<?= $edit ? 'editar' : 'criar' ?>">
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <label>Nome<input name="nome" required value="<?= h($edit['nome'] ?? '') ?>"></label>
  <label>Patrimônio<input name="patrimonio" type="number" min="1" required value="<?= h($edit['patrimonio'] ?? '') ?>"></label>
  <label>Categoria
    <select name="categoria_id" required>
      <option value="">Selecione</option>
      <?php while ($c = $cats->fetch_assoc()): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === (int)($edit['categoria_id'] ?? 0) ? 'selected' : '' ?>><?= h($c['nome']) ?></option>
      <?php endwhile; ?>
    </select>
  </label>
  <label>Descrição<input name="descricao" value="<?= h($edit['descricao'] ?? '') ?>"></label>
  <?php if ($edit): ?>
    <?php if ($edit['status'] === 'reservado'): ?>
      <p class="hint">Status: reservado. Ele muda automaticamente quando a reserva for cancelada ou devolvida.</p>
    <?php else: ?>
      <label>Status
        <select name="status">
          <?php foreach ($statusLabels as $valor => $rotulo): ?>
            <option value="<?= h($valor) ?>" <?= $edit['status'] === $valor ? 'selected' : '' ?>><?= h($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
  <?php endif; ?>
  <button><?= $edit ? 'Salvar alterações' : 'Cadastrar equipamento' ?></button>
  <?php if ($edit): ?><a class="button secondary" href="?page=equipamentos">Cancelar</a><?php endif; ?>
</form>
<?php endif; ?>

<table>
  <tr>
    <th>Nome</th><th>Patrimônio</th><th>Categoria</th><th>Status</th>
    <?php if (admin()): ?><th>Ações</th><?php endif; ?>
  </tr>
  <?php while ($r = $rows->fetch_assoc()): ?>
  <tr>
    <td><?= h($r['nome']) ?></td>
    <td><?= h($r['patrimonio']) ?></td>
    <td><?= h($r['categoria']) ?></td>
    <td><?= h($r['status']) ?></td>
    <?php if (admin()): ?>
    <td>
      <div class="actions">
        <a class="button" href="?page=equipamentos&id=<?= (int)$r['id'] ?>">Editar</a>
        <form method="post" onsubmit="return confirm('Excluir este equipamento?')">
          <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
          <input type="hidden" name="acao" value="excluir">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="danger">Excluir</button>
        </form>
      </div>
    </td>
    <?php endif; ?>
  </tr>
  <?php endwhile; ?>
</table>
