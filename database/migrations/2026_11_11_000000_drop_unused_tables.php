<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop legacy and unused framework tables so the database only contains
     * active MIT Campus Kiosk domain tables for documentation and production.
     */
    public function up(): void
    {
        // 1. Remove legacy faculty_id foreign key & column from waypoints if present
        if (Schema::hasTable('waypoints') && Schema::hasColumn('waypoints', 'faculty_id')) {
            try {
                Schema::table('waypoints', function (Blueprint $table) {
                    $table->dropForeign(['faculty_id']);
                });
            } catch (\Throwable $e) {
                // Foreign key may already be absent depending on DB driver
            }

            try {
                Schema::table('waypoints', function (Blueprint $table) {
                    $table->dropColumn('faculty_id');
                });
            } catch (\Throwable $e) {
                // Ignore if column cannot be dropped
            }
        }

        // 2. Drop all unused / empty tables
        $unusedTables = [
            'faculties',
            'password_reset_tokens',
            'jobs',
            'job_batches',
            'failed_jobs',
            'cache',
            'cache_locks',
            'sessions',
        ];

        foreach ($unusedTables as $table) {
            Schema::dropIfExists($table);
        }

        // 3. Clean up removed migration entries from the migrations table
        if (Schema::hasTable('migrations')) {
            DB::table('migrations')->whereIn('migration', [
                '0001_01_01_000001_create_cache_table',
                '0001_01_01_000002_create_jobs_table',
                '2026_04_03_000001_create_faculties_table',
            ])->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Unused tables are intentionally removed permanently.
    }
};
