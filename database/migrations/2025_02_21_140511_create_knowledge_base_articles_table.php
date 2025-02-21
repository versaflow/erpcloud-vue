<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');  // Changed from text to longText
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('author_id')->constrained('users');
            $table->string('status')->default('published');
            $table->json('tags')->nullable();
            $table->integer('sent_count')->default(0); 
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('knowledge_base_articles');
    }
};

