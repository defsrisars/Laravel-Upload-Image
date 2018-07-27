<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sliders', function (Blueprint $table) {

            // === 欄位 ===
            // [PK] 資料識別碼 (laravel-ulid)
            $table->char('id', 26)
                ->comment('[PK] 資料識別碼');

            $table->string('title')
                ->comment('輪播圖標題');

            $table->string('image_link_desktop')
                ->comment('輪播圖大圖連結(for 電腦)');

            $table->string('image_link_mobile')
                ->comment('輪播圖小圖連結(for 手機)');

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
        Schema::dropIfExists('sliders');
    }
}
