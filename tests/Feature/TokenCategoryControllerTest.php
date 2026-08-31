<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TokenCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_administrator_can_create_a_token_category(): void
    {
        $superAdministrator = $this->createUser('super-admin');

        $response = $this->actingAs($superAdministrator)->post(route('token-categories.store'), [
            'name' => 'Demand Letter Submission',
            'code' => 'dls',
            'description' => 'Creates a labour demand token.',
            'default_fee' => '25.00',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('token-categories.index'));
        $this->assertDatabaseHas('token_categories', [
            'name' => 'Demand Letter Submission',
            'code' => 'DLS',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'token-categories',
            'action' => 'create',
        ]);
    }

    public function test_duplicate_category_code_is_rejected(): void
    {
        $superAdministrator = $this->createUser('super-admin');
        TokenCategory::create(['name' => 'Demand Letter Submission', 'code' => 'DLS']);

        $response = $this->actingAs($superAdministrator)->post(route('token-categories.store'), [
            'name' => 'Another Category',
            'code' => 'dls',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseCount('token_categories', 1);
    }

    public function test_super_administrator_can_update_and_deactivate_a_token_category(): void
    {
        $superAdministrator = $this->createUser('super-admin');
        $category = TokenCategory::create(['name' => 'Change Pre Worker', 'code' => 'CPA']);

        $this->actingAs($superAdministrator)->put(route('token-categories.update', $category), [
            'name' => 'Change Pre-selected Worker',
            'code' => 'cpa',
            'display_order' => 2,
            'is_active' => true,
        ])->assertRedirect(route('token-categories.index'));

        $this->assertDatabaseHas('token_categories', [
            'id' => $category->id,
            'name' => 'Change Pre-selected Worker',
            'code' => 'CPA',
            'is_active' => true,
        ]);

        $this->actingAs($superAdministrator)
            ->delete(route('token-categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseHas('token_categories', ['id' => $category->id, 'is_active' => false]);
    }

    public function test_administrator_navigation_links_to_token_categories(): void
    {
        $administrator = $this->createUser('administrator');

        $response = $this->actingAs($administrator)->get(route('token-categories.index'));

        $response->assertSee('href="'.route('token-categories.index').'"', false);
        $response->assertSeeText('Token Categories');
    }

    public function test_administrator_can_create_a_token_category(): void
    {
        $administrator = $this->createUser('administrator');

        $response = $this->actingAs($administrator)->post(route('token-categories.store'), [
            'name' => 'Demand Letter Submission',
            'code' => 'DLS',
        ]);

        $response->assertRedirect(route('token-categories.index'));
        $this->assertDatabaseHas('token_categories', [
            'name' => 'Demand Letter Submission',
            'code' => 'DLS',
        ]);
    }

    public function test_administrator_cannot_deactivate_a_token_category(): void
    {
        $administrator = $this->createUser('administrator');
        $category = TokenCategory::create(['name' => 'Demand Letter Submission', 'code' => 'DLS']);

        $response = $this->actingAs($administrator)->delete(route('token-categories.destroy', $category));

        $response->assertForbidden();
        $this->assertTrue($category->fresh()->is_active);
    }

    private function createUser(string $roleName): User
    {
        $role = Role::create(['name' => $roleName, 'label' => ucwords(str_replace('-', ' ', $roleName))]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
