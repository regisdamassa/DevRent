<?php
require 'config.php';
$page=$_GET['page']??'reservas';
if(!isset($_SESSION['id']) && $page!=='login')go('login');
$routes=['login'=>'login.php','reservas'=>'reservas.php','reserva'=>'reserva.php','usuarios'=>'usuarios.php','equipamentos'=>'equipamentos.php','categorias'=>'categorias.php','historico'=>'historico.php'];
if(!isset($routes[$page]))$page='reservas';
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>DevRent</title><link rel="stylesheet" href="css/style.css"></head><body>
<?php if(isset($_SESSION['id'])): ?><nav><b>DevRent</b><a href="?page=reservas">Reservas</a><a href="?page=equipamentos">Equipamentos</a><a href="?page=categorias">Categorias</a><a href="?page=usuarios">Usuários</a><a href="?page=historico">Histórico</a><span><?=h($_SESSION['nome'])?></span><a href="logout.php">Sair</a></nav><?php endif; ?>
<main><?php if(isset($_SESSION['flash'])): $f=$_SESSION['flash'];unset($_SESSION['flash']);?><p class="alert"><?=h($f[0])?></p><?php endif;?><?php require $routes[$page];?></main></body></html>
