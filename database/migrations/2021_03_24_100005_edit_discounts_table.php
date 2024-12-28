<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \Illuminate\Support\Facades\DB;

class EditDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
        {
            // // Xóa các cột nếu có
            // Schema::table('discounts', function (Blueprint $table) {
            //     // Kiểm tra và xóa cột 'name' nếu tồn tại
            //     if (Schema::hasColumn('discounts', 'name')) {
            //         DB::statement("ALTER TABLE `discounts` DROP COLUMN `name`");
            //     }

            //     // Kiểm tra và xóa cột 'started_at' nếu tồn tại
            //     if (Schema::hasColumn('discounts', 'started_at')) {
            //         DB::statement("ALTER TABLE `discounts` DROP COLUMN `started_at`");
            //     }

            //     // Sửa cột 'created_at' nếu cột đã tồn tại, không thêm cột mới
            //     if (Schema::hasColumn('discounts', 'created_at')) {
            //         // Sửa kiểu dữ liệu của cột 'created_at'
            //         $table->integer('created_at')->unsigned()->change(); // Sử dụng change() thay vì add
            //     } else {
            //         // Nếu cột không tồn tại, thêm mới
            //         $table->integer('created_at')->unsigned()->after('expired_at');
            //     }
            // });

            // // Thêm các cột mới vào bảng 'discounts'
            // Schema::table('discounts', function (Blueprint $table) {
            //     $table->string('title')->after('creator_id');
            //     $table->string('code', 64)->after('title')->unique();
            //     $table->enum('type', ['all_users', 'special_users'])->after('code');
            // });

            // // Xóa cột 'count' trong bảng 'discount_users' nếu có
            // Schema::table('discount_users', function (Blueprint $table) {
            //     if (Schema::hasColumn('discount_users', 'count')) {
            //         DB::statement("ALTER TABLE `discount_users` DROP COLUMN `count`");
            //     }
            // });
        }

}
