<?php
// =====================================================
// CONFIGURAÇÃO PRINCIPAL DO SISTEMA
// =====================================================

// Iniciar sessão com configurações seguras
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mude para 1 se usar HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

// =====================================================
// CARREGAR VARIÁVEIS DE AMBIENTE (.env)
// =====================================================
function loadEnv($path = __DIR__ . '/.env')
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Ignorar comentários
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

loadEnv();

// =====================================================
// CONFIGURAÇÕES DE TIMEZONE
// =====================================================
date_default_timezone_set(getenv('TIMEZONE') ?: 'America/Sao_Paulo');

// =====================================================
// CONFIGURAÇÕES DE ERRO
// =====================================================
$isDebug = getenv('APP_DEBUG') === 'true';
if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php-errors.log');
}

// =====================================================
// CONEXÃO COM BANCO DE DADOS
// =====================================================
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db = getenv('DB_NAME') ?: 'agenda_manicure';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    error_log("Erro de conexão: " . $e->getMessage());
    die("Erro ao conectar ao banco de dados. Verifique as configurações.");
}

// =====================================================
// FUNÇÕES DE SEGURANÇA - CSRF PROTECTION
// =====================================================
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > (int)(getenv('CSRF_TOKEN_LIFETIME') ?: 3600)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }

    $lifetime = (int)(getenv('CSRF_TOKEN_LIFETIME') ?: 3600);
    if ((time() - $_SESSION['csrf_token_time']) > $lifetime) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRF()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!validateCSRFToken($token)) {
            http_response_code(403);
            die(json_encode(['status' => 'error', 'msg' => 'Token CSRF inválido ou expirado']));
        }
    }
}

// =====================================================
// FUNÇÕES DE AUTENTICAÇÃO E AUTORIZAÇÃO
// =====================================================
function checkLogin()
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
        if (isAjaxRequest()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'msg' => 'Não autenticado', 'redirect' => 'login.php']);
            exit;
        } else {
            header("Location: login.php");
            exit;
        }
    }

    // Verificar timeout de sessão
    $sessionLifetime = (int)(getenv('SESSION_LIFETIME') ?: 7200);
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $sessionLifetime) {
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function isDono()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'dono';
}

function isFuncionario()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'funcionario';
}

function isDonoOrFuncionario()
{
    return isDono() || isFuncionario();
}

function requireDono()
{
    checkLogin();
    if (!isDono()) {
        http_response_code(403);
        if (isAjaxRequest()) {
            echo json_encode(['status' => 'error', 'msg' => 'Acesso negado. Apenas donos podem acessar.']);
        } else {
            die('Acesso negado. Apenas donos podem acessar esta página.');
        }
        exit;
    }
}

function requireDonoOrFuncionario()
{
    checkLogin();
    if (!isDonoOrFuncionario()) {
        http_response_code(403);
        if (isAjaxRequest()) {
            echo json_encode(['status' => 'error', 'msg' => 'Acesso negado.']);
        } else {
            die('Acesso negado.');
        }
        exit;
    }
}

// =====================================================
// FUNÇÕES DE VALIDAÇÃO DE PERMISSÕES
// =====================================================
function canEditEvent($eventId, $pdo)
{
    $userId = $_SESSION['user_id'] ?? 0;
    $role = $_SESSION['role'] ?? 'cliente';

    // Dono pode editar qualquer coisa
    if ($role === 'dono') {
        return true;
    }

    // Cliente só pode editar seus próprios eventos
    $stmt = $pdo->prepare("SELECT user_id FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    return $event && $event['user_id'] == $userId;
}

function canCancelEvent($eventId, $pdo)
{
    global $pdo;
    
    $userId = $_SESSION['user_id'] ?? 0;
    $role = $_SESSION['role'] ?? 'cliente';

    // Dono sempre pode cancelar
    if ($role === 'dono') {
        return ['can' => true, 'reason' => ''];
    }

    // Buscar evento
    $stmt = $pdo->prepare("SELECT user_id, start, status FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        return ['can' => false, 'reason' => 'Evento não encontrado'];
    }

    // Verificar se é o dono do evento
    if ($event['user_id'] != $userId) {
        return ['can' => false, 'reason' => 'Você não pode cancelar eventos de outros usuários'];
    }

    // Verificar se já foi cancelado
    if ($event['status'] === 'cancelado') {
        return ['can' => false, 'reason' => 'Este evento já foi cancelado'];
    }

    // Verificar prazo de antecedência
    $antecedencia = (int)(getConfiguracao('antecedencia_cancelamento', 2));
    $prazoMinimo = strtotime($event['start']) - ($antecedencia * 3600);

    if (time() > $prazoMinimo) {
        return [
            'can' => false, 
            'reason' => "Cancelamento deve ser feito com {$antecedencia}h de antecedência. Entre em contato conosco."
        ];
    }

    return ['can' => true, 'reason' => ''];
}

// =====================================================
// FUNÇÕES DE CONFIGURAÇÃO
// =====================================================
function getConfiguracao($chave, $default = null)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
        $stmt->execute([$chave]);
        $config = $stmt->fetch();
        
        if ($config) {
            return $config['valor'];
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar configuração: " . $e->getMessage());
    }
    
    return $default;
}

function setConfiguracao($chave, $valor, $descricao = '', $tipo = 'text')
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO configuracoes (chave, valor, descricao, tipo)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE valor = ?, descricao = ?, tipo = ?
        ");
        return $stmt->execute([$chave, $valor, $descricao, $tipo, $valor, $descricao, $tipo]);
    } catch (PDOException $e) {
        error_log("Erro ao definir configuração: " . $e->getMessage());
        return false;
    }
}

// =====================================================
// FUNÇÕES DE VALIDAÇÃO
// =====================================================
function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 11;
}

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validateTime($time)
{
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
}

// =====================================================
// FUNÇÕES AUXILIARES
// =====================================================
function isAjaxRequest()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

function formatCurrency($value)
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function formatPhone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 11) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
    } elseif (strlen($phone) == 10) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6);
    }
    return $phone;
}

function formatDateBR($date)
{
    if (empty($date)) return '';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date('d/m/Y', $timestamp);
}

function formatDateTimeBR($datetime)
{
    if (empty($datetime)) return '';
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date('d/m/Y H:i', $timestamp);
}

// =====================================================
// FUNÇÃO DE LOG DE AUDITORIA
// =====================================================
function logAuditoria($acao, $detalhes = '', $event_id = null)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO historico (event_id, acao, descricao, user_id, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $event_id,
            $acao,
            $detalhes,
            $_SESSION['user_id'] ?? null,
            getUserIP()
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
        return false;
    }
}

// =====================================================
// FUNÇÃO DE PROTEÇÃO CONTRA BRUTE FORCE
// =====================================================
function checkLoginAttempts($email)
{
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $maxAttempts = (int)(getenv('MAX_LOGIN_ATTEMPTS') ?: 5);
    $lockoutTime = (int)(getenv('LOCKOUT_TIME') ?: 900); // 15 minutos

    // Limpar tentativas antigas
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        function ($attempt) use ($lockoutTime) {
            return (time() - $attempt['time']) < $lockoutTime;
        }
    );

    // Contar tentativas para este email
    $attempts = array_filter(
        $_SESSION['login_attempts'],
        function ($attempt) use ($email) {
            return $attempt['email'] === $email;
        }
    );

    return count($attempts) < $maxAttempts;
}

function recordLoginAttempt($email, $success = false)
{
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    if ($success) {
        // Limpar tentativas em caso de sucesso
        $_SESSION['login_attempts'] = array_filter(
            $_SESSION['login_attempts'],
            function ($attempt) use ($email) {
                return $attempt['email'] !== $email;
            }
        );
    } else {
        // Registrar tentativa falha
        $_SESSION['login_attempts'][] = [
            'email' => $email,
            'time' => time()
        ];
    }
}

// =====================================================
// CONSTANTES DO SISTEMA
// =====================================================
define('SITE_NAME', getenv('ESTABELECIMENTO_NOME') ?: 'Agenda Manicure');
define('SITE_EMAIL', getenv('ESTABELECIMENTO_EMAIL') ?: 'contato@example.com');
define('SITE_PHONE', getenv('ESTABELECIMENTO_TELEFONE') ?: '(00) 00000-0000');
define('SITE_ADDRESS', getenv('ESTABELECIMENTO_ENDERECO') ?: '');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_MAX_SIZE', (int)(getenv('UPLOAD_MAX_SIZE') ?: 5242880)); // 5MB
define('ALLOWED_EXTENSIONS', explode(',', getenv('ALLOWED_EXTENSIONS') ?: 'jpg,jpeg,png,gif,webp'));

// =====================================================
// FIM DA CONFIGURAÇÃO
// =====================================================
