<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->innoDb();
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organisation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_name', 100);
            $table->string('subject_type', 100)->nullable();
            $table->uuid('subject_public_id')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['organisation_id', 'created_at', 'id'], 'audit_organisation_created_index');
            $table->index(['subject_type', 'subject_public_id'], 'audit_subject_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
