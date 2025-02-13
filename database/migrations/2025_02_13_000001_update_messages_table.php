<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['support_user_id']);
            $table->dropForeign(['user_id']);
            
            // Both IDs should be nullable since a message can come from either a support user or an agent
            $table->unsignedBigInteger('support_user_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Add column for message source if not exists
            if (!Schema::hasColumn('messages', 'email_message_id')) {
                $table->string('email_message_id')->nullable();
            }
            
            // Update foreign keys to allow null and cascade deletion
            $table->foreign('support_user_id')
                  ->references('id')
                  ->on('support_users')
                  ->onDelete('set null');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop foreign keys again
            $table->dropForeign(['support_user_id']);
            $table->dropForeign(['user_id']);
            
            // Make columns required again
            $table->unsignedBigInteger('support_user_id')->nullable(false)->change();
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            
            // Drop email_message_id if it was added
            if (Schema::hasColumn('messages', 'email_message_id')) {
                $table->dropColumn('email_message_id');
            }
            
            // Re-add original foreign key constraints
            $table->foreign('support_user_id')
                  ->references('id')
                  ->on('support_users');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users');
        });
    }
};
