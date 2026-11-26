<?php
if(session_status()===PHP_SESSION_NONE) session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn=new mysqli('sql101.infinityfree.com','if0_42858433','ZNPZLM0633J9','if0_42858433_db_devrent_1');
$conn->set_charset('utf8mb4');
function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function admin(){return ($_SESSION['tipo']??'')==='administrador';}
function flash($msg,$type='success'){$_SESSION['flash']=[$msg,$type];}
function go($page){header('Location: index.php?page='.$page);exit;}
function csrf(){return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function verify_csrf(){if(!hash_equals(csrf(),$_POST['csrf']??''))throw new RuntimeException('Token de segurança inválido.');}
function log_event($conn,$id,$action,$msg=''){$uid=(int)$_SESSION['id'];$s=$conn->prepare('INSERT INTO historico_reservas(reserva_id,usuario_id,acao,observacao) VALUES(?,?,?,?)');$s->bind_param('iiss',$id,$uid,$action,$msg);$s->execute();}
