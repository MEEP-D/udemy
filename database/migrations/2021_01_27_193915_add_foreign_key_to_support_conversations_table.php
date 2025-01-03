<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToSupportConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->foreign('support_id')
                ->references('id')->on('supports')
                ->onDelete('cascade')
                ->name('support_conversations_support_id_foreign');

            $table->foreign('sender_id')
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->name('support_conversations_sender_id_foreign');
        });
    }

}
