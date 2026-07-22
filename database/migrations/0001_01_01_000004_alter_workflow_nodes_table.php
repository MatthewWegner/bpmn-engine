<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('workflow_nodes', function (Blueprint $table) {
            // The ID of the task this event sits on (e.g., 'Task_Invoice')
            $table->string('attached_to_element_id')->nullable()->after('implementation');
            
            // The specific sub-type (e.g., 'error', 'timer', 'message')
            $table->string('event_definition_type')->nullable()->after('attached_to_element_id');
        });
    }

    public function down()
    {
        Schema::table('workflow_nodes', function (Blueprint $table) {
            $table->dropColumn(['attached_to_element_id', 'event_definition_type']);
        });
    }
};