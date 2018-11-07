<?php

use SuperV\Platform\Domains\Database\Schema\Blueprint;
use SuperV\Platform\Domains\Database\Schema\Schema;
use SuperV\Platform\Domains\Database\Migrations\Migration;
use SuperV\Platform\Domains\Resource\ResourceBlueprint;

class CreateAddonsTable extends Migration
{
    public function up()
    {
        Schema::create('sv_addons', function (Blueprint $table, ResourceBlueprint $resource) {
            $table->increments('id');
            $table->string('name');
            $table->string('vendor');
            $table->string('slug');
            $table->string('path');
            $table->string('namespace');
            $table->string('type');
            $table->boolean('enabled');
            $table->timestamps();

            $resource->label('SuperV Addons');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sv_addons');
    }
}