<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Expense;
use Carbon\Carbon;

class ExpenseTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $user;
    protected $token;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and get JWT token for authentication
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
        
        // Create a category for testing expenses
        $this->category = Category::create(['name' => 'Groceries']);
        
        // Create some test expenses
        Expense::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'description' => 'Weekly groceries',
            'amount' => 120.50,
            'expense_date' => now()->subDays(2),
        ]);
        
        Expense::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'description' => 'Emergency supplies',
            'amount' => 85.75,
            'expense_date' => now()->subDays(5),
        ]);
    }
    
    /**
     * Test expense listing.
     */
    public function test_user_can_list_expenses(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/expenses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'category_id',
                            'description',
                            'amount',
                            'expense_date',
                            'created_at',
                            'updated_at',
                            'category',
                        ]
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                ]
            ]);
        
        // Verify we have our seeded expenses
        $this->assertCount(2, $response->json('data.data'));
    }
    
    /**
     * Test expense filtering by date range.
     */
    public function test_user_can_filter_expenses_by_date_range(): void
    {
        // Create an older expense outside our filter range
        Expense::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'description' => 'Old expense',
            'amount' => 50.25,
            'expense_date' => now()->subMonths(2),
        ]);
        
        // Test past week filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/expenses?filter=past_week');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data')); // Only our 2 recent expenses
        
        // Test past month filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/expenses?filter=past_month');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data')); // Only our 2 recent expenses
        
        // Test custom date range filter
        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/expenses?filter=custom&start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data')); // Only our 2 recent expenses
    }
    
    /**
     * Test expense creation.
     */
    public function test_user_can_create_expense(): void
    {
        $expenseData = [
            'category_id' => $this->category->id,
            'description' => 'New test expense',
            'amount' => 75.00,
            'expense_date' => now()->format('Y-m-d'),
            'notes' => 'Some test notes',
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/expenses', $expenseData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Expense created successfully',
                'data' => [
                    'description' => 'New test expense',
                    'amount' => '75.00',
                    'notes' => 'Some test notes',
                ]
            ]);
            
        $this->assertDatabaseHas('expenses', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'description' => 'New test expense',
            'amount' => 75.00,
        ]);
    }
    
    /**
     * Test expense retrieval.
     */
    public function test_user_can_get_expense(): void
    {
        $expense = Expense::where('description', 'Weekly groceries')->first();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/expenses/' . $expense->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $expense->id,
                    'description' => 'Weekly groceries',
                    'amount' => '120.50',
                ]
            ]);
    }
    
    /**
     * Test expense update.
     */
    public function test_user_can_update_expense(): void
    {
        $expense = Expense::where('description', 'Emergency supplies')->first();
        
        $updateData = [
            'description' => 'Updated emergency supplies',
            'amount' => 95.25,
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/expenses/' . $expense->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Expense updated successfully',
                'data' => [
                    'id' => $expense->id,
                    'description' => 'Updated emergency supplies',
                    'amount' => '95.25',
                ]
            ]);
            
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Updated emergency supplies',
            'amount' => 95.25,
        ]);
    }
    
    /**
     * Test expense deletion.
     */
    public function test_user_can_delete_expense(): void
    {
        $expense = Expense::where('description', 'Weekly groceries')->first();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/expenses/' . $expense->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Expense deleted successfully',
            ]);
            
        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id,
        ]);
    }
    
    /**
     * Test expense summary endpoint.
     */
    public function test_user_can_get_expense_summary(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/expenses/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_amount',
                    'by_category',
                ]
            ]);
    }
    
    /**
     * Test that a user can only see their own expenses.
     */
    public function test_user_can_only_see_own_expenses(): void
    {
        // Create another user with their own expense
        $anotherUser = User::factory()->create();
        $anotherExpense = Expense::create([
            'user_id' => $anotherUser->id,
            'category_id' => $this->category->id,
            'description' => 'Another user expense',
            'amount' => 150.00,
            'expense_date' => now(),
        ]);
        
        // Original user should not see the other user's expense
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/expenses');

        $response->assertStatus(200);
        
        $expenseIds = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertNotContains($anotherExpense->id, $expenseIds);
        
        // Also check that user can't access another user's expense directly
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/expenses/' . $anotherExpense->id);

        $response->assertStatus(404);
    }
    
    /**
     * Test expense creation validation.
     */
    public function test_expense_creation_validation(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/expenses', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'description', 'amount', 'expense_date']);
    }
}
