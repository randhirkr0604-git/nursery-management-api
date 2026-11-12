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
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('child_id');

            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();

            $table->timestamps();

            $table->foreign(['tenant_id', 'room_id'])
            ->references(['tenant_id', 'id'])
            ->on('rooms')
            ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'child_id'])
            ->references(['tenant_id', 'id'])
            ->on('children')
            ->cascadeOnDelete();


            $table->index(
                ['tenant_id', 'room_id', 'checked_out_at'],
                'check_ins_room_occupants_index'
            );

            $table->index(
                ['tenant_id', 'child_id', 'checked_out_at'],
                'check_ins_child_active_index'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
