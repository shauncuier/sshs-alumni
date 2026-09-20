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
        Schema::create('job_postings', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('title');
            $table->string('company_name')->index();
            $table->string('location')->nullable();
            $table->string('workplace_type')->default('on_site')->index();
            $table->string('employment_type')->default('full_time')->index();
            $table->string('experience_level')->nullable()->index();
            $table->string('salary_range')->nullable();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->string('application_url_or_email');
            $table->date('deadline_at')->nullable()->index();
            $table->string('status')->default(ContentStatus::Published->value)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
