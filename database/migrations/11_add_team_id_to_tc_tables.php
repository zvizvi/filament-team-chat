<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tc_channels', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->after('id')->index();
        });

        Schema::table('tc_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tc_channels', function (Blueprint $table) {
            $table->dropIndex(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::table('tc_conversations', function (Blueprint $table) {
            $table->dropIndex(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
