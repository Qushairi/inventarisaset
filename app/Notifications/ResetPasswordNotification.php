<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(#[\SensitiveParameter] public string $token)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the password reset email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expiresInMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset Password - '.config('app.name'))
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Kami menerima permintaan untuk mereset password akun Anda.')
            ->action('Reset Password', $resetUrl)
            ->line("Tautan reset password ini berlaku selama {$expiresInMinutes} menit.")
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.')
            ->salutation('Salam, '.config('app.name'));
    }
}
