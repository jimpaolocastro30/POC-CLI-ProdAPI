<?php

use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function createAdmin(): User
{
    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.local',
        'password' => Hash::make('password'),
        'status' => UserStatus::Active,
    ]);
    $user->assignRole('Super Administrator');

    return $user;
}

test('health endpoint returns operational status', function () {
    $this->get('/up')->assertOk();
});

test('user can login with valid credentials', function () {
    $user = createAdmin();

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['access_token', 'user']]);
});

test('login fails with invalid credentials', function () {
    createAdmin();

    $this->postJson('/api/auth/login', [
        'email' => 'admin@test.local',
        'password' => 'wrong-password',
    ])->assertStatus(401);
});

test('stock in increases product quantity', function () {
    $user = User::create([
        'name' => 'Warehouse',
        'email' => 'warehouse@test.local',
        'password' => Hash::make('password'),
        'status' => UserStatus::Active,
    ]);
    $user->assignRole('Warehouse Staff');

    $category = Category::create(['name' => 'Electronics']);
    $supplier = Supplier::create(['name' => 'Supplier A']);
    $product = Product::create([
        'sku' => 'SKU001',
        'name' => 'Laptop',
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'minimum_stock' => 10,
        'current_stock' => 50,
        'unit_cost' => 1000,
        'selling_price' => 1500,
    ]);

    $token = auth('api')->login($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/inventory/stock-in', [
            'product_id' => $product->id,
            'quantity' => 25,
            'remarks' => 'Purchased stocks',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.balance_after', 75);

    expect($product->fresh()->current_stock)->toBe(75);
});

test('dashboard returns kpi metrics', function () {
    $user = createAdmin();
    $token = auth('api')->login($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'total_products',
                'low_stock_products',
                'out_of_stock_products',
                'stock_value',
                'inventory_movements_today',
            ],
        ]);
});
