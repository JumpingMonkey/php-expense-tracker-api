<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    /**
     * Test user registration.
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ]);
            
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }
    
    /**
     * Test user login.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'access_token',
                'token_type',
                'expires_in',
                'user',
            ]);
    }
    
    /**
     * Test invalid login credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized',
            ]);
    }
    
    /**
     * Test user profile retrieval.
     */
    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'profile@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = auth()->login($user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }
    
    /**
     * Test user logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = auth()->login($user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);
    }
    
    /**
     * Test token refresh.
     */
    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create([
            'email' => 'refresh@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = auth()->login($user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'access_token',
                'token_type',
                'expires_in',
                'user',
            ]);
    }
    
    /**
     * Test unauthorized access.
     */
    public function test_unauthorized_access_is_blocked(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
}
