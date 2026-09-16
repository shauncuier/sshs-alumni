<?php

declare(strict_types=1);

use App\Enums\BatchStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batches are keyed by SSC year — the school opened in 1976, so the first
 * batch is roughly SSC 1981.
 *
 * `members.batch_id` is the single source of batch membership. There is
 * deliberately no `batch_members` pivot: a member belongs to exactly one SSC
 * batch, and a pivot could disagree with the member row.
 *
 * @see docs/02-database-schema.md sections 1.6 and 5
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 80);
            $table->string('name_bn', 80)->nullable();
            $table->smallInteger('ssc_year')->unique();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->string('cover_path', 255)->nullable();
            // Counter cache maintained by MemberObserver. Without it the batch
            // index issues one COUNT(*) per batch.
            $table->unsignedInteger('members_count')->default(0);
            $table->string('status', 16)->default(BatchStatus::Active->value);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status', 'batches_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
