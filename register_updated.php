<?php
require 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $whatsapp = $_POST['whatsapp'] ?? '';
  $password = $_POST['password'] ?? '';
  $role = $_POST['role'] ?? '';
  $receive_reminders = isset($_POST['receive_reminders']) ? 1 : 0;

  if (!$name || !$email || !$password || !$role) {
    $error = "Preencha todos os campos obrigatórios";
  } else {
    // Limpar WhatsApp (remover caracteres especiais)
    $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, whatsapp, password, role, receive_reminders) VALUES (?,?,?,?,?,?)");
    try {
      $stmt->execute([
        $name, 
        $email, 
        $whatsapp, 
        password_hash($password, PASSWORD_DEFAULT), 
        $role,
        $receive_reminders
      ]);
      header("Location: login.php");
      exit;
    } catch (PDOException $e) {
      $error = "Email já cadastrado!";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Cadastro - Agenda Manicure</title>
  <link href="styles.css" rel="stylesheet">
  <style>
    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 15px 0;
      text-align: left;
    }
    
    .checkbox-group input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
    }
    
    .checkbox-group label {
      cursor: pointer;
      font-size: 0.9rem;
      color: var(--brown);
    }
  </style>
</head>

<body>
  <div class="auth-container">
    <div class="auth-card">
      <div style="display: flex; justify-content: center; margin-bottom: 20px;">
        <div class="logo">💅</div>
      </div>
      <h2>Cadastro</h2>
      <p style="margin-bottom: 30px;">Crie sua conta</p>
      
      <?php if ($error) echo "<p class='error-msg'>$error</p>"; ?>
      
      <form method="POST">
        <input type="text" name="name" placeholder="Nome completo" class="input" required>
        
        <input type="email" name="email" placeholder="Seu e-mail" class="input" required>
        
        <input 
          type="tel" 
          name="whatsapp" 
          placeholder="WhatsApp (11) 99999-9999" 
          class="input"
          pattern="\([0-9]{2}\) [0-9]{4,5}-[0-9]{4}"
          title="Formato: (00) 00000-0000"
        >
        <small style="color: #666; font-size: 0.85rem; display: block; margin-top: -10px; margin-bottom: 15px;">
          * Opcional - Para receber lembretes via WhatsApp
        </small>
        
        <input type="password" name="password" placeholder="Senha" class="input" required>
        
        <select name="role" class="input" required>
          <option value="">Selecione o perfil</option>
          <option value="cliente">Cliente</option>
          <option value="dono">Dono</option>
        </select>
        
        <div class="checkbox-group">
          <input type="checkbox" id="receive_reminders" name="receive_reminders" checked>
          <label for="receive_reminders">
            Desejo receber lembretes de agendamento por WhatsApp e E-mail
          </label>
        </div>
        
        <button type="submit" class="btn">Cadastrar</button>
      </form>
      
      <p>Já tem conta? <a href="login.php">Entrar</a></p>
    </div>
  </div>
  
  <script>
    // Máscara de telefone
    document.querySelector('input[name="whatsapp"]').addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      
      if (value.length <= 11) {
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d)(\d{4})$/, '$1-$2');
      }
      
      e.target.value = value;
    });
  </script>
</body>

</html>
