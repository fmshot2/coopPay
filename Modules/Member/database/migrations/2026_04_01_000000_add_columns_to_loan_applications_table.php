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
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('loan_type_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->integer('duration_months');
            $table->decimal('interest_rate', 8, 2);
            $table->decimal('monthly_payment', 15, 2);
            $table->decimal('total_payment', 15, 2);
            $table->text('purpose');
            $table->string('status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['loan_type_id']);
            $table->dropForeign(['user_id']);

            $table->dropColumn([
                'approved_by',
                'approved_at',
                'rejection_reason',
                'status',
                'purpose',
                'total_payment',
                'monthly_payment',
                'interest_rate',
                'duration_months',
                'amount',
                'loan_type_id',
                'user_id',
            ]);
        });
    }
};
