<?php

declare(strict_types=1);

use App\Models\Batch;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Receiving;
use App\Models\User;
use App\Models\WarehouseLocation;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'view_any_batch',
        'view_any_product',
        'view_any_manufacturer',
        'view_any_receiving',
    ]);
});

test('can get dashboard stats', function (): void {
    Product::factory()->count(10)->create();
    Manufacturer::factory()->count(5)->create();
    Batch::factory()->count(20)->create();
    Receiving::factory()->count(15)->create();

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/stats');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'products' => [
                    'total',
                    'active',
                    'local',
                    'imported',
                ],
                'manufacturers' => [
                    'total',
                    'active',
                ],
                'batches' => [
                    'total',
                    'active',
                    'expired',
                    'expiring_soon',
                    'blocked',
                    'recalled',
                ],
                'receivings' => [
                    'total',
                    'pending',
                    'accepted',
                    'rejected',
                    'quarantined',
                ],
                'inventory' => [
                    'total_quantity',
                    'total_value',
                ],
            ],
        ]);

    $data = $response->json('data');
    expect($data['products']['total'])->toBeGreaterThanOrEqual(10);
    expect($data['manufacturers']['total'])->toBeGreaterThanOrEqual(5);
    expect($data['batches']['total'])->toBeGreaterThanOrEqual(20);
    expect($data['receivings']['total'])->toBeGreaterThanOrEqual(15);
});

test('dashboard stats requires authentication', function (): void {
    $response = $this->getJson('/api/dashboard/stats');

    $response->assertUnauthorized();
});

test('dashboard stats requires proper permissions', function (): void {
    $unauthorizedUser = User::factory()->create();

    $response = $this->actingAs($unauthorizedUser, 'api')
        ->getJson('/api/dashboard/stats');

    $response->assertForbidden();
});

test('can get expiring batches with default parameters', function (): void {
    Batch::factory()->count(5)->create([
        'expiry_date' => now()->addDays(15),
        'status' => 'in_storage',
        'remaining_quantity' => 100,
    ]);

    Batch::factory()->count(3)->create([
        'expiry_date' => now()->addDays(100),
        'status' => 'in_storage',
        'remaining_quantity' => 100,
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/expiring-batches');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'batch_number',
                    'expiry_date',
                    'remaining_quantity',
                    'status',
                ],
            ],
            'meta',
            'links',
        ]);

    $data = $response->json('data');
    expect($data)->toBeArray();
    expect(count($data))->toBe(5);
});

test('can get expiring batches with custom days parameter', function (): void {
    Batch::factory()->create([
        'expiry_date' => now()->addDays(5),
        'status' => 'in_storage',
        'remaining_quantity' => 100,
    ]);

    Batch::factory()->create([
        'expiry_date' => now()->addDays(10),
        'status' => 'in_storage',
        'remaining_quantity' => 100,
    ]);

    Batch::factory()->create([
        'expiry_date' => now()->addDays(15),
        'status' => 'in_storage',
        'remaining_quantity' => 100,
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/expiring-batches?days=7');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toBeArray();
    expect(count($data))->toBe(1);
});

test('expiring batches only includes active batches', function (): void {
    Batch::factory()->create([
        'expiry_date' => now()->addDays(15),
        'status' => 'in_storage',
        'remaining_quantity' => 100,
    ]);

    Batch::factory()->create([
        'expiry_date' => now()->addDays(15),
        'status' => 'disposed',
        'remaining_quantity' => 0,
    ]);

    Batch::factory()->create([
        'expiry_date' => now()->addDays(15),
        'status' => 'expired',
        'remaining_quantity' => 0,
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/expiring-batches');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toBeArray();
    expect(count($data))->toBe(1);
});

test('expiring batches respects pagination', function (): void {
    Batch::factory()->count(30)->create([
        'expiry_date' => now()->addDays(15),
        'status' => 'in_storage',
        'remaining_quantity' => 100,
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/expiring-batches?per_page=10');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toBeArray();
    expect(count($data))->toBe(10);
    expect($response->json('meta.per_page'))->toBe(10);
});

test('can get recent additions with default parameters', function (): void {
    Manufacturer::factory()->count(3)->create([
        'created_at' => now()->subDays(2),
    ]);

    Product::factory()->count(5)->create([
        'created_at' => now()->subDays(3),
    ]);

    Manufacturer::factory()->count(2)->create([
        'created_at' => now()->subDays(10),
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/recent-additions');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'manufacturers' => [
                    'count',
                    'items' => [
                        '*' => [
                            'id',
                            'name',
                            'short_name',
                            'country',
                            'created_at',
                        ],
                    ],
                ],
                'products' => [
                    'count',
                    'items' => [
                        '*' => [
                            'id',
                            'name',
                            'sku',
                            'category',
                            'manufacturer',
                            'created_at',
                        ],
                    ],
                ],
            ],
        ]);

    expect($response->json('data.manufacturers.count'))->toBeGreaterThanOrEqual(3);
    expect($response->json('data.products.count'))->toBeGreaterThanOrEqual(5);
});

test('can get recent additions with custom days parameter', function (): void {
    Manufacturer::factory()->create([
        'created_at' => now()->subDays(2),
    ]);

    Manufacturer::factory()->create([
        'created_at' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/recent-additions?days=3');

    $response->assertOk();
    expect($response->json('data.manufacturers.count'))->toBe(1);
});

test('recent additions respects limit parameter', function (): void {
    Manufacturer::factory()->count(15)->create([
        'created_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/recent-additions?limit=5');

    $response->assertOk();
    expect($response->json('data.manufacturers.items'))->toHaveCount(5);
});

test('can get overview visualization data', function (): void {
    Product::factory()->count(5)->create();
    Batch::factory()->count(10)->create(['status' => 'in_storage']);
    Receiving::factory()->count(3)->create(['status' => 'pending']);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/visualization?type=overview');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'type',
                'data' => [
                    'products',
                    'active_batches',
                    'expiring_soon',
                    'pending_receivings',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('overview');
});

test('can get expiry timeline visualization data', function (): void {
    Batch::factory()->create(['expiry_date' => now()->subDays(5)]);
    Batch::factory()->create(['expiry_date' => now()->addDays(5)]);
    Batch::factory()->create(['expiry_date' => now()->addDays(20)]);
    Batch::factory()->create(['expiry_date' => now()->addDays(45)]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/visualization?type=expiry_timeline');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'type',
                'data' => [
                    'expired',
                    '0-7_days',
                    '8-14_days',
                    '15-30_days',
                    '31-60_days',
                    '60+_days',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('expiry_timeline');
});

test('can get product categories visualization data', function (): void {
    Product::factory()->count(3)->create(['category' => 'Dairy']);
    Product::factory()->count(5)->create(['category' => 'Meat']);
    Product::factory()->count(2)->create(['category' => 'Vegetables']);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/visualization?type=product_categories');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'type',
                'data' => [
                    '*' => [
                        'category',
                        'count',
                    ],
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('product_categories');
    expect($response->json('data.data'))->toHaveCount(3);
});

test('can get receiving status visualization data', function (): void {
    Receiving::factory()->count(5)->create(['status' => 'pending']);
    Receiving::factory()->count(10)->create(['status' => 'accepted']);
    Receiving::factory()->count(2)->create(['status' => 'rejected']);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/visualization?type=receiving_status');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'type',
                'data' => [
                    '*' => [
                        'status',
                        'count',
                    ],
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('receiving_status');
});

test('can get batch status visualization data', function (): void {
    Batch::factory()->count(10)->create(['status' => 'in_storage']);
    Batch::factory()->count(5)->create(['status' => 'pending']);
    Batch::factory()->count(2)->create(['status' => 'expired']);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/visualization?type=batch_status');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'type',
                'data' => [
                    '*' => [
                        'status',
                        'count',
                    ],
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('batch_status');
});

test('can get inventory by location visualization data', function (): void {
    $location1 = WarehouseLocation::factory()->create();
    $location2 = WarehouseLocation::factory()->create();

    Batch::factory()->count(3)->create([
        'warehouse_location_id' => $location1->id,
        'remaining_quantity' => 100,
    ]);

    Batch::factory()->count(2)->create([
        'warehouse_location_id' => $location2->id,
        'remaining_quantity' => 50,
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/visualization?type=inventory_by_location');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'type',
                'data' => [
                    '*' => [
                        'location_id',
                        'location_name',
                        'location_code',
                        'total_quantity',
                    ],
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('inventory_by_location');
    expect($response->json('data.data'))->toHaveCount(2);
});

test('visualization defaults to overview when type not provided', function (): void {
    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/visualization');

    $response->assertOk();
    expect($response->json('data.type'))->toBe('overview');
});

test('visualization returns error for invalid type', function (): void {
    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/dashboard/visualization?type=invalid_type');

    $response->assertOk()
        ->assertJsonFragment([
            'error' => 'Invalid visualization type',
        ]);
});
