<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $product;
    private $category;
    private $address;
    private $transaction;
    private $payment;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup User
        $this->user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe_' . Str::random(5) . '@example.com',
            'password' => bcrypt('password123'),
            'usertype' => 'user',
            'is_membership' => true,
            'point' => 1000
        ]);

        // 2. Setup Address
        $this->address = Address::create([
            'user_id' => $this->user->id,
            'region' => 'Test Region',
            'first_name_address' => 'John',
            'last_name_address' => 'Doe',
            'address_location' => 'Jl. Test No 123',
            'city' => 'Test City',
            'province' => 'Test Province',
            'postal_code' => '12345',
            'location_type' => 'home'
        ]);

        // 3. Setup Category & Product
        $this->category = Category::create([
            'code' => 'CAT-' . Str::random(4),
            'name' => 'Test Category',
            'slug' => 'test-category-' . Str::random(5)
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'sku' => 'TEST-' . Str::random(4),
            'name' => 'Test Product',
            'slug' => 'test-product-' . Str::random(5),
            'price' => 150000,
            'stock' => 10,
            'status' => 'active'
        ]);

        ProductStock::create([
            'product_id' => $this->product->id,
            'batch_code' => 'BAT-TEST-' . Str::random(4),
            'quantity' => 10,
            'initial_quantity' => 10,
        ]);

        // 4. Setup Transaction & Details
        $this->transaction = Transaction::create([
            'user_id' => $this->user->id,
            'order_id' => 'SOL-TEST-' . Str::random(5),
            'total_amount' => 150000,
            'status' => 'pending',
            'address_id' => $this->address->id,
            'shipping_method' => 'free',
            'shipping_cost' => 0,
        ]);

        TransactionDetail::create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 150000,
        ]);

        // 5. Setup existing Payment for this transaction
        $this->payment = Payment::create([
            'transaction_id' => $this->transaction->id,
            'external_id' => 'PAY-' . $this->transaction->order_id,
            'checkout_url' => 'https://checkout.xendit.co/test',
            'amount' => 150000,
            'status' => 'pending',
        ]);
    }

    private function authenticateUser()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    // =========================================================================
    // TEST POST /api/payments/invoice (createInvoice)
    // =========================================================================
    public function test_create_invoice_returns_existing_url_if_already_pending()
    {
        $payload = [
            'transaction_id' => $this->transaction->id,
            'address_id' => $this->address->id,
            'shipping_method' => 'free',
        ];

        // Karena transaksi ini sudah punya Payment 'pending' dengan checkout_url,
        // controller harus langsung mengembalikan URL lama tersebut (bypass Xendit creation).
        $response = $this->authenticateUser()->postJson('/api/payments/invoice', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'checkout_url' => 'https://checkout.xendit.co/test',
                 ]);
    }

    // public function test_create_invoice_fails_if_transaction_not_found()
    // {
    //     $payload = [
    //         'transaction_id' => 999999, // Invalid ID
    //         'address_id' => $this->address->id,
    //         'shipping_method' => 'free',
    //     ];

    //     $response = $this->authenticateUser()->postJson('/api/payments/invoice', $payload);

    //     $response->assertStatus(404); // ModelNotFoundException
    // }

    public function test_create_invoice_fails_if_transaction_not_found()
    {
        $payload = [
            'transaction_id' => 999999, // Invalid ID
            'address_id' => $this->address->id,
            'shipping_method' => 'free',
        ];

        $response = $this->authenticateUser()->postJson('/api/payments/invoice', $payload);

        // PERBAIKAN: Berubah dari 404 menjadi 422 karena di-intercept oleh Laravel Validation
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['transaction_id']);
    }

    // =========================================================================
    // TEST POST /api/payments/callback (Xendit Webhook Callback)
    // =========================================================================
    public function test_payment_callback_handles_paid_status()
    {
        // Simulasi Payload dari Xendit Callback (Seringkali tanpa autentikasi, jadi kita post as guest)
        $payload = [
            'external_id' => $this->payment->external_id,
            'status' => 'PAID',
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BCA',
        ];

        $response = $this->postJson('/api/payments/callback', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Callback processed');

        // Pastikan DB terupdate
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'PAID'
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'status' => 'completed', // Karena shipping method adalah 'free', status langsung 'completed'
            'payment_method' => 'BANK_TRANSFER BCA'
        ]);
    }

    public function test_payment_callback_handles_expired_status_and_restores_stock()
    {
        // Setup: Kurangi stok dummy seolah-olah sedang di-checkout
        $this->product->update(['stock' => 9]);

        $payload = [
            'external_id' => $this->payment->external_id,
            'status' => 'EXPIRED',
        ];

        $response = $this->postJson('/api/payments/callback', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Callback processed');

        // Status payment dan transaksi harus dibatalkan
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'EXPIRED'
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'status' => 'cancelled',
            'shipping_status' => 'cancelled'
        ]);

        // Cek apakah stok berhasil dikembalikan
        $this->assertEquals(10, $this->product->fresh()->stock);
    }

    public function test_payment_callback_returns_404_if_external_id_not_found()
    {
        $payload = [
            'external_id' => 'PAY-INVALID-EXTERNAL-ID',
            'status' => 'PAID',
        ];

        $response = $this->postJson('/api/payments/callback', $payload);

        $response->assertStatus(404)
                 ->assertJsonPath('message', 'Payment not found');
    }

    // =========================================================================
    // TEST POST /api/shipping/rates (Biteship Integration)
    // =========================================================================
    public function test_get_shipping_rates_validates_address_and_cart()
    {
        // Request tanpa data yang diperlukan
        $response = $this->authenticateUser()->postJson('/api/shipping/rates', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['address_id', 'cart_ids']);
    }

    public function test_get_shipping_rates_returns_success_from_biteship()
    {
        // 1. Buat dummy keranjang
        $cart = Cart::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'gross_amount' => 150000,
        ]);

        $payload = [
            'address_id' => $this->address->id,
            'cart_ids' => [$cart->id]
        ];

        // 2. MOCK API BITESIP MENGGUNAKAN HTTP::FAKE()
        // Ini memastikan tes tidak memanggil server Biteship asli
        Http::fake([
            'api.biteship.com/v1/rates/couriers' => Http::response([
                'success' => true,
                'pricing' => [
                    [
                        'company' => 'jne',
                        'type' => 'reg',
                        'price' => 15000
                    ],
                    [
                        'company' => 'sicepat',
                        'type' => 'best',
                        'price' => 12000
                    ]
                ]
            ], 200)
        ]);

        $response = $this->authenticateUser()->postJson('/api/shipping/rates', $payload);

        // Jika Anda menggunakan GuzzleHttp client biasa di BiteshipService.php (bukan Facade Http::),
        // Http::fake() mungkin tidak bekerja.
        // Asumsi BiteshipService menggunakan Http::post() / Http::get() bawaan Laravel:
        $response->assertStatus(200);
        // Tergantung dari struktur balikan BiteshipService Anda, jika me-return pricing langsung:
        // ->assertJsonFragment(['company' => 'jne']);
    }
}
