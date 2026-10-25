<?php

namespace Tests\Feature\Client;

use App\Http\Controllers\Client\BookingController;
use App\Http\Requests\StoreBudgetRequest;
use App\Models\ClientBudgetModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BudgetMatchTest extends TestCase
{
    private function match(float $price, ?float $preferred): array
    {
        $budget = $preferred === null ? null : new ClientBudgetModel(['preferred_budget' => $preferred]);
        $method = new \ReflectionMethod(BookingController::class, 'calculateBudgetMatch');

        return $method->invoke(app(BookingController::class), $price, $budget);
    }

    public function test_price_within_ten_percent_of_preferred_is_perfect_match(): void
    {
        foreach ([10000, 9000, 11000] as $price) {
            $result = $this->match($price, 10000);
            $this->assertSame('perfect_match', $result['match_level'], "price {$price}");
            $this->assertTrue($result['is_recommended']);
        }
    }

    public function test_price_more_than_ten_percent_below_preferred_is_within_budget(): void
    {
        $result = $this->match(5000, 10000);

        $this->assertSame('within_budget', $result['match_level']);
        $this->assertFalse($result['is_recommended']);
    }

    public function test_price_more_than_ten_percent_above_preferred_is_above_budget(): void
    {
        $result = $this->match(11001, 10000);

        $this->assertSame('above_budget', $result['match_level']);
        $this->assertSame(0, $result['match_percentage']);
    }

    public function test_no_budget_returns_no_budget_level(): void
    {
        $this->assertSame('no_budget', $this->match(5000, null)['match_level']);
    }

    public function test_store_request_needs_only_preferred_budget(): void
    {
        $rules = (new StoreBudgetRequest())->rules();

        $this->assertArrayNotHasKey('minimum_budget', $rules);
        $this->assertArrayNotHasKey('maximum_budget', $rules);
        $this->assertTrue(Validator::make(['preferred_budget' => 25000, 'status' => 'active'], $rules)->passes());
        $this->assertTrue(Validator::make(['status' => 'active'], $rules)->fails());
    }

    public function test_budget_amount_must_fit_the_column(): void
    {
        // Client test C-15: 99,999,999,999 reached MySQL and failed with "Numeric value out of range".
        foreach ([new StoreBudgetRequest(), new \App\Http\Requests\UpdateBudgetRequest()] as $request) {
            $validator = Validator::make(['preferred_budget' => 99999999999, 'status' => 'active'], $request->rules(), $request->messages());
            $this->assertTrue($validator->fails());
            $this->assertSame('The budget can be at most ₱99,999,999.99.', $validator->errors()->first('preferred_budget'));
            $this->assertTrue(Validator::make(['preferred_budget' => 99999999.99, 'status' => 'active'], $request->rules())->passes());
        }
    }

    public function test_overspend_alert_fires_once_spent_reaches_preferred_budget(): void
    {
        Schema::dropAllTables();
        Schema::create('tbl_client_budget', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('budget_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('preferred_budget', 10, 2);
            $table->decimal('spent_amount', 10, 2)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('budget_type')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });

        ClientBudgetModel::create([
            'client_id' => 1, 'category_id' => 7, 'preferred_budget' => 10000,
            'spent_amount' => 0, 'status' => 'active',
        ]);

        $controller = $this->partialMock(BookingController::class, function ($mock) {
            $mock->shouldReceive('notifyBudgetExceeded')->once();
        });
        $spend = new \ReflectionMethod(BookingController::class, 'updateClientBudgetSpending');
        $booking = (object) ['client_id' => 1, 'category_id' => 7, 'client' => (object) ['id' => 1]];

        $spend->invoke($controller, $booking, (object) ['amount' => 6000]); // below preferred: no alert
        $spend->invoke($controller, $booking, (object) ['amount' => 4000]); // reaches preferred: alert
    }
}
