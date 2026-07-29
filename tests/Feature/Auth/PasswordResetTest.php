<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Email reset link');
    }

    public function test_reset_link_is_emailed_to_a_known_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status');

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use ($user): bool {
                $actionUrl = $notification->toMail($user)->actionUrl;
                parse_str((string) parse_url($actionUrl, PHP_URL_QUERY), $query);

                return str_contains($actionUrl, '/reset-password/'.$notification->token)
                    && ($query['email'] ?? null) === $user->email;
            }
        );
    }

    public function test_unknown_email_receives_the_same_neutral_response(): void
    {
        Notification::fake();

        $this->post(route('password.email'), [
            'email' => 'unknown@example.com',
        ])->assertSessionHas(
            'status',
            'If an account matches that email address, a password reset link has been sent.'
        );

        Notification::assertNothingSent();
    }

    public function test_mobile_api_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'parent',
            'status' => 'active',
        ]);

        $this->postJson(route('api.password.email'), [
            'email' => $user->email,
        ])->assertOk()->assertExactJson([
            'message' => 'If an account matches that email address, a password reset link has been sent.',
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->createToken('parent-phone');

        $token = Password::createToken($user);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'A-new-secure-password9',
            'password_confirmation' => 'A-new-secure-password9',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue(Hash::check('A-new-secure-password9', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_password_is_not_reset_with_an_invalid_token(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);

        $response = $this->from(route('password.reset', [
            'token' => 'invalid-token',
            'email' => $user->email,
        ]))->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'A-new-secure-password9',
            'password_confirmation' => 'A-new-secure-password9',
        ]);

        $response
            ->assertRedirect(route('password.reset', [
                'token' => 'invalid-token',
                'email' => $user->email,
            ]))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
