<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    // Gunakan RefreshDatabase agar database kembali bersih setiap kali test dijalankan
    use DatabaseTransactions, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Memalsukan sistem storage S3 agar test tidak benar-benar mengunggah file ke AWS
        Storage::fake('s3');
    }

    /**
     * Helper function untuk membuat Admin/User dengan Token
     */
    private function authenticateAdmin()
    {
        $admin = User::factory()->create([
            'usertype' => 'admin',
        ]);

        // Berpura-pura login menggunakan Sanctum
        return $this->actingAs($admin, 'sanctum');
    }

    // =========================================================================
    // TEST GET /api/products (Hanya Active)
    // =========================================================================
    public function test_can_get_all_active_products()
    {
        $category = Category::factory()->create();

        // Buat 1 produk aktif, 1 produk nonaktif
        Product::factory()->create(['category_id' => $category->id, 'status' => 'active', 'name' => 'Active Product']);
        Product::factory()->create(['category_id' => $category->id, 'status' => 'inactive', 'name' => 'Inactive Product']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonCount(1) // Harus hanya 1 yang kembali
                 ->assertJsonFragment(['name' => 'Active Product'])
                 ->assertJsonMissing(['name' => 'Inactive Product']);
    }

    // =========================================================================
    // TEST GET /api/products/inactive
    // =========================================================================
    public function test_can_get_all_inactive_products()
    {
        // Asumsi rute /api/products/inactive dibatasi untuk admin, jadi kita authenticate
        $category = Category::factory()->create();

        Product::factory()->create(['category_id' => $category->id, 'status' => 'active', 'name' => 'Active Product']);
        Product::factory()->create(['category_id' => $category->id, 'status' => 'inactive', 'name' => 'Inactive Product']);

        // Ganti URL sesuai dengan rute Anda (mungkin perlu $this->authenticateAdmin()->getJson(...))
        $response = $this->getJson('/api/products/inactive');

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonFragment(['name' => 'Inactive Product'])
                 ->assertJsonMissing(['name' => 'Active Product']);
    }

    // =========================================================================
    // TEST GET /api/products/{id}
    // =========================================================================
    public function test_can_get_single_product_detail()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.id', $product->id)
                 ->assertJsonPath('data.name', $product->name);
    }

    public function test_returns_404_if_product_not_found()
    {
        $response = $this->getJson("/api/products/99999");

        $response->assertStatus(404)
                 ->assertJsonPath('status', 'error')
                 ->assertJsonPath('message', 'Produk tidak ditemukan.');
    }

    // =========================================================================
    // TEST POST /api/products (STORE)
    // =========================================================================
    public function test_admin_can_store_new_product()
    {
        $category = Category::factory()->create();

        $payload = [
            'category_id' => $category->id,
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Product Name',
            'description' => 'This is a test description',
            'price' => 150000,
            'discount_price' => 120000,
            'stock' => 50,
            'status' => 'active',
            'color' => [
                ['hex' => '#000000', 'name' => 'Black']
            ]
        ];

        $response = $this->authenticateAdmin()->postJson('/api/products', $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'sku' => 'TEST-SKU-001',
                     'name' => 'Test Product Name',
                     'price' => 150000,
                     'discount_price' => 120000,
                 ]);

        // Pastikan tersimpan di DB
        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-SKU-001',
        ]);

        // Karena stok dikirim > 0, pastikan tabel product_stocks juga terisi
        $this->assertDatabaseHas('product_stocks', [
            'quantity' => 50,
            'initial_quantity' => 50
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->authenticateAdmin()->postJson('/api/products', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['category_id', 'sku', 'name', 'price', 'stock', 'status']);
    }

    // =========================================================================
    // TEST PUT /api/products/{id} (UPDATE)
    // =========================================================================
    public function test_admin_can_update_product()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Old Name',
            'price' => 100000,
        ]);

        $payload = [
            'category_id' => $category->id,
            'sku' => $product->sku, // Gunakan SKU lama untuk bypass validasi unique
            'name' => 'New Awesome Name',
            'price' => 120000,
            'discount_price' => 99000,
            'status' => 'active'
        ];

        $response = $this->authenticateAdmin()->putJson("/api/products/{$product->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'name' => 'New Awesome Name',
                     'price' => 120000,
                     'discount_price' => 99000,
                 ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Awesome Name',
        ]);
    }

    // =========================================================================
    // TEST DELETE /api/products/{id} (SOFT DELETE / INACTIVE)
    // =========================================================================
    public function test_admin_can_deactivate_product()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        $response = $this->authenticateAdmin()->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Product deactivated');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'inactive'
        ]);
    }

    // =========================================================================
    // TEST RESTORE /api/products/{id}/restore
    // =========================================================================
    public function test_admin_can_restore_product()
    {
        // Asumsi route-nya adalah /api/products/{id}/restore
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 'inactive'
        ]);

        // Sesuaikan dengan URL route restore Anda di api.php
        $response = $this->authenticateAdmin()->postJson("/api/products/{$product->id}/restore");

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Product activated');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'active'
        ]);
    }
}
