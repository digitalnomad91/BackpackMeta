<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMetaOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meta_options', function (Blueprint $table) {
          $table->increments('id');
          $table->string('key', 255)->unique();
          $table->string('display', 255)->nullable(); // publicly displayed title
          $table->string('helper', 255)->nullable(); // an optional helper text
          $table->string('type');
          $table->text('model')->nullable();
          $table->text('extras')->nullable();
          $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('meta_options');
    }
}
