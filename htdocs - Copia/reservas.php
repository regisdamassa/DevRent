<?php
$sql='SELECT r.id,r.data_retirada,r.status,r.criado_em,u.nome AS usuario,e.nome AS equipamento,e.patrimonio FROM reservas r INNER JOIN usuarios u ON u.id=r.usuario_id INNER JOIN equipamentos e ON e.id=r.equipamento_id';
if(!admin()){$sql.=' WHERE r.usuario_id='.(int)$_SESSION['id'];}
$sql.=' ORDER BY r.id DESC';$rows=$conn->query($sql);
?><h1>Reservas</h1><p><a class="button" href="?page=reserva">Nova reserva</a></p><table><tr><th>ID</th><th>Usuário</th><th>Equipamento</th><th>Patrimônio</th><th>Retirada</th><th>Status</th><th>Ações</th></tr><?php while($r=$rows->fetch_assoc()):?><tr><td><?=h($r['id'])?></td><td><?=h($r['usuario'])?></td><td><?=h($r['equipamento'])?></td><td><?=h($r['patrimonio'])?></td><td><?=h($r['data_retirada'])?></td><td><?=h($r['status'])?></td><td><a href="?page=reserva&id=<?=$r['id']?>">Detalhes / editar</a></td></tr><?php endwhile;?></table>
