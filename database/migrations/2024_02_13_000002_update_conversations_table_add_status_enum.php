<?php

use App\Enums\ConversationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;  // Add this import

return new class extends Migration
{
    public function up(): void
    {
        // First convert existing statuses to match enum values
        DB::table('conversations')->update([
            'status' => DB::raw("CASE 
                WHEN status = 'new' THEN 'new'
                WHEN status = 'open' THEN 'open'
                WHEN status = 'pending' THEN 'pending'
                WHEN status = 'resolved' THEN 'resolved'
                WHEN status = 'closed' THEN 'closed'
                WHEN status = 'spam' THEN 'spam'
                WHEN status = 'assigned' THEN 'assigned'
                ELSE 'new'
            END")
        ]);

        // Then modify the column to use enum values
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('status')->default(ConversationStatus::NEW->value)
                  ->change();
        });
    }

    public function down(): void
    {
        // If needed, convert back to simple string
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('status')->default('new')->change();
        });
    }
};
