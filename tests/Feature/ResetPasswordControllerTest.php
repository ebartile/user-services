<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sendCode()
    {
        Notification::fake(); // Disable notifications for testing

        // Create a user
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Send a code to the user
        $response = $this->postJson(route('auth.reset-password.send-code'), ['email' => $user->email]);
        $response->assertSuccessful();

        // Assert that the notification was sent to the user
        Notification::assertSentTo(
            $user,
            \App\Notifications\Auth\EmailToken::class
        );
    }

    public function test_reset()
    {
        // Create a user
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Generate a password reset token
        $token = $this->app['auth.password.broker']->createToken($user);

        // Reset the password
        $response = $this->postJson(route('auth.reset-password.reset'), [
            'email'                 => $user->email,
            'password'              => 'new-password123',
            'password_confirmation' => 'new-password123',
            'token'                 => $token,
        ]);

        $response->assertSuccessful();

        // Assert that the user's password was reset
        $this->assertTrue(\Hash::check('new-password123', $user->fresh()->password));
    }
}
