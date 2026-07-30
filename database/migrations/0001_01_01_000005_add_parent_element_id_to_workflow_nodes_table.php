<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('workflow_nodes', function (Blueprint $table) {
            // Track elements that belong to an inline sub-process scope
            $table->string('parent_element_id')->nullable()->after('workflow_version_id');
        });
    }

    public function down()
    {
        Schema::table('workflow_nodes', function (Blueprint $table) {
            $table->dropColumn('parent_element_id');
        });
    }
};