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
        Schema::table('extra_payments', function (Blueprint $table) {
            $table->enum('type', ['loan_repayment', 'savings'])->default('loan_repayment')->after('loan_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extra_payments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
