<?php

namespace Tests\Feature\Procurement;

use App\Http\Requests\StudioPhotographer\StoreProcurementRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Client test C-27: a huge quantity or cost reached MySQL and failed with
 * "Numeric value out of range" (shown on live as "Something went wrong").
 */
class ProcurementAmountLimitsTest extends TestCase
{
    private function check(array $item): \Illuminate\Validation\Validator
    {
        $request = StoreProcurementRequest::create('/', 'POST', [
            'action' => 'save_draft',
            'items' => [array_merge(['item_name' => 'Battery', 'category' => 'consumable', 'quantity' => 2, 'unit_of_measure' => 'pcs', 'estimated_unit_cost' => 3500], $item)],
        ]);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages(), $request->attributes());
        $request->withValidator($validator);
        $validator->passes();

        return $validator;
    }

    public function test_amounts_must_fit_the_database_columns(): void
    {
        $this->assertTrue($this->check([])->errors()->isEmpty());
        $this->assertTrue($this->check(['quantity' => 999999999999])->errors()->has('items.0.quantity'));
        $this->assertTrue($this->check(['estimated_unit_cost' => 999999999999999])->errors()->has('items.0.estimated_unit_cost'));

        // Each amount fits, but quantity x cost does not.
        $overflow = $this->check(['quantity' => 99999999, 'estimated_unit_cost' => 9999999]);
        $this->assertTrue($overflow->errors()->has('items'));
        $this->assertSame('The request total can be at most ₱9,999,999,999.99.', $overflow->errors()->first('items'));
    }
}
