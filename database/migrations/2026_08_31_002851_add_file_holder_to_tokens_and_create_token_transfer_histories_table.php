<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->foreignId('current_holder_id')->nullable()->after('current_desk_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('token_transfer_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_holder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_holder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transferred_by')->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();
            $table->index(['token_id', 'transferred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_transfer_histories');

        Schema::table('tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_holder_id');
        });
    }
};
