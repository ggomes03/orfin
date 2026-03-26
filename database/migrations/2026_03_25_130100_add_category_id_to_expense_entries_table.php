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
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('description')->constrained('expense_categories')->nullOnDelete();
        });

        $categoryIdByCode = DB::table('expense_categories')->pluck('id', 'code');

        foreach ($categoryIdByCode as $code => $categoryId) {
            DB::table('expense_entries')
                ->where('category', $code)
                ->update(['category_id' => $categoryId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
