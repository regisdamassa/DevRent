<?php
require 'config.php';
$count=(int)$conn->query('SELECT COUNT(*) FROM usuarios')->fetch_row()[0];
if($count>0)die('Configuração inicial encerrada. Remova este arquivo.');
if($_SERVER['REQUEST_METHOD']==='POST'){
try{verify_csrf();$nome=trim($_POST['nome']??'');$email=trim($_POST['email']??'');$senha=$_POST['senha']??'';if(!$nome||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($senha)<12)throw new RuntimeException('Informe nome, e-mail válido e senha com pelo menos 12 caracteres.');$hash=password_hash($senha,PASSWORD_DEFAULT);$s=$conn->prepare("INSERT INTO usuarios(nome,email,senha,tipo) VALUES(?,?,?,'administrador')");$s->bind_param('sss',$nome,$email,$hash);$s->execute();die('Administrador criado. APAGUE setup_admin.php e acesse index.php.');}catch(Throwable $e){$error=$e->getMessage();}}
?><!doctype html><meta charset="utf-8"><title>Configuração inicial</title><h1>Criar administrador inicial</h1><?php if(isset($error))echo htmlspecialchars($error);?><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf())?>"><input name="nome" placeholder="Nome" required><input type="email" name="email" placeholder="E-mail" required><input type="password" name="senha" placeholder="Senha forte (12+ caracteres)" required minlength="12"><button>Criar</button></form>
