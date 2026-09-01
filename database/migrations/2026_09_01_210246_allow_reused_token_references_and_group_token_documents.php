<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropUnique('tokens_token_number_unique');
            $table->index('token_number');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('collection_key')->nullable()->after('type');
            $table->index(['token_id', 'type', 'collection_key'], 'documents_token_type_collection_index');
        });

        DB::table('documents')
            ->whereNotNull('token_id')
            ->whereNull('worker_id')
            ->select(['token_id', 'type'])
            ->distinct()
            ->orderBy('token_id')
            ->each(function (object $documentGroup): void {
                DB::table('documents')
                    ->where('token_id', $documentGroup->token_id)
                    ->whereNull('worker_id')
                    ->where('type', $documentGroup->type)
                    ->update(['collection_key' => (string) Str::uuid()]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_token_type_collection_index');
            $table->dropColumn('collection_key');
        });

        Schema::table('tokens', function (Blueprint $table) {
            $table->dropIndex('tokens_token_number_index');
            $table->unique('token_number');
        });
    }
};
