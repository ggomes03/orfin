<?php

use App\Models\ExpenseCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            // Migrate legacy category code to the new catalog without changing referenced IDs.
            DB::table('expense_categories')
                ->where('code', 'food')
                ->update([
                    'code' => ExpenseCategory::CODE_PERSONAL_EXPENSES,
                    'name' => ExpenseCategory::defaultDefinitions()[ExpenseCategory::CODE_PERSONAL_EXPENSES],
                    'updated_at' => now(),
                ]);

            foreach (ExpenseCategory::defaultDefinitions() as $code => $name) {
                DB::table('expense_categories')->updateOrInsert(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('expense_categories')
                ->where('code', ExpenseCategory::CODE_PERSONAL_EXPENSES)
                ->update([
                    'code' => 'food',
                    'name' => 'Alimentacao',
                    'updated_at' => now(),
                ]);

            DB::table('expense_categories')
                ->whereIn('code', [ExpenseCategory::CODE_EDUCATION, ExpenseCategory::CODE_OTHER])
                ->delete();

            DB::table('expense_categories')
                ->where('code', ExpenseCategory::CODE_HOUSING)
                ->update([
                    'name' => 'Moradia',
                    'updated_at' => now(),
                ]);
        });
    }
};
