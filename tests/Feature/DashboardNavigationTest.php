<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_metric_cards_link_to_their_corresponding_lists(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($administrator)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('class="card metric-card metric-card-link"', false);
        $response->assertSee('href="'.route('tokens.index', ['created' => 'today']).'"', false);
        $response->assertSee('href="'.route('tokens.index', ['bhc_status' => 'pending']).'"', false);
        $response->assertSee('href="'.route('tokens.index', ['boesl_status' => 'pending']).'"', false);
        $response->assertSee('href="'.route('applicants.index', ['flight_status' => 'pending']).'"', false);
        $response->assertSee('href="'.route('tokens.index').'"', false);
        $response->assertSee('href="'.route('applicants.index').'"', false);
    }
}
