<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('support_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('can_see_all_department_tickets')->default(false);
            $table->timestamps();
        });

  
    }

    public function down()
    {
        Schema::dropIfExists('support_settings');
    }
};
