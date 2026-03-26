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
        Schema::table('income_sources', function (Blueprint $table) {
            $table->date('monthly_amount_started_at')->nullable()->after('monthly_amount');
        });

        Schema::create('income_source_amount_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_source_id')->constrained('income_sources')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('effective_from');
            $table->timestamps();
        });

        DB::table('income_sources')
            ->whereNotNull('monthly_amount')
            ->orderBy('id')
            ->chunkById(100, function ($sources): void {
                foreach ($sources as $source) {
                    $effectiveFrom = $source->monthly_amount_started_at
                        ?? substr((string) $source->created_at, 0, 10)
                        ?? now()->toDateString();

                    DB::table('income_source_amount_histories')->insert([
                        'income_source_id' => $source->id,
                        'amount' => $source->monthly_amount,
                        'effective_from' => $effectiveFrom,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('income_sources')
                        ->where('id', $source->id)
                        ->update(['monthly_amount_started_at' => $effectiveFrom]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_source_amount_histories');

        Schema::table('income_sources', function (Blueprint $table) {
            $table->dropColumn('monthly_amount_started_at');
        });
    }
};
