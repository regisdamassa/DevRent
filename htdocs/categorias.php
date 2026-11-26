<?php
// Categorias: listar, cadastrar, editar e excluir (somente administradores alteram)
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (!admin()) throw new RuntimeException('Acesso restrito.');

        $acao = $_POST['acao'] ?? 'criar';
        $cid  = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome'] ?? '');
        $desc = trim($_POST['descricao'] ?? '');

        if ($acao === 'criar') {
            if (!$nome) throw new RuntimeException('Nome obrigatório.');
            $s = $conn->prepare('INSERT INTO categorias(nome,descricao) VALUES(?,?)');
            $s->bind_param('ss', $nome, $desc);
            $s->execute();
            flash('Categoria cadastrada.');

        } elseif ($acao === 'editar') {
            if (!$cid || !$nome) throw new RuntimeException('Categoria ou nome inválidos.');
            $s = $conn->prepare('UPDATE categorias SET nome=?, descricao=? WHERE id=?');
            $s->bind_param('ssi', $nome, $desc, $cid);
            $s->execute();
            flash('Categoria atualizada.');

        } elseif ($acao === 'excluir') {
            if (!$cid) throw new RuntimeException('Categoria inválida.');
            // Não pode excluir se ainda houver equipamentos nesta categoria (chave estrangeira)
            $s = $conn->prepare('SELECT COUNT(*) FROM equipamentos WHERE categoria_id=?');
            $s->bind_param('i', $cid);
            $s->execute();
            $total = (int)$s->get_result()->fetch_row()[0];
            if ($total > 0) throw new RuntimeException("Não é possível excluir: há $total equipamento(s) nesta categoria.");
            $s = $conn->prepare('DELETE FROM categorias WHERE id=?');
            $s->bind_param('i', $cid);
            $s->execute();
            flash('Categoria excluída.');

        } else {
            throw new RuntimeException('Ação inválida.');
        }
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if ($e instanceof mysqli_sql_exception) {
            $msg = $e->getCode() === 1062 ? 'já existe uma categoria com esse nome.' : 'falha ao gravar no banco de dados.';
        }
        flash('Erro: ' . $msg);
    }
    go('categorias');
}

// Modo edição: carrega a categoria escolhida (?page=categorias&id=3)
$edit = null;
if ($id && admin()) {
    $s = $conn->prepare('SELECT id,nome,descricao FROM categorias WHERE id=?');
    $s->bind_param('i', $id);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc();
    if (!$edit) { flash('Categoria não encontrada.'); go('categorias'); }
}

$rows = $conn->query('SELECT c.id,c.nome,c.descricao,COUNT(e.id) AS total FROM categorias c LEFT JOIN equipamentos e ON e.categoria_id=c.id GROUP BY c.id,c.nome,c.descricao ORDER BY c.nome');
?>
<h1>Categorias</h1>

<?php if (admin()): ?>
<form method="post">
  <h2><?= $edit ? 'Editar categoria' : 'Nova categoria' ?></h2>
  <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
  <input type="hidden" name="acao" value="<?= $edit ? 'editar' : 'criar' ?>">
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <label>Nome<input name="nome" required value="<?= h($edit['nome'] ?? '') ?>"></label>
  <label>Descrição<input name="descricao" value="<?= h($edit['descricao'] ?? '') ?>"></label>
  <button><?= $edit ? 'Salvar alterações' : 'Cadastrar categoria' ?></button>
  <?php if ($edit): ?><a class="button secondary" href="?page=categorias">Cancelar</a><?php endif; ?>
</form>
<?php endif; ?>

<table>
  <tr>
    <th>Categoria</th><th>Descrição</th><th>Equipamentos</th>
    <?php if (admin()): ?><th>Ações</th><?php endif; ?>
  </tr>
  <?php while ($r = $rows->fetch_assoc()): ?>
  <tr>
    <td><?= h($r['nome']) ?></td>
    <td><?= h($r['descricao']) ?></td>
    <td><?= h($r['total']) ?></td>
    <?php if (admin()): ?>
    <td>
      <div class="actions">
        <a class="button" href="?page=categorias&id=<?= (int)$r['id'] ?>">Editar</a>
        <form method="post" onsubmit="return confirm('Excluir esta categoria?')">
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
