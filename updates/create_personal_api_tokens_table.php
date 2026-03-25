<?php

namespace Golem15\Apparatus\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

class CreatePersonalApiTokensTable extends Migration
{
    public function up()
    {
        Schema::create('golem15_apparatus_personal_api_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('backend_user_id')->index();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('golem15_apparatus_personal_api_tokens');
    }
}
