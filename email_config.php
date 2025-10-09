<?php
// Configurações de email e WhatsApp

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu-email@gmail.com'); // ALTERE
define('SMTP_PASS', 'sua-senha-app'); // ALTERE - Use senha de app do Gmail
define('SMTP_FROM', 'seu-email@gmail.com'); // ALTERE
define('SMTP_NAME', 'Agenda Manicure');

define('WHATSAPP_ENABLED', true);
define('WHATSAPP_NUMBER', '5517999999999'); // ALTERE: Número com DDI+DDD

define('ESTABELECIMENTO_NOME', 'Salão de Beleza XYZ'); // ALTERE
define('ESTABELECIMENTO_ENDERECO', 'Rua Exemplo, 123 - Centro'); // ALTERE
define('ESTABELECIMENTO_TELEFONE', '(17) 99999-9999'); // ALTERE

function enviarEmail($para, $assunto, $mensagem, $html = true)
{
    $headers = "MIME-Version: 1.0\r\n";
    if ($html) {
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "Content-type: text/plain; charset=UTF-8\r\n";
    }
    $headers .= "From: " . SMTP_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";

    return mail($para, $assunto, $mensagem, $headers);
}

function gerarLinkWhatsApp($numeroCliente, $mensagem)
{
    if (!WHATSAPP_ENABLED)
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
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #c89b4b, #5a3a1c); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #c89b4b; border-radius: 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>💅 Agendamento Confirmado!</h1>
            </div>
            <div class='content'>
                <p>Olá <strong>{$nomeCliente}</strong>,</p>
                <p>Seu agendamento foi <strong style='color: #4caf50;'>CONFIRMADO</strong> com sucesso!</p>
                
                <div class='info-box'>
                    <h3>📋 Detalhes do Agendamento:</h3>
                    <p><strong>Serviço:</strong> {$servico}</p>
                    <p><strong>Data e Hora:</strong> {$dataHora}</p>
                    <p><strong>Local:</strong> " . ESTABELECIMENTO_NOME . "</p>
                    <p><strong>Endereço:</strong> " . ESTABELECIMENTO_ENDERECO . "</p>
                </div>
                
                <p>Estamos ansiosos para atendê-la! 💖</p>
                <p>Se precisar de qualquer alteração, entre em contato: " . ESTABELECIMENTO_TELEFONE . "</p>
                
                <div class='footer'>
                    <p>Este é um email automático, por favor não responda.</p>
                    <p>" . ESTABELECIMENTO_NOME . " | " . ESTABELECIMENTO_ENDERECO . "</p>
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

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #f44336, #c62828); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #f44336; border-radius: 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>😔 Agendamento Não Confirmado</h1>
            </div>
            <div class='content'>
                <p>Olá <strong>{$nomeCliente}</strong>,</p>
                <p>Infelizmente não conseguimos confirmar seu agendamento.</p>
                
                <div class='info-box'>
                    <h3>📋 Detalhes do Agendamento:</h3>
                    <p><strong>Serviço:</strong> {$servico}</p>
                    <p><strong>Data e Hora:</strong> {$dataHora}</p>
                    {$motivoHtml}
                </div>
                
                <p>Entre em contato conosco para agendar outro horário!</p>
                <p><strong>Telefone/WhatsApp:</strong> " . ESTABELECIMENTO_TELEFONE . "</p>
                
                <div class='footer'>
                    <p>Este é um email automático, por favor não responda.</p>
                    <p>" . ESTABELECIMENTO_NOME . " | " . ESTABELECIMENTO_ENDERECO . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

function emailAgendamentoCancelado($nomeCliente, $servico, $dataHora, $motivo = '')
{
    $motivoHtml = $motivo ? "<p><strong>Motivo:</strong> {$motivo}</p>" : "";

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #ff9800, #f57c00); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #ff9800; border-radius: 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>❌ Agendamento Cancelado</h1>
            </div>
            <div class='content'>
                <p>Olá <strong>{$nomeCliente}</strong>,</p>
                <p>Seu agendamento foi cancelado conforme solicitado.</p>
                
                <div class='info-box'>
                    <h3>📋 Detalhes do Agendamento Cancelado:</h3>
                    <p><strong>Serviço:</strong> {$servico}</p>
                    <p><strong>Data e Hora:</strong> {$dataHora}</p>
                    {$motivoHtml}
                </div>
                
                <p>Esperamos vê-la em breve! Será um prazer atendê-la novamente. 💖</p>
                <p>Para novo agendamento, acesse o sistema ou entre em contato: " . ESTABELECIMENTO_TELEFONE . "</p>
                
                <div class='footer'>
                    <p>Este é um email automático, por favor não responda.</p>
                    <p>" . ESTABELECIMENTO_NOME . " | " . ESTABELECIMENTO_ENDERECO . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}