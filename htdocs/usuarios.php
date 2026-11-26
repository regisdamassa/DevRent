<?php
// Usuários: listar, cadastrar, editar e excluir (somente administradores)
if (!admin()) { echo '<p>Acesso restrito a administradores.</p>'; return; }

$id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$meuId = (int)$_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $acao  = $_POST['acao'] ?? 'criar';
        $uid   = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $nome  = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $tipo  = in_array($_POST['tipo'] ?? '', ['usuario', 'administrador'], true) ? $_POST['tipo'] : 'usuario';
        $ativo = ($_POST['ativo'] ?? '1') === '0' ? 0 : 1;

        if ($acao === 'criar') {
            if (!$nome || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 12) throw new RuntimeException('Informe nome, e-mail e senha de pelo menos 12 caracteres.');
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $s = $conn->prepare('INSERT INTO usuarios(nome,email,senha,tipo) VALUES(?,?,?,?)');
            $s->bind_param('ssss', $nome, $email, $hash, $tipo);
            $s->execute();
            flash('Usuário cadastrado.');

        } elseif ($acao === 'editar') {
            if (!$uid || !$nome || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Informe nome e e-mail válidos.');
            if ($senha !== '' && strlen($senha) < 12) throw new RuntimeException('A nova senha precisa ter pelo menos 12 caracteres.');

            // Proteção contra bloqueio: o admin não pode se rebaixar nem se desativar
            if ($uid === $meuId) { $tipo = 'administrador'; $ativo = 1; }

            if ($senha !== '') {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $s = $conn->prepare('UPDATE usuarios SET nome=?, email=?, tipo=?, ativo=?, senha=? WHERE id=?');
                $s->bind_param('sssisi', $nome, $email, $tipo, $ativo, $hash, $uid);
            } else {
                $s = $conn->prepare('UPDATE usuarios SET nome=?, email=?, tipo=?, ativo=? WHERE id=?');
                $s->bind_param('sssii', $nome, $email, $tipo, $ativo, $uid);
            }
            $s->execute();
            if ($uid === $meuId) $_SESSION['nome'] = $nome; // atualiza o nome mostrado no menu
            flash('Usuário atualizado.');

        } elseif ($acao === 'excluir') {
            if (!$uid) throw new RuntimeException('Usuário inválido.');
            if ($uid === $meuId) throw new RuntimeException('Você não pode excluir a sua própria conta.');
            // Quem já tem reservas ou histórico não pode ser apagado (chave estrangeira): deve ser desativado
            $s = $conn->prepare('SELECT (SELECT COUNT(*) FROM reservas WHERE usuario_id=?) + (SELECT COUNT(*) FROM historico_reservas WHERE usuario_id=?)');
            $s->bind_param('ii', $uid, $uid);
            $s->execute();
            if ((int)$s->get_result()->fetch_row()[0] > 0) throw new RuntimeException('Este usuário possui reservas ou histórico. Em vez de excluir, edite e marque como inativo.');
            $s = $conn->prepare('DELETE FROM usuarios WHERE id=?');
            $s->bind_param('i', $uid);
            $s->execute();
            flash('Usuário excluído.');

        } else {
            throw new RuntimeException('Ação inválida.');
        }
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if ($e instanceof mysqli_sql_exception) {
            $msg = $e->getCode() === 1062 ? 'já existe um usuário com esse e-mail.' : 'falha ao gravar no banco de dados.';
        }
        flash('Erro: ' . $msg);
    }
    go('usuarios');
}

// Modo edição: carrega o usuário escolhido (?page=usuarios&id=3)
$edit = null;
if ($id) {
    $s = $conn->prepare('SELECT id,nome,email,tipo,ativo FROM usuarios WHERE id=?');
    $s->bind_param('i', $id);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc();
    if (!$edit) { flash('Usuário não encontrado.'); go('usuarios'); }
}
$editandoEuMesmo = $edit && (int)$edit['id'] === $meuId;

$rows = $conn->query('SELECT id,nome,email,tipo,ativo FROM usuarios ORDER BY nome');
?>
<h1>Usuários</h1>

<form method="post">
  <h2><?= $edit ? 'Editar usuário' : 'Novo usuário' ?></h2>
  <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
  <input type="hidden" name="acao" value="<?= $edit ? 'editar' : 'criar' ?>">
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <label>Nome<input name="nome" required value="<?= h($edit['nome'] ?? '') ?>"></label>
  <label>E-mail<input name="email" type="email" required value="<?= h($edit['email'] ?? '') ?>"></label>
  <label><?= $edit ? 'Nova senha <span class="hint">(deixe em branco para manter a atual)</span>' : 'Senha' ?>
    <input name="senha" type="password" minlength="12" <?= $edit ? '' : 'required' ?>>
  </label>
  <label>Perfil
    <select name="tipo" <?= $editandoEuMesmo ? 'disabled' : '' ?>>
      <option value="usuario" <?= ($edit['tipo'] ?? '') === 'usuario' ? 'selected' : '' ?>>Usuário</option>
      <option value="administrador" <?= ($edit['tipo'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
    </select>
  </label>
  <?php if ($edit): ?>
  <label>Situação
    <select name="ativo" <?= $editandoEuMesmo ? 'disabled' : '' ?>>
      <option value="1" <?= (int)$edit['ativo'] === 1 ? 'selected' : '' ?>>Ativo</option>
      <option value="0" <?= (int)$edit['ativo'] === 0 ? 'selected' : '' ?>>Inativo (não consegue entrar)</option>
    </select>
  </label>
  <?php if ($editandoEuMesmo): ?><p class="hint">Você não pode alterar o seu próprio perfil nem desativar a própria conta.</p><?php endif; ?>
  <?php endif; ?>
  <button><?= $edit ? 'Salvar alterações' : 'Cadastrar usuário' ?></button>
  <?php if ($edit): ?><a class="button secondary" href="?page=usuarios">Cancelar</a><?php endif; ?>
</form>

<table>
  <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Ativo</th><th>Ações</th></tr>
  <?php while ($r = $rows->fetch_assoc()): ?>
  <tr>
    <td><?= h($r['nome']) ?></td>
    <td><?= h($r['email']) ?></td>
    <td><?= h($r['tipo']) ?></td>
    <td><?= $r['ativo'] ? 'Sim' : 'Não' ?></td>
    <td>
      <div class="actions">
        <a class="button" href="?page=usuarios&id=<?= (int)$r['id'] ?>">Editar</a>
        <?php if ((int)$r['id'] !== $meuId): ?>
        <form method="post" onsubmit="return confirm('Excluir este usuário?')">
          <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
          <input type="hidden" name="acao" value="excluir">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="danger">Excluir</button>
        </form>
        <?php endif; ?>
      </div>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
