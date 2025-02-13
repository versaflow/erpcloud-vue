<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('email_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('email_settings', 'username')) {
                $table->string('username')->nullable(false);
            }
            if (!Schema::hasColumn('email_settings', 'password')) {
                $table->string('password');
            }
            if (!Schema::hasColumn('email_settings', 'enabled')) {
                $table->boolean('enabled')->default(true);
            }
            if (!Schema::hasColumn('email_settings', 'imap_settings')) {
                $table->json('imap_settings')->nullable();
            }
            if (!Schema::hasColumn('email_settings', 'department_id')) {
                $table->foreignId('department_id')->nullable();
            }
            if (!Schema::hasColumn('email_settings', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('email_settings', function (Blueprint $table) {
            $table->dropColumn(['username', 'password', 'enabled', 'imap_settings', 'department_id', 'last_sync_at']);
        });
    }
};
