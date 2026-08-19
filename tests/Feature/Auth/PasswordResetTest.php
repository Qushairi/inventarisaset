<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('name="email"', escape: false);
        $response->assertSee('action="'.route('password.email').'"', escape: false);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'reset-token',
            'email' => 'pegawai@example.test',
        ]));

        $response->assertOk();
        $response->assertSee('name="token"', escape: false);
        $response->assertSee('value="reset-token"', escape: false);
        $response->assertSee('name="email"', escape: false);
        $response->assertSee('name="password"', escape: false);
        $response->assertSee('name="password_confirmation"', escape: false);
        $response->assertSee('action="'.route('password.store').'"', escape: false);
    }

    public function test_reset_link_notification_contains_the_reset_url(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $notification = $this->resetNotificationSentTo($user);

        $this->assertNotSame('', $notification->token);
        $this->assertSame(
            route('password.reset', [
                'token' => $notification->token,
                'email' => $user->email,
            ]),
            $notification->toMail($user)->actionUrl,
        );
    }

    public function test_reset_email_uses_the_application_branding(): void
    {
        config(['app.name' => 'Sistem Inventaris Aset']);

        $user = User::factory()->create([
            'name' => 'Ari',
        ]);
        $notification = new ResetPasswordNotification('reset-token');
        $mail = $notification->toMail($user);
        $html = (string) $mail->render();

        $this->assertSame('Reset Password - Sistem Inventaris Aset', $mail->subject);
        $this->assertSame('Salam, Sistem Inventaris Aset', $mail->salutation);
        $this->assertStringContainsString('Sistem Inventaris Aset', $html);
        $this->assertStringContainsString('Dinas Pendidikan Kabupaten Bengkalis', $html);
        $this->assertStringNotContainsString('Laravel', $html);
    }

    public function test_unknown_email_receives_the_same_generic_success_response(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $knownResponse = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $knownResponse->assertRedirect();
        $knownResponse->assertSessionHasNoErrors();
        $knownResponse->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $knownLocation = $knownResponse->headers->get('Location');
        $knownStatus = session('status');

        Notification::fake();

        $unknownResponse = $this->post(route('password.email'), [
            'email' => 'tidak-terdaftar@example.test',
        ]);

        $unknownResponse->assertRedirect();
        $unknownResponse->assertSessionHasNoErrors();
        $unknownResponse->assertSessionHas('status');
        $this->assertSame($knownLocation, $unknownResponse->headers->get('Location'));
        $this->assertSame($knownStatus, session('status'));
        Notification::assertNothingSent();
    }

    public function test_email_is_validated_before_requesting_a_reset_link(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'bukan-email',
        ]);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_and_the_token_is_consumed(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => 'PasswordLama123!',
            'remember_token' => 'remember-token-before-reset',
        ]);
        $notification = $this->requestResetNotification($user);

        $response = $this->post(route('password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();

        $user->refresh();

        $this->assertTrue(Hash::check('PasswordBaru123!', $user->password));
        $this->assertNotSame('remember-token-before-reset', $user->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_cannot_be_reset_with_an_invalid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => 'PasswordLama123!',
        ]);
        $this->requestResetNotification($user);

        $response = $this->post(route('password.store'), [
            'token' => 'token-yang-salah',
            'email' => $user->email,
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('PasswordLama123!', $user->fresh()->password));
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_resetting_password_revokes_existing_database_sessions(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $notification = $this->requestResetNotification($user);

        DB::table('sessions')->insert([
            'id' => 'active-session-before-reset',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        config(['session.driver' => 'database']);

        $response = $this->post(route('password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('sessions', [
            'id' => 'active-session-before-reset',
        ]);
    }

    public function test_reset_password_fields_are_validated(): void
    {
        $response = $this->post(route('password.store'), [
            'token' => '',
            'email' => 'bukan-email',
            'password' => 'pendek',
            'password_confirmation' => 'berbeda',
        ]);

        $response->assertSessionHasErrors(['token', 'email', 'password']);
    }

    public function test_smtp_mailer_requires_tls(): void
    {
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertTrue(config('mail.mailers.smtp.require_tls'));
    }

    private function requestResetNotification(User $user): ResetPasswordNotification
    {
        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');

        return $this->resetNotificationSentTo($user);
    }

    private function resetNotificationSentTo(User $user): ResetPasswordNotification
    {
        $notification = null;

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $sentNotification) use (&$notification): bool {
                $notification = $sentNotification;

                return true;
            },
        );

        $this->assertInstanceOf(ResetPasswordNotification::class, $notification);

        return $notification;
    }
}
