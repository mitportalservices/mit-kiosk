<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear unwanted job titles from the honorific title column
        DB::table('users')
            ->whereNotNull('title')
            ->whereNotIn('title', ['', 'Mr.', 'Ms.', 'Mx.', 'Dr.', 'Prof.'])
            ->update(['title' => null]);

        // Clean existing activity logs
        DB::table('activity_logs')
            ->where('user_name', 'like', '%Professor of Mathematics%')
            ->update([
                'user_name' => DB::raw("REPLACE(user_name, 'Professor of Mathematics ', '')")
            ]);

        DB::table('activity_logs')
            ->where('description', 'like', '%Professor of Mathematics%')
            ->update([
                'description' => DB::raw("REPLACE(description, 'Professor of Mathematics ', '')")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed for cleanup
    }
};
