<?php
session_start();

// Verificar se o usuário já está logado
if (isset($_SESSION['user_id']) && isset($_SESSION['group_id']) && $_SESSION['group_id'] == 99) {
  header('Location: dashboard.php');
  exit;
}

// Processar o formulário de login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_once 'class/Conn.class.php';

  $userid = $_POST['userid'] ?? '';
  $password = $_POST['password'] ?? '';

  if (empty($userid) || empty($password)) {
    $error = 'Por favor, preencha todos os campos.';
  } else {
    $pdo = DB::getInstance();

    $stmt = $pdo->prepare("SELECT id, userid, user_pass, group_id FROM login WHERE userid = :userid");
    $stmt->bindParam(':userid', $userid);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['user_pass'])) {
      if ($user['group_id'] == 99) {
        // Login bem-sucedido para administrador
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['userid'] = $user['userid'];
        $_SESSION['group_id'] = $user['group_id'];

        header('Location: dashboard.php');
        exit;
      } else {
        $error = 'Você não tem permissão para acessar esta área.';
      }
    } else {
      $error = 'Usuário ou senha incorretos.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Painel Administrativo</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="max-w-md w-full bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="bg-blue-600 py-4 px-6">
      <h2 class="text-2xl font-bold text-white text-center">Painel Administrativo</h2>
    </div>
    <div class="p-6">
      <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
          <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-4">
          <label for="userid" class="block text-gray-700 text-sm font-bold mb-2">
            <i class="fas fa-user mr-2"></i>Usuário
          </label>
          <input type="text" id="userid" name="userid"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            placeholder="Digite seu usuário">
        </div>

        <div class="mb-6">
          <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
            <i class="fas fa-lock mr-2"></i>Senha
          </label>
          <input type="password" id="password" name="password"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            placeholder="Digite sua senha">
        </div>

        <div class="flex items-center justify-center">
          <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full transition duration-300 ease-in-out transform hover:scale-105">
            <i class="fas fa-sign-in-alt mr-2"></i>Entrar
          </button>
        </div>
      </form>
    </div>
    <div class="bg-gray-100 py-3 px-6 text-center text-sm text-gray-600">
      &copy; <?php echo date('Y'); ?> - Sistema de Administração de Pagamentos
    </div>
  </div>
</body>

</html>