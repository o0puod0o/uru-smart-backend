<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ข้อมูลติดต่อ
            $table->string('phone_work')->nullable()->after('email');
            $table->string('phone_mobile')->nullable()->after('phone_work');
            $table->string('line_id')->nullable()->after('phone_mobile');
            $table->string('facebook')->nullable()->after('line_id');
            $table->string('website')->nullable()->after('facebook');

            // แนะนำตัว
            $table->text('bio')->nullable()->after('website');

            // รูปโปรไฟล์เพิ่มเติม (อัพโหลดเอง)
            $table->string('profile_picture')->nullable()->after('bio');

            // ที่อยู่
            $table->string('address')->nullable()->after('profile_picture');
            $table->string('moo')->nullable()->after('address');
            $table->string('road')->nullable()->after('moo');
            $table->string('tambon')->nullable()->after('road');
            $table->string('amphoe')->nullable()->after('tambon');
            $table->string('province')->nullable()->after('amphoe');
            $table->string('zipcode', 10)->nullable()->after('province');

            // ตำแหน่ง
            $table->string('position')->nullable()->after('zipcode');
            $table->string('branch')->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone_work', 'phone_mobile', 'line_id',
                'facebook', 'website', 'bio', 'profile_picture',
                'address', 'moo', 'road', 'tambon',
                'amphoe', 'province', 'zipcode',
                'position', 'branch',
            ]);
        });
    }
};