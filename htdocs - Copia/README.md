# DevRent — PHP + MySQL

Sistema de reservas com autenticação, cadastro de usuários, equipamentos e categorias, histórico e consultas usando `INNER JOIN`.

## Instalação local (banco novo)

1. Inicie Apache e MySQL no XAMPP ou USBWebserver.
2. Copie esta pasta para o diretório público (`htdocs/devrent`, por exemplo).
3. No phpMyAdmin, crie o banco `db_devrent` e **importe `database.sql`**.
4. Ajuste host, usuário, senha e banco em `config.php` (`root`/`usbw` é um exemplo do USBWebserver).
5. Abra `http://localhost/devrent/setup_admin.php` e crie o primeiro administrador com senha forte.
6. **Apague `setup_admin.php` imediatamente após criar o administrador.**
7. Entre em `http://localhost/devrent/index.php`, cadastre categorias, equipamentos e usuários e depois faça reservas.

## Banco de dados existente

**Atenção:** `database.sql` é para uma instalação nova. Se você já tem a tabela `reservas` antiga, `CREATE TABLE IF NOT EXISTS` não atualiza sua estrutura. Faça backup e migre as colunas/dados antigos antes de executar a versão nova. Não apague dados existentes.

## Regras

- Apenas administradores cadastram usuários, categorias e equipamentos e aprovam retiradas.
- Usuários comuns consultam e alteram apenas as próprias reservas.
- Reservas em andamento ocupam o equipamento; cancelamento e devolução o liberam.
- O campo `criado_em` não é modificado em edições.
- Consultas de reservas, equipamentos e histórico usam `INNER JOIN`.
- Este é um projeto didático. Antes de colocar em produção, revise permissões, políticas de senha, logs, tratamento de erros e proteção contra abuso.
