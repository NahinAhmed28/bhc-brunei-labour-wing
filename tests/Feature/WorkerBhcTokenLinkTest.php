<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Role;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerBhcTokenLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_gets_new_tab_links_for_both_displayed_bhc_numbers(): void
    {
        $user = $this->createViewer();
        $token = $this->createToken($user);
        $worker = $this->createWorker($token);

        $response = $this->actingAs($user)->get(route('workers.show', $worker));

        $response->assertOk();
        $response->assertSee('href="'.route('tokens.show', $token).'"', false);
        $response->assertSee('data-bhc-token-urls="'.e(json_encode([route('tokens.show', $token)])).'"', false);
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'data-bhc-token-urls='));
        $response->assertSeeText('Open token in a new tab');
        $response->assertDontSee('data-bhc-token-fallback', false);
    }

    public function test_bhc_links_include_all_matching_tokens_and_exclude_other_years_and_deleted_tokens(): void
    {
        $user = $this->createViewer();
        $token = $this->createToken($user, ['bhc_number' => 'BHC-032/26; 02/02/2026']);
        $worker = $this->createWorker($token);
        $newerToken = $this->createToken($user, ['bhc_number' => '32/2026', 'received_on' => '2026-09-01']);
        $this->createToken($user, ['bhc_number' => '32/2025']);
        $this->createToken($user, ['bhc_number' => '032']);
        $this->createToken($user, ['bhc_number' => 'BHC-032/26'])->delete();

        $response = $this->actingAs($user)->get(route('workers.show', $worker));

        $response->assertOk();
        $response->assertSee('data-bhc-token-urls="'.e(json_encode([
            route('tokens.show', $newerToken),
            route('tokens.show', $token),
        ])).'"', false);
        $response->assertSeeText('Open all 2 matching tokens in new tabs');
        $response->assertSee('data-bhc-token-fallback', false);
        $this->assertSame([$newerToken->id, $token->id], $response->viewData('bhcTokens')->modelKeys());
    }

    public function test_missing_bhc_numbers_remain_plain_text_without_token_links(): void
    {
        $user = $this->createViewer();
        $worker = $this->createWorker($this->createToken($user, ['bhc_number' => null]));
        $this->createToken($user, ['bhc_number' => '']);

        $response = $this->actingAs($user)->get(route('workers.show', $worker));

        $response->assertOk();
        $response->assertSeeText('BHC number pending');
        $response->assertSeeText('Not assigned');
        $response->assertDontSee('data-bhc-token-urls', false);
        $this->assertCount(0, $response->viewData('bhcTokens'));
    }

    public function test_bhc_link_text_is_escaped(): void
    {
        $user = $this->createViewer();
        $bhcNumber = 'BHC-<script>alert(1)</script>';
        $worker = $this->createWorker($this->createToken($user, ['bhc_number' => $bhcNumber]));

        $response = $this->actingAs($user)->get(route('workers.show', $worker));

        $response->assertOk();
        $response->assertSee($bhcNumber);
        $response->assertDontSee($bhcNumber, false);
    }

    public function test_guests_cannot_access_worker_token_links(): void
    {
        $worker = $this->createWorker($this->createToken($this->createViewer()));

        $this->get(route('workers.show', $worker))->assertRedirect(route('login'));
    }

    private function createViewer(): User
    {
        $role = Role::create(['name' => 'viewer', 'label' => 'Viewer']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createToken(User $user, array $attributes = []): Token
    {
        return Token::create(array_replace([
            'token_number' => 'VA-12345',
            'bhc_number' => 'BHC-032/26',
            'company_id' => Company::firstOrCreate(['name' => 'Test Company'])->id,
            'agency_id' => Agency::firstOrCreate(['name' => 'Test Agency'])->id,
            'token_category_id' => TokenCategory::firstOrCreate(['code' => 'VA'], ['name' => 'Visa Attestation'])->id,
            'received_on' => '2026-08-01',
            'created_by' => $user->id,
        ], $attributes));
    }

    private function createWorker(Token $token): Worker
    {
        return Worker::create([
            'token_id' => $token->id,
            'full_name' => 'Test Worker',
            'passport_number' => 'A00000001',
            'created_by' => $token->created_by,
        ]);
    }
}
