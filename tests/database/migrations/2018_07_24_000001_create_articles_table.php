<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {

            // === 欄位 ===
            // [PK] 資料識別碼 (laravel-ulid)
            $table->char('id', 26)
                ->comment('[PK] 資料識別碼');

            // 標題
            $table->text('title')
                ->nullable()
                ->comment('標題');

            // 內容
            $table->text('content')
                ->nullable()
                ->comment('內容');

            // 建立時間
            $table->datetime('created_at')
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->comment('最後更新');

            // === 索引 ===
            // 指定主鍵索引
            $table->primary(['id']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
