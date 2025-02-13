<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spam_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('email'); // email or phone
            $table->string('value'); // email address or phone number
            $table->text('reason')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->integer('attempt_count')->default(0);
            $table->timestamps();
            
            $table->unique(['type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spam_contacts');
    }
};
