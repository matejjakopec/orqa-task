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
        Schema::create('storage_accounts', function (Blueprint $table): void {
            $table->innoDb();
            $table->foreignId('user_id')->primary()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('quota_bytes');
            $table->unsignedBigInteger('used_bytes')->default(0);
            $table->unsignedBigInteger('reserved_bytes')->default(0);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE storage_accounts ADD CONSTRAINT storage_accounts_quota_check CHECK (used_bytes + reserved_bytes <= quota_bytes)');
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_accounts');
    }
};
