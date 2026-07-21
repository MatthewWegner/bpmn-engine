<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('workflow_instances')) {
            Schema::create('workflow_instances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_version_id')->constrained()->cascadeOnDelete();
                $table->string('status'); // running, suspended, halted, completed, failed
                $table->string('durable_workflow_id');
                $table->string('current_node_id');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('workflow_instances');
    }
};