<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataContactReuseTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_and_email_can_be_reused_across_companies_and_agencies(): void
    {
        $role = Role::create(['name' => 'administrator', 'label' => 'Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);
        $contact = ['phone' => '+673 800 1234', 'email' => 'shared@example.com', 'is_active' => true];

        foreach (['First Company', 'Second Company'] as $name) {
            $this->actingAs($administrator)
                ->post(route('companies.store'), $contact + ['name' => $name])
                ->assertRedirect(route('companies.index'));
        }

        foreach (['First Agency', 'Second Agency'] as $name) {
            $this->actingAs($administrator)
                ->post(route('agencies.store'), $contact + ['name' => $name])
                ->assertRedirect(route('agencies.index'));
        }

        $this->assertSame(2, Company::where($contact)->count());
        $this->assertSame(2, Agency::where($contact)->count());
    }
}
