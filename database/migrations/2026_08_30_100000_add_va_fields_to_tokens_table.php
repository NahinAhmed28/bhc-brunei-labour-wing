<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            // Make demanded_workers nullable — VA-category tokens don't carry a worker demand count
            $table->unsignedInteger('demanded_workers')->nullable()->change();
            // Add visa-attestation count field used when the category code is 'VA'
            $table->unsignedInteger('required_visa_attestation')->nullable()->after('demanded_workers');
        });
    }

    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropColumn('required_visa_attestation');
            $table->unsignedInteger('demanded_workers')->nullable(false)->change();
        });
    }
};
