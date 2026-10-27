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
        Schema::create('users', function (Blueprint $table): void {
            $table->innoDb();
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organisation_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('username', 50)->charset('ascii')->collation('ascii_bin')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country', 2)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('setup_link_sent_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->unique(['id', 'organisation_id'], 'users_id_organisation_unique');
            $table->index(['organisation_id', 'created_at', 'id'], 'users_organisation_created_index');
            $table->index(['activated_at', 'disabled_at', 'setup_link_sent_at', 'id'], 'users_pending_setup_index');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_username_check CHECK (CHAR_LENGTH(username) > 0 AND username NOT REGEXP BINARY '[^a-z0-9._-]')");

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
