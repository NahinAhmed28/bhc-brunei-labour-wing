<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Document;
use App\Models\Role;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TokenDocumentTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['confirmation-letter', 'confirmation.pdf', 'application/pdf'])]
    #[TestWith(['demand-letter', 'demand-letter.png', 'image/png'])]
    public function test_administrator_can_upload_a_supported_token_document(string $type, string $filename, string $mimeType): void
    {
        Storage::fake('local');
        [$administrator, $token] = $this->createToken('administrator');

        $response = $this->actingAs($administrator)
            ->from(route('tokens.index'))
            ->post(route('tokens.documents.store', $token), [
                'type' => $type,
                'file' => UploadedFile::fake()->create($filename, 100, $mimeType),
            ]);

        $document = Document::sole();

        $response->assertRedirect(route('tokens.index'));
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'token_id' => $token->id,
            'worker_id' => null,
            'type' => $type,
            'version' => 1,
            'original_name' => $filename,
            'uploaded_by' => $administrator->id,
        ]);
        $this->assertNotNull($document->collection_key);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'documents',
            'record_id' => (string) $document->id,
            'action' => 'upload-document',
        ]);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_token_document_upload_rejects_unsupported_files(): void
    {
        Storage::fake('local');
        [$administrator, $token] = $this->createToken('administrator');

        $response = $this->actingAs($administrator)->post(route('tokens.documents.store', $token), [
            'type' => 'confirmation-letter',
            'file' => UploadedFile::fake()->create('letter.php', 10, 'application/x-httpd-php'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_token_edit_page_renders_multiple_letters_and_their_latest_previews(): void
    {
        [$administrator, $token] = $this->createToken('administrator');
        $confirmationLetter = Document::create([
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'collection_key' => '11111111-1111-4111-8111-111111111111',
            'version' => 2,
            'original_name' => 'confirmation-v2.pdf',
            'path' => 'documents/tokens/'.$token->id.'/confirmation-v2.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $administrator->id,
        ]);
        $secondConfirmationLetter = Document::create([
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'collection_key' => '22222222-2222-4222-8222-222222222222',
            'version' => 1,
            'original_name' => 'second-confirmation.pdf',
            'path' => 'documents/tokens/'.$token->id.'/second-confirmation.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'uploaded_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('tokens.edit', $token));

        $response->assertSeeText('Confirmation and demand letters');
        $response->assertSee('enctype="multipart/form-data"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'action="'.route('tokens.documents.store', $token).'"'));
        $response->assertSee('name="type" value="confirmation-letter"', false);
        $response->assertSee('name="type" value="demand-letter"', false);
        $response->assertSeeText('Add another confirmation letter');
        $response->assertDontSeeText('Add another demand letter');
        $response->assertSee('href="'.route('documents.preview', $confirmationLetter).'"', false);
        $response->assertSee('href="'.route('documents.preview', $secondConfirmationLetter).'"', false);
        $response->assertSeeText('second-confirmation.pdf');
    }

    public function test_token_create_page_does_not_offer_letter_uploads(): void
    {
        [$administrator] = $this->createToken('administrator');

        $response = $this->actingAs($administrator)->get(route('tokens.create'));

        $response->assertDontSeeText('Official attachments');
        $response->assertDontSee('enctype="multipart/form-data"', false);
    }

    public function test_uploading_an_existing_letter_type_creates_another_letter(): void
    {
        Storage::fake('local');
        [$administrator, $token] = $this->createToken('administrator');
        $existingDocument = Document::create([
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'collection_key' => '11111111-1111-4111-8111-111111111111',
            'version' => 1,
            'original_name' => 'old-confirmation.pdf',
            'path' => 'documents/tokens/'.$token->id.'/old-confirmation.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)
            ->from(route('tokens.index'))
            ->post(route('tokens.documents.store', $token), [
                'type' => 'confirmation-letter',
                'file' => UploadedFile::fake()->create('new-confirmation.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('tokens.index'));
        $newDocument = Document::whereKeyNot($existingDocument->id)->sole();
        $this->assertDatabaseHas('documents', [
            'id' => $newDocument->id,
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'version' => 1,
            'original_name' => 'new-confirmation.pdf',
        ]);
        $this->assertNotSame($existingDocument->collection_key, $newDocument->collection_key);
    }

    public function test_uploading_a_second_demand_letter_is_rejected(): void
    {
        Storage::fake('local');
        [$administrator, $token] = $this->createToken('administrator');
        Document::create([
            'token_id' => $token->id,
            'type' => 'demand-letter',
            'collection_key' => '11111111-1111-4111-8111-111111111111',
            'version' => 1,
            'original_name' => 'original-demand.pdf',
            'path' => 'documents/tokens/'.$token->id.'/original-demand.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)
            ->from(route('tokens.edit', $token))
            ->post(route('tokens.documents.store', $token), [
                'type' => 'demand-letter',
                'file' => UploadedFile::fake()->create('second-demand.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('tokens.edit', $token));
        $response->assertSessionHasErrors([
            'file' => 'This token already has a demand letter. Upload a new version of the existing letter instead.',
        ]);
        $this->assertSame(1, Document::where('token_id', $token->id)->where('type', 'demand-letter')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_token_edit_page_only_offers_a_new_demand_letter_field_when_none_exists(): void
    {
        [$administrator, $token] = $this->createToken('administrator');
        $demandLetter = Document::create([
            'token_id' => $token->id,
            'type' => 'demand-letter',
            'collection_key' => '11111111-1111-4111-8111-111111111111',
            'version' => 1,
            'original_name' => 'demand.pdf',
            'path' => 'documents/tokens/'.$token->id.'/demand.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('tokens.edit', $token));

        $response->assertSee('action="'.route('tokens.documents.update', [$token, $demandLetter]).'"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'action="'.route('tokens.documents.store', $token).'"'));
        $response->assertDontSeeText('Demand letter file');
        $response->assertSeeText('Add another confirmation letter');
    }

    public function test_updating_one_confirmation_letter_creates_a_new_version_for_only_that_letter(): void
    {
        Storage::fake('local');
        [$administrator, $token] = $this->createToken('administrator');
        $document = Document::create([
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'collection_key' => '11111111-1111-4111-8111-111111111111',
            'version' => 1,
            'original_name' => 'first-confirmation.pdf',
            'path' => 'documents/tokens/'.$token->id.'/first-confirmation.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $administrator->id,
        ]);
        Document::create([
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'collection_key' => '22222222-2222-4222-8222-222222222222',
            'version' => 1,
            'original_name' => 'second-confirmation.pdf',
            'path' => 'documents/tokens/'.$token->id.'/second-confirmation.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)
            ->put(route('tokens.documents.update', [$token, $document]), [
                'type' => 'confirmation-letter',
                'file' => UploadedFile::fake()->create('first-confirmation-v2.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'token_id' => $token->id,
            'collection_key' => $document->collection_key,
            'version' => 2,
            'original_name' => 'first-confirmation-v2.pdf',
        ]);
        $this->assertSame(1, Document::where('collection_key', '22222222-2222-4222-8222-222222222222')->count());
    }

    public function test_updating_the_demand_letter_creates_a_new_version_of_the_same_letter(): void
    {
        Storage::fake('local');
        [$administrator, $token] = $this->createToken('administrator');
        $document = Document::create([
            'token_id' => $token->id,
            'type' => 'demand-letter',
            'collection_key' => '11111111-1111-4111-8111-111111111111',
            'version' => 1,
            'original_name' => 'demand.pdf',
            'path' => 'documents/tokens/'.$token->id.'/demand.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)
            ->put(route('tokens.documents.update', [$token, $document]), [
                'type' => 'demand-letter',
                'file' => UploadedFile::fake()->create('demand-v2.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'token_id' => $token->id,
            'type' => 'demand-letter',
            'collection_key' => $document->collection_key,
            'version' => 2,
            'original_name' => 'demand-v2.pdf',
        ]);
        $this->assertSame(1, Document::where('token_id', $token->id)
            ->where('type', 'demand-letter')
            ->distinct('collection_key')
            ->count('collection_key'));
    }

    public function test_data_entry_user_cannot_upload_token_documents(): void
    {
        Storage::fake('local');
        [$dataEntryUser, $token] = $this->createToken('data-entry');

        $response = $this->actingAs($dataEntryUser)->post(route('tokens.documents.store', $token), [
            'type' => 'confirmation-letter',
            'file' => UploadedFile::fake()->create('confirmation.pdf', 100, 'application/pdf'),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_authorized_user_can_preview_a_token_document_inline(): void
    {
        Storage::fake('local');
        [$viewer, $token] = $this->createToken('viewer');
        $path = 'documents/tokens/'.$token->id.'/confirmation.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test document');
        $document = Document::create([
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'version' => 1,
            'original_name' => 'confirmation.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 22,
            'uploaded_by' => $viewer->id,
        ]);

        $response = $this->actingAs($viewer)->get(route('documents.preview', $document));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('x-content-type-options', 'nosniff');
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
    }

    /**
     * @return array{User, Token}
     */
    private function createToken(string $roleName): array
    {
        $role = Role::create(['name' => $roleName, 'label' => ucwords(str_replace('-', ' ', $roleName))]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $company = Company::create(['name' => 'Brunei Harbour Services']);
        $agency = Agency::create(['name' => 'Dhaka Workforce Agency']);
        $category = TokenCategory::create(['name' => 'Demand Letter', 'code' => 'DL']);
        $token = Token::create([
            'token_number' => 'BHC-2608-00001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 12,
            'approved_workers' => 8,
            'boesl_status' => 'pending',
            'visa_status' => 'processing',
            'file_status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return [$user, $token];
    }
}
