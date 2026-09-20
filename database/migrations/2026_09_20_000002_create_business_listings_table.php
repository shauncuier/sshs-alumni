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
        Schema::create('business_listings', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->index();
            $table->string('industry')->nullable()->index();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('district')->nullable()->index();
            $table->string('country')->default('Bangladesh');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('alumni_discount')->nullable();
            $table->string('status')->default(ContentStatus::Published->value)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_listings');
    }
};
