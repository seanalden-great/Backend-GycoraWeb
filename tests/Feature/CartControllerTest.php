<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $product;
    private $category;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup User yang akan login (usertype 'user')
        $this->user = User::factory()->create([
            'usertype' => 'user'
        ]);

        // 2. Setup Category dan Product (Syarat Cart bisa ada adalah ada Produk)
        $this->category = Category::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 50000,
            'stock' => 10,
            'status' => 'active'
        ]);
    }

    /**
     * Helper function to login as regular user
     */
    private function authenticateUser()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    // =========================================================================
    // TEST GET /api/carts
    // =========================================================================
    public function test_user_can_get_their_cart_items()
    {
        // 1. Buat item keranjang untuk user ini
        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'gross_amount' => 100000,
        ]);

        // 2. Buat user lain dan item keranjangnya (untuk memastikan user hanya melihat miliknya)
        $otherUser = User::factory()->create();
        Cart::create([
            'user_id' => $otherUser->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'gross_amount' => 250000,
        ]);

        // 3. Hit API
        $response = $this->authenticateUser()->getJson('/api/carts');

        // 4. Assertions
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data') // Tergantung struktur response (apakah di dalam 'data' atau root array)
                 ->assertJsonFragment([
                     'product_id' => $this->product->id,
                     'quantity' => 2,
                 ])
                 ->assertJsonMissing([
                     'user_id' => $otherUser->id,
                     'quantity' => 5
                 ]);
    }

    public function test_guest_cannot_get_cart_items()
    {
        // Hit API tanpa authentication
        $response = $this->getJson('/api/carts');

        $response->assertStatus(401); // Unauthorized
    }

    // =========================================================================
    // TEST POST /api/carts
    // =========================================================================
    public function test_user_can_add_product_to_cart()
    {
        $payload = [
            'product_id' => $this->product->id,
            'quantity' => 3,
            'color' => '{"hex":"#000","name":"Black"}'
        ];

        $response = $this->authenticateUser()->postJson('/api/carts', $payload);

        $response->assertStatus(200) // Atau 201 tergantung bagaimana Controller Anda diatur
                 ->assertJsonStructure(['cart_id', 'message']);
                 // Asumsi CartController return JSON ['cart_id' => $cart->id, 'message' => 'Success']

        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'gross_amount' => 150000, // 3 * 50000 (Harga produk)
        ]);
    }

    public function test_adding_same_product_increases_quantity_instead_of_creating_new_row()
    {
        // Kondisi awal: Keranjang sudah punya 1 item
        Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'color' => 'red',
            'gross_amount' => 100000,
        ]);

        // Request tambah produk yang sama dengan warna yang sama
        $payload = [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'color' => 'red'
        ];

        $response = $this->authenticateUser()->postJson('/api/carts', $payload);

        $response->assertStatus(200);

        // Pastikan hanya ada 1 baris di database (row tidak bertambah), tapi qty naik jadi 3
        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'gross_amount' => 150000,
        ]);
    }

    public function test_cannot_add_product_exceeding_stock()
    {
        // Produk kita set up dengan stock 10
        $payload = [
            'product_id' => $this->product->id,
            'quantity' => 15,
        ];

        $response = $this->authenticateUser()->postJson('/api/carts', $payload);

        // Tergantung logika di controller Anda, bisa mereturn 400 Bad Request atau 422 Unprocessable Entity
        $response->assertStatus(400); // Cek status code yang Anda atur jika gagal stok
    }

    // =========================================================================
    // TEST PUT /api/carts/{id}
    // =========================================================================
    public function test_user_can_update_cart_quantity()
    {
        $cart = Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'gross_amount' => 50000,
        ]);

        $payload = [
            'quantity' => 5
        ];

        $response = $this->authenticateUser()->putJson("/api/carts/{$cart->id}", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'quantity' => 5,
            'gross_amount' => 250000, // 5 * 50000
        ]);
    }

    public function test_user_cannot_update_cart_of_another_user()
    {
        $otherUser = User::factory()->create();
        $cartOtherUser = Cart::create([
            'user_id' => $otherUser->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'gross_amount' => 50000,
        ]);

        $payload = ['quantity' => 5];

        // Login sebagai $this->user (bukan pemilik cart)
        $response = $this->authenticateUser()->putJson("/api/carts/{$cartOtherUser->id}", $payload);

        // Tergantung Controller: apakah pakai findOrFail (404) atau return 403 Forbidden
        $response->assertStatus(404); // Kita asumsi query dibatasi dengan Auth::user()->carts()
    }

    // =========================================================================
    // TEST DELETE /api/carts/{id}
    // =========================================================================
    public function test_user_can_remove_item_from_cart()
    {
        $cart = Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'gross_amount' => 50000,
        ]);

        $response = $this->authenticateUser()->deleteJson("/api/carts/{$cart->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('carts', [
            'id' => $cart->id
        ]);
    }
}
