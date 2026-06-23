<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // SSO Identity
            $table->unsignedBigInteger('sso_id')->unique();
            $table->string('code')->unique();
            $table->string('username')->unique();
            $table->string('citizen_id')->nullable();
            $table->string('passport_id')->nullable();

            // ประเภท/วุฒิ/สถานะ
            $table->string('type')->default('TEACHER');
            $table->string('degree')->nullable();
            $table->string('status')->default('ACTIVE');

            // ชื่อไทย
            $table->string('prefix_th')->nullable();
            $table->string('first_name_th');
            $table->string('last_name_th');

            // ชื่ออังกฤษ
            $table->string('prefix_en')->nullable();
            $table->string('first_name_en');
            $table->string('last_name_en');
            $table->string('nickname')->nullable();

            // ข้อมูลส่วนตัว
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('email')->nullable();
            $table->text('picture')->nullable();

            // คณะ/ภาค
            $table->unsignedInteger('faculty_id')->nullable();
            $table->string('faculty_name_th')->nullable();
            $table->string('faculty_name_en')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->string('department_name_th')->nullable();
            $table->string('department_name_en')->nullable();

            // อื่นๆ
            $table->unsignedInteger('campus_id')->nullable();
            $table->unsignedInteger('curriculum_id')->nullable();
            $table->unsignedSmallInteger('study_year')->default(0);
            $table->string('custom1')->nullable();
            $table->string('custom2')->nullable();
            $table->string('custom3')->nullable();

            // Sync tracking
            $table->timestamp('sso_last_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};