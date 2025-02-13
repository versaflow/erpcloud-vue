<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHelpdeskTables extends Migration
{
    public function up()
    {
        // 1. Create support_users table
        Schema::create('support_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->string('timezone')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
        });

        // 2. Create conversations table
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->string('status')->default('new');
            $table->string('email_message_id')->nullable();
            $table->foreignId('support_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_email');
            $table->string('to_email');
            $table->string('source')->default('email');
            $table->string('source_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            
            // Add indexes
            $table->index('email_message_id');
            $table->index('status');
            $table->index('created_at');
        });

        // 3. Create messages table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('support_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('content');
            $table->string('email_message_id')->nullable();
            $table->string('type')->default('initial');
            $table->timestamps();
            
            // Add indexes
            $table->index('email_message_id');
            $table->index('created_at');
        });

        // 4. Create message_attachments table
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type');
            $table->integer('size');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('support_users');
    }
}
