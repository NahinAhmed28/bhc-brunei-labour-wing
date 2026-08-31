<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Document;
use App\Models\Role;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_list_renders_one_modal_and_keeps_actions_independent(): void
    {
        [$administrator, $token] = $this->createToken();

        $response = $this->actingAs($administrator)->get(route('tokens.index'));

        $response->assertSee('class="token-row"', false);
        $response->assertSee('class="card mb-4 token-register-card"', false);
        $response->assertSee('href="'.asset('assets/css/token-list.css').'"', false);
        $response->assertSee('class="token-column-label"', false);
        $response->assertSee('scope="col"', false);
        $response->assertSee('class="token-action token-action-view"', false);
        $response->assertSee('class="token-action token-action-edit"', false);
        $response->assertSee('data-token-modal-url="'.route('tokens.modal', $token).'"', false);
        $response->assertSee('data-token-modal-url="'.route('tokens.workers.modal', $token).'"', false);
        $response->assertSee('token-workers-button', false);
        $response->assertSee('aria-controls="tokenDetailsModal"', false);
        $response->assertSee('href="'.route('tokens.edit', $token).'"', false);
        $response->assertDontSee('href="'.route('tokens.show', $token).'"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="tokenDetailsModal"'));
    }

    public function test_modal_renders_complete_token_details_and_uploaded_document_previews(): void
    {
        [$administrator, $token] = $this->createToken();
        $confirmationLetter = Document::create([
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'version' => 1,
            'original_name' => 'confirmation.pdf',
            'path' => 'documents/tokens/'.$token->id.'/confirmation.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $administrator->id,
        ]);
        $demandLetter = Document::create([
            'token_id' => $token->id,
            'type' => 'demand-letter',
            'version' => 1,
            'original_name' => 'demand-letter.png',
            'path' => 'documents/tokens/'.$token->id.'/demand-letter.png',
            'mime_type' => 'image/png',
            'size' => 2048,
            'uploaded_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('tokens.modal', $token));

        $response->assertSeeText($token->token_number);
        $response->assertSeeText('Brunei Harbour Services');
        $response->assertSeeText('Submission details');
        $response->assertSeeText('Processing details');
        $response->assertSeeText('Workers');
        $response->assertSeeText('File transfer history');
        $response->assertSeeText('Documents');
        $response->assertSeeText('Official attachments');
        $response->assertSeeText('Confirmation Letter');
        $response->assertSeeText('Demand Letter');
        $response->assertSeeText('Record information');
        $response->assertDontSeeText('Amount');
        $response->assertDontSeeText('Receipt number');
        $response->assertSee('action="'.route('tokens.documents.store', $token).'"', false);
        $response->assertSee('src="'.route('documents.preview', $confirmationLetter).'"', false);
        $response->assertSee('src="'.route('documents.preview', $demandLetter).'"', false);
        $response->assertSee(
            'href="'.route('tokens.pdf', $token).'" target="_blank" rel="noopener"',
            false,
        );
        $response->assertSeeText('View PDF');
        $response->assertDontSeeText('PDF preview');
    }

    public function test_worker_roster_modal_links_worker_names_to_their_details(): void
    {
        [$administrator, $token] = $this->createToken();
        $worker = Worker::create([
            'token_id' => $token->id,
            'full_name' => 'Nur Rahman',
            'passport_number' => 'BA0123456',
            'registration_number' => 'REG-1001',
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('tokens.workers.modal', $token));

        $response->assertOk();
        $response->assertSeeText('Worker roster');
        $response->assertSeeText($token->token_number);
        $response->assertSeeText($worker->full_name);
        $response->assertSee('href="'.route('workers.show', $worker).'"', false);
    }

    /**
     * @return array{User, Token}
     */
    private function createToken(): array
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);
        $company = Company::create(['name' => 'Brunei Harbour Services']);
        $agency = Agency::create(['name' => 'Dhaka Workforce Agency']);
        $category = TokenCategory::create(['name' => 'Visa Attestation', 'code' => 'VA', 'display_order' => 1]);
        $token = Token::create([
            'token_number' => 'BHC260800001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 12,
            'approved_workers' => 8,
            'amount' => 125.50,
            'receipt_number' => 'REC1001',
            'boesl_status' => 'pending',
            'visa_status' => 'processing',
            'file_status' => 'active',
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        return [$administrator, $token];
    }
}
