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
        Schema::table('loan_applications', function (Blueprint $table) {});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            // $table->dropForeign(['approved_by']);
            // $table->dropForeign(['loan_type_id']);
            // $table->dropForeign(['user_id']);

            // $table->dropColumn([
            //     'approved_by',
            //     'approved_at',
            //     'rejection_reason',
            //     'status',
            //     'purpose',
            //     'total_payment',
            //     'monthly_payment',
            //     'interest_rate',
            //     'duration_months',
            //     'amount',
            //     'loan_type_id',
            //     'user_id',
            // ]);
        });
    }
};
