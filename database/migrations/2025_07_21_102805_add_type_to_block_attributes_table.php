<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::table('block_attributes', function (Blueprint $table) {
        $table->string('type')->nullable()->after('name');
    });
}

public function down()
{
    Schema::table('block_attributes', function (Blueprint $table) {
        $table->dropColumn('type');
    });
}

};
