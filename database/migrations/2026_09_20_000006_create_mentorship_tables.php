<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_profiles', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('member_id')->unique()->constrained('members')->cascadeOnDelete();
            $table->string('title');
            $table->string('company_or_institution');
            $table->json('expertise');
            $table->text('bio');
            $table->unsignedInteger('years_of_experience')->default(0);
            $table->unsignedInteger('max_mentees')->default(3);
            $table->boolean('is_available')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mentorship_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('mentor_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('mentee_id')->constrained('members')->cascadeOnDelete();
            $table->string('topic');
            $table->text('message');
            $table->string('status')->default('pending')->index();
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentorship_requests');
        Schema::dropIfExists('mentor_profiles');
    }
};
