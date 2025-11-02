<?php
require 'config.php';

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$info = '';

// Verificar se foi timeout de sessão
if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $info = 'Sua sessão expirou. Faça login novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = "Preencha todos os campos";
    } elseif (!validateEmail($email)) {
        $error = "Email inválido";
    } else {
        // Verificar proteção contra brute force
        if (!checkLoginAttempts($email)) {
            $lockoutTime = (int)(getenv('LOCKOUT_TIME') ?: 900) / 60;
            $error = "Muitas tentativas de login. Tente novamente em {$lockoutTime} minutos.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Login bem-sucedido
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['last_activity'] = time();
                    
                    // Limpar tentativas de login
                    recordLoginAttempt($email, true);

                    // Registrar log de login
                    logAuditoria('login', "Login realizado por {$user['name']} ({$email})");

                    // Redirecionar
                    header("Location: index.php");
                    exit;
                } else {
                    // Login falhou
                    recordLoginAttempt($email, false);
                    $error = "Email ou senha incorretos";
                    
                    // Log de tentativa falha
                    error_log("Tentativa de login falha para: {$email} - IP: " . getUserIP());
                }
            } catch (PDOException $e) {
                error_log("Erro no login: " . $e->getMessage());
                $error = "Erro ao processar login. Tente novamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                <div class="logo">💅</div>
            </div>
            
            <h2>Login</h2>
            <p style="margin-bottom: 30px; color: #666;">Acesse sua agenda</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($info): ?>
                <div class="alert alert-info">
                    ℹ️ <?php echo htmlspecialchars($info); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="login-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="seu@email.com" 
                        class="input" 
                        required
                        autocomplete="email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        placeholder="Sua senha" 
                        class="input" 
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin: 0; font-size: 0.9rem;">
                        <input type="checkbox" name="remember" style="width: auto;">
                        Lembrar-me
                    </label>
                    <a href="recuperar_senha.php" style="font-size: 0.9rem; color: var(--gold);">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>

            <p style="margin-top: 24px; text-align: center;">
                Não tem conta? <a href="register.php">Cadastre-se aqui</a>
            </p>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--beige-light); text-align: center;">
                <p style="font-size: 0.85rem; color: #999;">
                    <strong><?php echo SITE_NAME; ?></strong><br>
                    <?php echo SITE_PHONE; ?>
                </p>
            </div>
        </div>
    </div>

    <style>
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }

        .auth-card a {
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .auth-card a:hover {
            color: var(--brown);
        }
    </style>

    <script>
        // Validação do formulário no frontend
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos');
                return false;
            }

            // Validação básica de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Por favor, insira um email válido');
                return false;
            }
        });
    </script>
</body>

</html>
