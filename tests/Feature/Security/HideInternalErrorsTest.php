<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Panel re-test S06 showed the SQL, database name and host in a JSON 500,
 * because many catch blocks return $e->getMessage(). With debug off, those
 * details must stay in the log and out of the browser.
 */
class HideInternalErrorsTest extends TestCase
{
    private const SQL_ERROR = 'SQLSTATE[23000]: Integrity constraint violation (Connection: mysql, Host: localhost, Database: u565877322_Project_00)';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        Route::middleware('web')->group(function () {
            Route::get('/_test/error-500', fn () => response()->json([
                'success' => false,
                'message' => 'Failed to create package. Please try again.',
                'error' => self::SQL_ERROR,
            ], 500));
            Route::get('/_test/error-joined', fn () => response()->json([
                'success' => false,
                'message' => 'Failed to create booking: '.self::SQL_ERROR,
            ], 500));
            Route::get('/_test/error-409-sql', fn () => response()->json([
                'success' => false,
                'message' => self::SQL_ERROR,
            ], 409));
            Route::get('/_test/error-422', fn () => response()->json([
                'success' => false,
                'message' => 'This booking was cancelled, so its equipment cannot be confirmed.',
            ], 422));
            Route::get('/_test/ok', fn () => response()->json(['success' => true, 'message' => 'Saved.']));
        });
    }

    public function test_a_server_error_keeps_a_plain_message_and_hides_the_details(): void
    {
        Log::spy();

        $response = $this->getJson('/_test/error-500')->assertStatus(500);

        $response->assertExactJson(['success' => false, 'message' => 'Failed to create package. Please try again.']);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_a_message_with_the_details_joined_in_becomes_a_generic_message(): void
    {
        $this->getJson('/_test/error-joined')
            ->assertStatus(500)
            ->assertExactJson(['success' => false, 'message' => 'Something went wrong. Please try again.']);
    }

    public function test_database_details_are_hidden_in_any_error_status(): void
    {
        $this->getJson('/_test/error-409-sql')
            ->assertStatus(409)
            ->assertExactJson(['success' => false, 'message' => 'Something went wrong. Please try again.']);
    }

    public function test_plain_user_messages_and_success_responses_are_unchanged(): void
    {
        $this->getJson('/_test/error-422')
            ->assertStatus(422)
            ->assertJsonPath('message', 'This booking was cancelled, so its equipment cannot be confirmed.');

        $this->getJson('/_test/ok')->assertOk()->assertExactJson(['success' => true, 'message' => 'Saved.']);
    }

    public function test_details_stay_visible_when_debug_is_on(): void
    {
        config(['app.debug' => true]);

        $this->getJson('/_test/error-500')->assertJsonPath('error', self::SQL_ERROR);
    }
}
