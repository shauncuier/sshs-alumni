<?php

declare(strict_types=1);

use App\Enums\Locale;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the starter kit's users table with the columns the platform needs.
 *
 * A User is a login identity. A Member is an alumni record. They link 1:1 but
 * either can exist alone, so nothing membership-related lives here.
 *
 * @see docs/02-database-schema.md section 3
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 5)->default(Locale::Bn->value)->after('email');
            $table->string('avatar_path', 255)->nullable()->after('locale');
            $table->string('phone', 32)->nullable()->after('avatar_path');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('status', 24)->default(UserStatus::Active->value)->after('phone_verified_at');
            $table->timestamp('last_active_at')->nullable()->after('status');
            $table->softDeletes();

            $table->index('phone', 'users_phone_index');
            $table->index('status', 'users_status_index');
            $table->index('last_active_at', 'users_last_active_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_phone_index');
            $table->dropIndex('users_status_index');
            $table->dropIndex('users_last_active_at_index');

            $table->dropSoftDeletes();
            $table->dropColumn([
                'locale',
                'avatar_path',
                'phone',
                'phone_verified_at',
                'status',
                'last_active_at',
            ]);
        });
    }
};
