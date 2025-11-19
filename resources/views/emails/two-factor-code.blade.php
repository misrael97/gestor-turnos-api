<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación - Gestor de Turnos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #3880ff 0%, #5260ff 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo svg {
            width: 50px;
            height: 50px;
            fill: #ffffff;
        }
        
        .header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        
        .message {
            font-size: 16px;
            color: #666666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .code-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px dashed #3880ff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        
        .code-label {
            font-size: 14px;
            color: #666666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .code {
            font-size: 48px;
            font-weight: 700;
            color: #3880ff;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .code-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            padding: 12px;
            background-color: #fff3cd;
            border-radius: 8px;
        }
        
        .code-info svg {
            width: 20px;
            height: 20px;
            fill: #856404;
        }
        
        .code-info span {
            font-size: 14px;
            color: #856404;
            font-weight: 500;
        }
        
        .security-notice {
            background-color: #e7f3ff;
            border-left: 4px solid #3880ff;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        
        .security-notice p {
            font-size: 14px;
            color: #004085;
            line-height: 1.6;
            margin: 0;
        }
        
        .security-notice strong {
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .footer p {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .footer-links {
            margin-top: 20px;
        }
        
        .footer-links a {
            color: #3880ff;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
        }
        
        @media only screen and (max-width: 600px) {
            .email-container {
                border-radius: 0;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .code {
                font-size: 36px;
                letter-spacing: 6px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V7.3l7-3.11v8.8z"/>
                </svg>
            </div>
            <h1>Gestor de Turnos</h1>
            <p>Sistema de Verificación Segura</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="greeting">¡Hola {{ $nombre }}!</div>
            
            <p class="message">
                Has iniciado sesión en tu cuenta de <strong>Gestor de Turnos</strong>. 
                Para completar el proceso y garantizar la seguridad de tu cuenta, 
                por favor utiliza el siguiente código de verificación:
            </p>
            
            <!-- Code Container -->
            <div class="code-container">
                <div class="code-label">Tu Código de Verificación</div>
                <div class="code">{{ $codigo }}</div>
                <div class="code-info">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                    <span>Este código expirará en <strong>10 minutos</strong></span>
                </div>
            </div>
            
            <!-- Security Notice -->
            <div class="security-notice">
                <p>
                    <strong>🔒 Aviso de Seguridad</strong>
                    Si no iniciaste sesión recientemente, ignora este mensaje y asegúrate de que tu cuenta esté protegida. 
                    Nunca compartas este código con nadie, ni siquiera con el equipo de soporte.
                </p>
            </div>
            
            <div class="divider"></div>
            
            <p class="message" style="margin-bottom: 0;">
                Si tienes problemas para iniciar sesión, contacta a nuestro equipo de soporte.
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Gestor de Turnos</strong></p>
            <p style="font-size: 13px; color: #868e96;">
                Este es un correo automático, por favor no respondas a este mensaje.
            </p>
            <div class="footer-links">
                <a href="#">Centro de Ayuda</a>
                <a href="#">Política de Privacidad</a>
                <a href="#">Términos de Uso</a>
            </div>
            <p style="margin-top: 20px; font-size: 12px; color: #adb5bd;">
                © 2025 Gestor de Turnos. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>
