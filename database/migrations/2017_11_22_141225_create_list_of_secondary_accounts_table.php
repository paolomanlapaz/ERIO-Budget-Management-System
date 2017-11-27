<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateListOfSecondaryAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('list_of_secondary_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("account_id");
            $table->integer("list_id");
            $table->foreign("account_id")->references("id")->on("secondary_accounts");
            $table->foreign("list_id")->references("id")->on("list_of_primary_accounts");
            $table->decimal("amount");
            $table->string('status')->default("Open");
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
        Schema::dropIfExists('list_of_secondary_accounts');
    }
}
