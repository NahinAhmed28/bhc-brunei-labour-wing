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
        Schema::table('tokens', function (Blueprint $table) {
            $table->unsignedInteger('required_worker_changes')->nullable()->after('required_visa_attestation');
        });

        $changePreWorkerCategoryIds = DB::table('token_categories')
            ->where('code', 'CPA')
            ->pluck('id');

        DB::table('tokens')
            ->whereIn('token_category_id', $changePreWorkerCategoryIds)
            ->update([
                'required_worker_changes' => DB::raw('demanded_workers'),
                'demanded_workers' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $changePreWorkerCategoryIds = DB::table('token_categories')
            ->where('code', 'CPA')
            ->pluck('id');

        DB::table('tokens')
            ->whereIn('token_category_id', $changePreWorkerCategoryIds)
            ->whereNull('demanded_workers')
            ->update(['demanded_workers' => DB::raw('required_worker_changes')]);

        Schema::table('tokens', function (Blueprint $table) {
            $table->dropColumn('required_worker_changes');
        });
    }
};
