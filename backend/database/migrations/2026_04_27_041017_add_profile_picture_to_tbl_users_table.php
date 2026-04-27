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
        Schema::table('tbl_users', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_users', 'profile_picture')) {
                $table->string('profile_picture', 255)->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_users', 'profile_picture')) {
                $table->dropColumn('profile_picture');
            }
        });
    }
};
