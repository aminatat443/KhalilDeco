<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Version française et adaptée au parcours Khalil Déco (le compte n'est pas encore
     * connecté à ce stade — le lien connecte automatiquement une fois l'email vérifié).
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirmez votre adresse email — Khalil Déco')
            ->greeting('Bienvenue chez Khalil Déco !')
            ->line('Merci de confirmer votre adresse email pour activer votre compte.')
            ->action('Vérifier mon adresse email', $url)
            ->line('Ce lien expire dans 60 minutes.')
            ->line("Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement cet email.");
    }
}
