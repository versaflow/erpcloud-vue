<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('host');
            $table->integer('port');
            $table->string('username');
            $table->string('password');
            $table->foreignId('department_id')->nullable()->constrained();
            $table->boolean('enabled')->default(true);
            $table->json('imap_settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_setting_id')->constrained('email_settings')->onDelete('cascade');
            $table->string('from_name');
            $table->string('email');
            $table->string('host');
            $table->integer('port');
            $table->string('username');
            $table->string('password');
            $table->string('encryption');
            $table->timestamps();
        });

        Schema::create('email_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_setting_id')->constrained('email_settings')->onDelete('cascade');
            $table->string('name');
            $table->text('content');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_signatures');
        Schema::dropIfExists('smtp_settings');
        Schema::dropIfExists('email_settings');
    }
};

?>
