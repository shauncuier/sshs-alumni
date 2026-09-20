<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('certificate_no')->unique()->index();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('recipient_name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('appreciation')->index();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->date('issue_date');
            $table->char('qr_token', 40)->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
