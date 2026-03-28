<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLoginResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_still_opens_when_database_session_and_cache_tables_are_missing(): void
    {
        $this->markApplicationAsInstalled();

        Schema::dropIfExists('sessions');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');

        config([
            'session.driver' => 'database',
            'cache.default' => 'database',
        ]);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Odonto Flow');
    }
}
