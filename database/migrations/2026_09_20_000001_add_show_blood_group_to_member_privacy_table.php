<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_privacy', function (Blueprint $table): void {
            $table->boolean('show_blood_group')->default(false)->after('show_in_batch_list');
            $table->index('show_blood_group', 'member_privacy_show_blood_group_index');
        });
    }

    public function down(): void
    {
        Schema::table('member_privacy', function (Blueprint $table): void {
            $table->dropIndex('member_privacy_show_blood_group_index');
            $table->dropColumn('show_blood_group');
        });
    }
};
