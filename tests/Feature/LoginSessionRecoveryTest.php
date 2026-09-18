<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class LoginSessionRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends ValidateCsrfToken {
                protected function runningUnitTests()
                {
                    return false;
                }
            };
        });
    }

    public function test_stale_login_token_redirects_without_flashing_password(): void
    {
        $this->withSession(['_token' => 'current-token'])
            ->post('/login', ['_token' => 'stale-token', 'email' => 'sales@example.test', 'password' => 'secret-test-password'])
            ->assertRedirect('/login')
            ->assertSessionHas('error')
            ->assertSessionHas('_old_input.email', 'sales@example.test')
            ->assertSessionMissing('_old_input.password');

        $response = $this->get('/login')->assertOk()->assertSee('Sesi tidak cocok atau telah berakhir');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_json_requests_keep_419_status(): void
    {
        $this->withSession(['_token' => 'current-token'])
            ->postJson('/login', ['_token' => 'stale-token'])
            ->assertStatus(419)
            ->assertJsonStructure(['message']);
    }

    public function test_valid_token_reaches_login_validation(): void
    {
        $this->withSession(['_token' => 'current-token'])
            ->post('/login', ['_token' => 'current-token'])
            ->assertSessionHasErrors(['email', 'password']);
    }
}
