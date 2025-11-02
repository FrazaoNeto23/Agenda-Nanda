<?php
require 'config.php';

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $whatsapp = sanitizeInput($_POST['whatsapp'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? '');

    // Validações
    if (!$name || !$email || !$password || !$role) {
        $error = "Preencha todos os campos obrigatórios";
    } elseif (!validateEmail($email)) {
        $error = "Email inválido";
    } elseif (strlen($password) < 6) {
        $error = "A senha deve ter pelo menos 6 caracteres";
    } elseif ($password !== $password_confirm) {
        $error = "As senhas não coincidem";
    } elseif (!in_array($role, ['cliente', 'dono'])) {
        $error = "Perfil inválido";
    } elseif (!empty($phone) && !validatePhone($phone)) {
        $error = "Telefone inválido. Use o formato (00) 00000-0000";
    } elseif (!empty($whatsapp) && !validatePhone($whatsapp)) {
        $error = "WhatsApp inválido. Use o formato (00) 00000-0000";
    } else {
        try {
            // Verificar se o email já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "Este email já está cadastrado";
            } else {
                // Limpar telefones (remover formatação)
                $phone_clean = preg_replace('/[^0-9]/', '', $phone);
                $whatsapp_clean = preg_replace('/[^0-9]/', '', $whatsapp);

                // Se WhatsApp não foi informado, usar o telefone
                if (empty($whatsapp_clean) && !empty($phone_clean)) {
                    $whatsapp_clean = $phone_clean;
                }

                // Inserir usuário
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, whatsapp, password, role, active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt->execute([
                    $name,
                    $email,
                    $phone_clean ?: null,
                    $whatsapp_clean ?: null,
                    $hashedPassword,
                    $role
                ]);

                $user_id = $pdo->lastInsertId();

                // Registrar log
                logAuditoria('registro', "Novo usuário cadastrado: {$name} ({$email}) - Perfil: {$role}");

                // Auto-login após cadastro
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['last_activity'] = time();

                $success = "Cadastro realizado com sucesso! Redirecionando...";
                
                // Redirecionar após 2 segundos
                header("refresh:2;url=index.php");
            }
        } catch (PDOException $e) {
            error_log("Erro no cadastro: " . $e->getMessage());
            $error = "Erro ao cadastrar. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 520px;">
            <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                <div class="logo">💅</div>
            </div>
            
            <h2>Cadastro</h2>
            <p style="margin-bottom: 30px; color: #666;">Crie sua conta</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="register-form">
                <div class="form-group">
                    <label for="name">Nome Completo *</label>
                    <input 
                        type="text" 
                        id="name"
                        name="name" 
                        placeholder="Seu nome completo" 
                        class="input" 
                        required
                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="seu@email.com" 
                        class="input" 
                        required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Telefone</label>
                        <input 
                            type="tel" 
                            id="phone"
                            name="phone" 
                            placeholder="(00) 00000-0000" 
                            class="input phone-mask"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="whatsapp">WhatsApp</label>
                        <input 
                            type="tel" 
                            id="whatsapp"
                            name="whatsapp" 
                            placeholder="(00) 00000-0000" 
                            class="input phone-mask"
                            value="<?php echo isset($_POST['whatsapp']) ? htmlspecialchars($_POST['whatsapp']) : ''; ?>"
                        >
                        <small style="color: #999; font-size: 0.8rem;">Se vazio, usaremos o telefone</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Senha *</label>
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            placeholder="Mínimo 6 caracteres" 
                            class="input" 
                            required
                            minlength="6"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirmar Senha *</label>
                        <input 
                            type="password" 
                            id="password_confirm"
                            name="password_confirm" 
                            placeholder="Digite novamente" 
                            class="input" 
                            required
                            minlength="6"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Perfil *</label>
                    <select name="role" id="role" class="input" required>
                        <option value="">Selecione o perfil</option>
                        <option value="cliente" <?php echo (isset($_POST['role']) && $_POST['role'] === 'cliente') ? 'selected' : ''; ?>>
                            👤 Cliente - Agendar serviços
                        </option>
                        <option value="dono" <?php echo (isset($_POST['role']) && $_POST['role'] === 'dono') ? 'selected' : ''; ?>>
                            👑 Dono - Gerenciar agenda
                        </option>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label style="display: flex; align-items: start; gap: 10px; font-size: 0.9rem; font-weight: 400;">
                        <input type="checkbox" required style="width: auto; margin-top: 4px;">
                        <span>
                            Aceito os <a href="termos.php" target="_blank">Termos de Uso</a> e 
                            <a href="privacidade.php" target="_blank">Política de Privacidade</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </form>

            <p style="margin-top: 24px; text-align: center;">
                Já tem conta? <a href="login.php">Entrar</a>
            </p>
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

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        // Máscara de telefone
        document.querySelectorAll('.phone-mask').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                } else {
                    value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                }
                
                e.target.value = value;
            });
        });

        // Auto-preencher WhatsApp com telefone se vazio
        document.getElementById('phone').addEventListener('blur', function() {
            const whatsappInput = document.getElementById('whatsapp');
            if (!whatsappInput.value && this.value) {
                whatsappInput.value = this.value;
            }
        });

        // Validação do formulário
        document.getElementById('register-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;

            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres!');
                return false;
            }
        });
    </script>
</body>

</html>
