<?php
if(isset($_SESSION['id']))go('reservas');
if($_SERVER['REQUEST_METHOD']==='POST'){
try{verify_csrf();$email=trim($_POST['email']??'');$pass=$_POST['senha']??'';$s=$conn->prepare('SELECT id,nome,senha,tipo FROM usuarios WHERE email=? AND ativo=1');$s->bind_param('s',$email);$s->execute();$u=$s->get_result()->fetch_assoc();if(!$u||!password_verify($pass,$u['senha']))throw new RuntimeException('Credenciais inválidas.');session_regenerate_id(true);$_SESSION['id']=(int)$u['id'];$_SESSION['nome']=$u['nome'];$_SESSION['tipo']=$u['tipo'];go('reservas');}catch(Throwable $e){$error=$e->getMessage();}}
?><h1>Entrar no DevRent</h1><?php if(isset($error)):?><p class="alert"><?=h($error)?></p><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><label>E-mail<input type="email" name="email" required></label><label>Senha<input type="password" name="senha" required></label><button>Entrar</button></form>
