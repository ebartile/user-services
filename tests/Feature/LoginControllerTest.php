<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase; // Use this trait to reset the database after each test

    /**
     * Test successful login.
     *
     * @return void
     */
    public function testSuccessfulLogin()
    {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Simulate a login request
        $response = $this->postJson(route('auth.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Assert the response
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'name',
            'broadcast',
            'settings',
            'auth',
            'csrfToken',
            'auth_token',
        ]);

        // Assert that the LoggedIn event is fired
        Event::assertDispatched(\App\Events\UserActivities\LoggedIn::class);
    }

    /**
     * Test unsuccessful login.
     *
     * @return void
     */
    public function testUnsuccessfulLogin()
    {
        // Simulate an unsuccessful login request
        $response = $this->postJson(route('auth.login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'invalid_password',
        ]);

        // Assert the response
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'email',
            ],
        ]);

        // Assert that the LoggedIn event is not fired
        Event::assertNotDispatched(\App\Events\UserActivities\LoggedIn::class);
    }
}
