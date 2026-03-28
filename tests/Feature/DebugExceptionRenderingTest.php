<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugExceptionRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_http_exceptions_use_laravel_debug_renderer_when_app_debug_is_enabled(): void
    {
        $this->markApplicationAsInstalled();

        config(['app.debug' => true]);

        $this->get('/rota-inexistente-debug')
            ->assertStatus(404)
            ->assertSee('NotFoundHttpException', false);
    }
}
