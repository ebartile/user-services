<?php

namespace Tests\Feature\Auth;

use App\Events\UserActivities\Registered;
use App\Http\Controllers\Auth\RegisterController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user registration.
     *
     * @return void
     */
    public function testUserRegistration()
    {
        Event::fake();

        $response = $this->postJson(route('auth.register'), [
            'email'    => 'ebartile@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'name',
            'broadcast',
            'settings',
            'auth' => [
                'credential',
                'user' => [
                    'id',
                    'name',
                    'presence',
                    'last_seen_at',
                    // Add other user attributes as needed
                ],
            ],
            'csrfToken',
            'auth_token',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ebartile@gmail.com',
        ]);

        Event::assertDispatched(Registered::class);
    }

    /**
     * Test registration validation error.
     *
     * @return void
     */
    public function testRegistrationValidationError()
    {
        $response = $this->postJson(route('auth.register'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}
