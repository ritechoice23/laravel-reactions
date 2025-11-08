<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('reactions.table_name', 'reactions'), function (Blueprint $table) {
            $table->id();
            $table->morphs('reactor');
            $table->string('reaction_type');
            $table->morphs('reactable');
            $table->timestamps();

            $table->unique(['reactor_type', 'reactor_id', 'reactable_type', 'reactable_id'], 'unique_reaction');
            $table->index('reaction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('reactions.table_name', 'reactions'));
    }
};
