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
        Schema::create('roles', function (Blueprint $table): void {
            $table->innoDb();
            $table->id();
            $table->string('name', 20)->charset('ascii')->collation('ascii_bin')->unique();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_name_check CHECK (BINARY name IN ('user', 'admin', 'superadmin'))");

        Schema::create('role_user', function (Blueprint $table): void {
            $table->innoDb();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'role_id']);
            $table->index(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
