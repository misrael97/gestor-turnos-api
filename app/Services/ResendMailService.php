<?php

namespace App\Services;

use Resend;

class ResendMailService
{
    protected $resend;

    public function __construct()
    {
        $this->resend = Resend::client(config('services.resend.api_key'));
    }

    /**
     * Enviar código de verificación 2FA
     */
    public function sendTwoFactorCode($email, $name, $code)
    {
        try {
            $html = view('emails.two-factor-code', [
                'codigo' => $code,
                'nombre' => $name
            ])->render();

            $this->resend->emails->send([
                'from' => config('mail.from.address'),
                'to' => [$email],
                'subject' => 'Código de Verificación 2FA - Gestor de Turnos',
                'html' => $html,
            ]);

            \Log::info("Email 2FA enviado exitosamente a: {$email}");
            return true;
        } catch (\Exception $e) {
            \Log::error("Error enviando email 2FA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar email genérico
     */
    public function send($to, $subject, $view, $data = [])
    {
        try {
            $html = view($view, $data)->render();

            $this->resend->emails->send([
                'from' => config('mail.from.address'),
                'to' => is_array($to) ? $to : [$to],
                'subject' => $subject,
                'html' => $html,
            ]);

            \Log::info("Email enviado exitosamente a: " . (is_array($to) ? implode(', ', $to) : $to));
            return true;
        } catch (\Exception $e) {
            \Log::error("Error enviando email: " . $e->getMessage());
            return false;
        }
    }
}
