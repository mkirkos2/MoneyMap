<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('savings_goal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_goal_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['contribution', 'withdrawal']);
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['savings_goal_id', 'entry_date', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_goal_entries');
    }
};
