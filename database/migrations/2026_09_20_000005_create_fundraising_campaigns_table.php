<?php

declare(strict_types=1);

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fundraising_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('slug')->unique()->index();
            $table->string('title');
            $table->string('tagline')->nullable();
            $table->text('description');
            $table->decimal('goal_amount', 12, 2);
            $table->decimal('raised_amount', 12, 2)->default(0);
            $table->string('cover_image_path')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('status')->default(ContentStatus::Published->value)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('donations', function (Blueprint $table): void {
            $table->foreignId('fundraising_campaign_id')
                ->nullable()
                ->after('crm_contact_id')
                ->constrained('fundraising_campaigns')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('fundraising_campaign_id');
        });

        Schema::dropIfExists('fundraising_campaigns');
    }
};
