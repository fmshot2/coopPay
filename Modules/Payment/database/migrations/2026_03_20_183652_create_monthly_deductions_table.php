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
        Schema::create('monthly_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('loan_plan_id')->constrained()->onDelete('cascade');
            $table->string('month'); // format: 2025-01
            $table->decimal('expected_amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('confirmed_at')->nullable();  // when member clicked confirm
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();   // when admin approved
            $table->text('member_note')->nullable();        // optional note from member
            $table->text('admin_note')->nullable();         // optional note from admin
            $table->timestamps();

            $table->unique(['user_id', 'month']); // prevents duplicate confirmation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_deductions');
    }
};
