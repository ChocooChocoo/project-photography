<?php

namespace Tests\Feature\StudioEmployee;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class EmployeeViewCompileTest extends TestCase
{
    /**
     * An inline @php(...) placed before a block @php ... @endphp makes Blade
     * swallow everything between them, which left directives uncompiled and
     * caused the HTTP 500 on /owner/view/employee.
     */
    public function test_employee_views_compile_every_directive(): void
    {
        foreach (['owner/view-employee', 'studio-hr/view-employee'] as $view) {
            $compiled = Blade::compileString(file_get_contents(resource_path("views/{$view}.blade.php")));

            $this->assertStringNotContainsString('@foreach', $compiled, $view);
            $this->assertStringNotContainsString('@endforeach', $compiled, $view);
            $this->assertStringNotContainsString('{{ $slots', $compiled, $view);

            // Throws ParseError if the compiled PHP is invalid.
            token_get_all($compiled, TOKEN_PARSE);
        }
    }
}
