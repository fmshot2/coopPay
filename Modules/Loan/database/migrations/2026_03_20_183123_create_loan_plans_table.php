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
        Schema::create('loan_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('loan_amount', 12, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->decimal('repayment_per_month', 12, 2);
            $table->integer('total_months');
            $table->integer('months_remaining');
            $table->decimal('amount_remaining', 12, 2);
            $table->date('start_date');
            $table->date('next_due_date')->nullable();
            $table->enum('status', ['active', 'completed', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_plans');
    }
};
