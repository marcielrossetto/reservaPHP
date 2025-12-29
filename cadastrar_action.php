// ... dentro da sua lógica de cadastro ...

$nome_empresa = "Restaurante do João";
$nome_usuario = "João Master";
$email = "joao@teste.com";
$senha = md5("123");

// 1. Cria a Empresa com data de expiração para daqui a 7 dias
$data_expiracao = date('Y-m-d H:i:s', strtotime('+7 days'));

$sql_emp = $pdo->prepare("INSERT INTO empresas (nome_empresa, data_expiracao, status) VALUES (:nome, :expira, 1)");
$sql_emp->bindValue(":nome", $nome_empresa);
$sql_emp->bindValue(":expira", $data_expiracao);
$sql_emp->execute();

$empresa_id = $pdo->lastInsertId();

// 2. Cria o Usuário Master vinculado a essa empresa
$sql_user = $pdo->prepare("INSERT INTO login (empresa_id, nome, email, senha, nivel, status) VALUES (:eid, :nome, :email, :senha, 'master', 1)");
$sql_user->bindValue(":eid", $empresa_id);
$sql_user->bindValue(":nome", $nome_usuario);
$sql_user->bindValue(":email", $email);
$sql_user->bindValue(":senha", $senha);
$sql_user->execute();