<?php
// Configurações de email e WhatsApp

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu-email@gmail.com'); // ALTERE
define('SMTP_PASS', 'sua-senha-app'); // ALTERE
define('SMTP_FROM', 'seu-email@gmail.com'); // ALTERE
define('SMTP_NAME', 'Agenda Manicure');

define('WHATSAPP_ENABLED', false); // Desabilite se não quiser WhatsApp
define('WHATSAPP_NUMBER', '5517999999999');

define('ESTABELECIMENTO_NOME', 'Salão de Beleza XYZ');
define('ESTABELECIMENTO_ENDERECO', 'Rua Exemplo, 123 - Centro');
define('ESTABELECIMENTO_TELEFONE', '(17) 99999-9999');

function enviarEmail($para, $assunto, $mensagem, $html = true)
{
    // Retorna false por enquanto - descomente quando configurar
    return false;

    /*
    $headers = "MIME-Version: 1.0\r\n";
    if ($html) {
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    }
    $headers .= "From: " . SMTP_NAME . " <" . SMTP_FROM . ">\r\n";
    return mail($para, $assunto, $mensagem, $headers);
    */
}

function gerarLinkWhatsApp($numeroCliente, $mensagem)
{
    if (!WHATSAPP_ENABLED || empty($numeroCliente))
        return null;
    $numero = preg_replace('/[^0-9]/', '', $numeroCliente);
    if (strlen($numero) <= 11) {
        $numero = '55' . $numero;
    }
    $mensagemEncoded = urlencode($mensagem);
    return "https://wa.me/{$numero}?text={$mensagemEncoded}";
}

function emailAgendamentoConfirmado($nomeCliente, $servico, $dataHora)
{
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #c89b4b, #5a3a1c); color: white; padding: 30px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #c89b4b; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'><h1>💅 Agendamento Confirmado!</h1></div>
            <div class='content'>
                <p>Olá <strong>{$nomeCliente}</strong>,</p>
                <p>Seu agendamento foi confirmado!</p>
                <div class='info-box'>
                    <h3>📋 Detalhes:</h3>
                    <p><strong>Serviço:</strong> {$servico}</p>
                    <p><strong>Data/Hora:</strong> {$dataHora}</p>
                    <p><strong>Local:</strong> " . ESTABELECIMENTO_NOME . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

function emailAgendamentoRecusado($nomeCliente, $servico, $dataHora, $motivo = '')
{
    $motivoHtml = $motivo ? "<p><strong>Motivo:</strong> {$motivo}</p>" : "";
    return "<html><body><h2>Agendamento Não Confirmado</h2><p>Olá {$nomeCliente}, infelizmente não conseguimos confirmar.</p>{$motivoHtml}</body></html>";
}

function emailAgendamentoCancelado($nomeCliente, $servico, $dataHora, $motivo = '')
{
    $motivoHtml = $motivo ? "<p><strong>Motivo:</strong> {$motivo}</p>" : "";
    return "<html><body><h2>Agendamento Cancelado</h2><p>Olá {$nomeCliente}, seu agendamento foi cancelado.</p>{$motivoHtml}</body></html>";
}