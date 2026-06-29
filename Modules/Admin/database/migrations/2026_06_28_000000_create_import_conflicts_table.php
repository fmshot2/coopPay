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
        Schema::create('import_conflicts', function (Blueprint $table) {
            $table->id();

            // Raw data from the CSV row — mirrors users table fields
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('max_loan_amount', 15, 2)->nullable();
            $table->decimal('monthly_savings_target', 15, 2)->nullable();
            $table->foreignId('division_id')->nullable()->constrained()->restrictOnDelete();

            // Polymorphic source — what triggered this conflict
            $table->string('importable_type');          // e.g. 'member_import', 'loan_import'
            $table->unsignedBigInteger('importable_id')->nullable();

            // Human-readable action label for display/filtering
            $table->string('source');                   // e.g. 'member_import', 'loan_import'

            // IDs of existing users this row clashed with
            $table->json('conflicting_user_ids');

            $table->enum('status', ['pending', 'resolved', 'dismissed'])->default('pending');
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            $table->index(['importable_type', 'importable_id']);
            $table->index('status');
            $table->index('division_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('years');
    }
};
