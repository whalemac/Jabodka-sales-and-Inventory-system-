<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_path_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_to_login_from_admin_pages(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('login'));
        $this->get(route('admin.stock.index'))->assertRedirect(route('login'));
        $this->get(route('admin.online.index'))->assertRedirect(route('login'));
        $this->get(route('admin.consignment.index'))->assertRedirect(route('login'));
    }

    public function test_non_admins_cannot_reach_owner_pages(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_the_owner_dashboard_loads(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk();
    }
}
