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
            'applicant_id' => null,
            'type' => $type,
            'version' => 1,
            'original_name' => $filename,
            'uploaded_by' => $administrator->id,
        ]);
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

    public function test_token_edit_page_renders_letter_uploads_and_the_latest_preview(): void
    {
        [$administrator, $token] = $this->createToken('administrator');
        $confirmationLetter = Document::create([
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'version' => 2,
            'original_name' => 'confirmation-v2.pdf',
            'path' => 'documents/tokens/'.$token->id.'/confirmation-v2.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('tokens.edit', $token));

        $response->assertSeeText('Confirmation and demand letters');
        $response->assertSee('enctype="multipart/form-data"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'action="'.route('tokens.documents.store', $token).'"'));
        $response->assertSee('name="type" value="confirmation-letter"', false);
        $response->assertSee('name="type" value="demand-letter"', false);
        $response->assertSee('href="'.route('documents.preview', $confirmationLetter).'"', false);
        $response->assertSeeText('Preview v2');
    }

    public function test_uploading_an_existing_letter_type_creates_a_new_version(): void
    {
        Storage::fake('local');
        [$administrator, $token] = $this->createToken('administrator');
        Document::create([
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
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
        $this->assertDatabaseHas('documents', [
            'token_id' => $token->id,
            'type' => 'confirmation-letter',
            'version' => 2,
            'original_name' => 'new-confirmation.pdf',
        ]);
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
