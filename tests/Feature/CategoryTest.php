<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Expense;

class CategoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and get JWT token for authentication
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
        
        // Seed some categories for testing
        Category::create(['name' => 'Groceries']);
        Category::create(['name' => 'Utilities']);
    }
    
    /**
     * Test category listing.
     */
    public function test_user_can_list_categories(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
        
        // Verify we have at least our seeded categories
        $this->assertCount(2, $response->json('data'));
    }
    
    /**
     * Test category creation.
     */
    public function test_user_can_create_category(): void
    {
        $category = [
            'name' => 'Entertainment',
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/categories', $category);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => [
                    'name' => 'Entertainment',
                ]
            ]);
            
        $this->assertDatabaseHas('categories', [
            'name' => 'Entertainment',
        ]);
    }
    
    /**
     * Test category retrieval.
     */
    public function test_user_can_get_category(): void
    {
        $category = Category::where('name', 'Groceries')->first();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $category->id,
                    'name' => 'Groceries',
                ]
            ]);
    }
    
    /**
     * Test category update.
     */
    public function test_user_can_update_category(): void
    {
        $category = Category::where('name', 'Utilities')->first();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/categories/' . $category->id, [
            'name' => 'Bills & Utilities',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => [
                    'id' => $category->id,
                    'name' => 'Bills & Utilities',
                ]
            ]);
            
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Bills & Utilities',
        ]);
    }
    
    /**
     * Test category deletion.
     */
    public function test_user_can_delete_category(): void
    {
        // Create a new category for deletion to avoid conflicts with expenses
        $category = Category::create(['name' => 'Test Delete']);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Category deleted successfully',
            ]);
            
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }
    
    /**
     * Test category deletion restriction when it has expenses.
     */
    public function test_cannot_delete_category_with_expenses(): void
    {
        $category = Category::where('name', 'Groceries')->first();
        
        // Create an expense associated with this category
        Expense::create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'description' => 'Weekly grocery shopping',
            'amount' => 120.50,
            'expense_date' => now(),
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/categories/' . $category->id);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Cannot delete category as it is being used by expenses',
            ]);
            
        // Verify the category still exists
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }
    
    /**
     * Test unauthorized access to categories.
     */
    public function test_unauthorized_access_to_categories(): void
    {
        // Clear any existing authentication
        auth()->logout();
        
        // Make request with an invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/categories');
        
        $response->assertStatus(401);
    }
    
    /**
     * Test validation when creating a category.
     */
    public function test_category_creation_validation(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/categories', [
            'name' => '', // Invalid: empty name
        ]);

        $response->assertStatus(422) // Validation error status code
            ->assertJsonValidationErrors(['name']);
    }
}
