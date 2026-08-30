<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('registration_no')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('license_no')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('token_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('default_fee', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
        Schema::create('desks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_number')->unique();
            $table->foreignId('token_category_id')->constrained();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('agency_id')->constrained();
            $table->foreignId('current_desk_id')->nullable()->constrained('desks');
            $table->string('agent_name')->nullable();
            $table->date('received_on');
            $table->unsignedInteger('demanded_workers');
            $table->unsignedInteger('approved_workers')->default(0);
            $table->boolean('pre_selected')->default(false);
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('bhc_number')->nullable()->index();
            $table->string('boesl_status')->default('pending');
            $table->date('boesl_date')->nullable();
            $table->string('received_by')->nullable();
            $table->boolean('site_visit_required')->default(false);
            $table->date('site_visit_date')->nullable();
            $table->string('site_visit_by')->nullable();
            $table->string('visa_status')->default('pending');
            $table->string('file_status')->default('active');
            $table->text('remarks')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'agency_id', 'received_on']);
        });
        Schema::create('token_desk_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_desk_id')->nullable()->constrained('desks');
            $table->foreignId('new_desk_id')->constrained('desks');
            $table->foreignId('changed_by')->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamp('arrived_at');
            $table->timestamp('departed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained();
            $table->string('full_name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->default('Bangladeshi');
            $table->string('national_id')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('permanent_address')->nullable();
            $table->text('present_address')->nullable();
            $table->string('passport_number')->unique();
            $table->date('passport_issue_date')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('passport_authority')->nullable();
            $table->string('job_category')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('contract_duration')->nullable();
            $table->string('demand_reference')->nullable();
            $table->string('applicant_type')->nullable();
            $table->boolean('pre_selected')->default(false);
            $table->string('registration_number')->nullable()->index();
            $table->date('registration_date')->nullable();
            $table->string('tracking_status')->default('pending');
            $table->string('visa_status')->default('pending');
            $table->date('flight_date')->nullable();
            $table->string('flight_status')->default('pending');
            $table->date('insurance_date')->nullable();
            $table->string('insurance_status')->default('pending');
            $table->string('ic_status')->default('pending');
            $table->string('medical_status')->default('pending');
            $table->string('boesl_status')->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('applicant_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('field');
            $table->string('previous_value')->nullable();
            $table->string('new_value')->nullable();
            $table->foreignId('changed_by')->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('token_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('version')->default(1);
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('generated_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('token_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('version')->default(1);
            $table->string('path')->nullable();
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('module');
            $table->string('record_type')->nullable();
            $table->string('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['module', 'record_id']);
        });
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->boolean('successful');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['login_attempts', 'notifications', 'system_settings', 'audit_logs', 'generated_letters', 'documents', 'applicant_status_histories', 'applicants', 'token_desk_histories', 'tokens', 'desks', 'token_categories', 'agencies', 'companies', 'permission_role', 'permissions'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('role_id'));
        Schema::dropIfExists('roles');
    }
};
