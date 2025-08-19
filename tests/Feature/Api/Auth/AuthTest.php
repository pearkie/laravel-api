<?php

namespace Tests\Feature\Api\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user']);
    }

    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_register_and_receive_token():void
    {
        $payload = [
            'name' => 'New User',
            'email' => $email ='newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertCreated();
        $response->assertJsonStructure(['token', 'user']);
        $this->assertDatabaseHas('users', [
            'email' => $email
        ]);
    }

    public function test_user_cannot_register_with_invalid_data():void
    {
        $payload = [
            'name' => '',
            'email' => 'wrong-email',
            'password' => 'short',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_logout_and_token_is_revoked():void
    {
        $user = User::factory()->create();

        $token = $user->createToken('laravel_api_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/auth/logout');

        $response->assertNoContent();

        $this->app['auth']->forgetGuards();

        $protected = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/user');

        $protected->assertStatus(401);
    }

    public function test_guest_cannot_access_user_endpoint():void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_user_endpoint():void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/user');
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $user->id,
            'email' => $user->email
        ]);
    }
}
