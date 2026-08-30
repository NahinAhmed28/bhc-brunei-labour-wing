<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_lists_render_bootstrap_controls_and_a_result_summary(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);
        User::factory()->count(20)->create(['role_id' => $role->id]);

        $response = $this->actingAs($administrator)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('class="pagination"', false);
        $response->assertSee('page-item', false);
        $response->assertSeeText('Showing 1 to 20 of 21 results');
        $response->assertSee('href="'.route('users.index', ['page' => 2]).'"', false);
    }
}
