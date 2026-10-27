<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table): void {
            $table->innoDb();
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organisation_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_id');
            $table->string('storage_key')->charset('ascii')->collation('ascii_bin')->unique();
            $table->string('original_name');
            $table->string('extension', 50)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->nullable();
            $table->string('status', 20)->charset('ascii')->collation('ascii_bin');
            $table->string('quota_state', 20)->charset('ascii')->collation('ascii_bin');
            $table->unsignedInteger('scan_attempts')->default(0);
            $table->uuid('lease_token')->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->string('scanner_name')->nullable();
            $table->string('scanner_version')->nullable();
            $table->string('signature_version')->nullable();
            $table->string('failure_code', 100)->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('delete_requested_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->foreign(['owner_id', 'organisation_id'], 'files_owner_organisation_fk')
                ->references(['id', 'organisation_id'])->on('users')->restrictOnDelete();
            $table->index(['owner_id', 'created_at', 'id'], 'files_owner_created_index');
            $table->index(['organisation_id', 'created_at', 'id'], 'files_organisation_created_index');
            $table->index(['status', 'updated_at', 'id'], 'files_status_updated_index');
            $table->index(['created_at', 'id'], 'files_created_index');
        });

        DB::statement("ALTER TABLE stored_files ADD CONSTRAINT files_status_check CHECK (BINARY status IN ('receiving', 'quarantined', 'scanning', 'available', 'rejected', 'deleting', 'deleted'))");
        DB::statement("ALTER TABLE stored_files ADD CONSTRAINT files_quota_state_check CHECK (BINARY quota_state IN ('reserved', 'used', 'released'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
