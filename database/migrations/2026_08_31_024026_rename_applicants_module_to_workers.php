<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('applicants', 'workers');
        Schema::rename('applicant_status_histories', 'worker_status_histories');

        Schema::table('workers', function (Blueprint $table) {
            $table->renameColumn('applicant_type', 'worker_type');
        });
        Schema::table('worker_status_histories', function (Blueprint $table) {
            $table->renameColumn('applicant_id', 'worker_id');
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('applicant_id', 'worker_id');
        });
        Schema::table('generated_letters', function (Blueprint $table) {
            $table->renameColumn('applicant_id', 'worker_id');
        });

        DB::table('permissions')->where('name', 'manage-applicants')->update([
            'name' => 'manage-workers',
            'label' => 'Manage Workers',
        ]);
        DB::table('audit_logs')->where('module', 'applicants')->update(['module' => 'workers']);
        DB::table('audit_logs')->where('record_id', 'applicant-register')->update(['record_id' => 'worker-register']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('audit_logs')->where('record_id', 'worker-register')->update(['record_id' => 'applicant-register']);
        DB::table('audit_logs')->where('module', 'workers')->update(['module' => 'applicants']);
        DB::table('permissions')->where('name', 'manage-workers')->update([
            'name' => 'manage-applicants',
            'label' => 'Manage Applicants',
        ]);

        Schema::table('generated_letters', function (Blueprint $table) {
            $table->renameColumn('worker_id', 'applicant_id');
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('worker_id', 'applicant_id');
        });
        Schema::table('worker_status_histories', function (Blueprint $table) {
            $table->renameColumn('worker_id', 'applicant_id');
        });
        Schema::table('workers', function (Blueprint $table) {
            $table->renameColumn('worker_type', 'applicant_type');
        });

        Schema::rename('worker_status_histories', 'applicant_status_histories');
        Schema::rename('workers', 'applicants');
    }
};
