<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    // Reference tables
    Schema::create('departments', function (Blueprint $table) {
        $table->integer('dep_id')->primary();
        $table->string('name');
        $table->string('sort')->nullable();
        $table->string('name_en')->nullable();
    });

    Schema::create('sub_departments', function (Blueprint $table) {
        $table->increments('sub_dep_id');
        $table->integer('dep_id');
        $table->string('name');
        $table->string('name_en')->nullable();
    });

    Schema::create('degrees', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->date('date_add');
    });

    Schema::create('journal_types', function (Blueprint $table) {
        $table->increments('journal_type_id');
        $table->string('name')->nullable();
        $table->integer('orders');
        $table->datetime('date_add')->nullable();
    });

    Schema::create('research_types', function (Blueprint $table) {
        $table->increments('research_type_id');
        $table->string('name')->nullable();
        $table->integer('orders');
        $table->datetime('date_add')->nullable();
    });

    Schema::create('research_pmu_types', function (Blueprint $table) {
        $table->increments('research_PMU_type_id');
        $table->string('name')->nullable();
        $table->datetime('date_add')->nullable();
    });

    Schema::create('research_levels', function (Blueprint $table) {
        $table->increments('research_level_id');
        $table->string('name')->nullable();
        $table->datetime('date_add')->nullable();
    });

    Schema::create('position_types', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->datetime('date_add')->nullable();
    });

    Schema::create('positions', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('position_type_id');
        $table->datetime('date_add')->nullable();
    });

    Schema::create('has_experts', function (Blueprint $table) {
        $table->increments('expert_id');
        $table->string('name')->nullable();
        $table->datetime('date_add')->nullable();
    });

    // ตารางผลงาน
    Schema::create('educations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->integer('degree');
        $table->string('course', 200);
        $table->string('university', 500);
        $table->string('year', 4);
        $table->timestamp('dateAdd')->useCurrent();
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_interests', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->text('name');
        $table->datetime('dateAdd');
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_researches', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->text('name');
        $table->string('year', 4);
        $table->integer('research_type_id');
        $table->integer('research_PMU_type_id')->default(0);
        $table->integer('research_level_id')->default(0);
        $table->datetime('dateAdd');
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_journals', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->text('name');
        $table->string('year', 4);
        $table->integer('journal_type_id');
        $table->datetime('dateAdd');
        $table->text('url')->nullable();
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_books', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('name', 500);
        $table->string('year', 4);
        $table->datetime('dateAdd');
        $table->string('img')->nullable();
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_patents', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->text('name');
        $table->string('year', 4);
        $table->text('link');
        $table->datetime('dateAdd');
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_awards', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->text('name');
        $table->string('year', 4);
        $table->datetime('dateAdd');
        $table->string('img')->nullable();
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_academics', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->text('name');
        $table->string('year', 4);
        $table->string('picture', 200)->nullable();
        $table->text('link')->nullable();
        $table->datetime('dateAdd');
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_trainings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->text('name');
        $table->string('year', 4);
        $table->datetime('dateAdd');
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_lecturers', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->text('name');
        $table->string('year', 4);
        $table->datetime('dateAdd');
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_proceedings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('name', 500);
        $table->string('year', 4);
        $table->datetime('dateAdd');
        $table->text('url')->nullable();
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('has_hsps', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->text('name');
        $table->string('year', 4);
        $table->text('link')->nullable();
        $table->datetime('dateAdd');
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('workexes', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('year_start', 4)->default('');
        $table->string('year_end', 10)->default('');
        $table->string('position', 100)->nullable();
        $table->string('workplace', 100)->nullable();
        $table->datetime('dateAdd')->nullable();
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('boardexes', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('year_start', 4)->default('');
        $table->string('year_end', 10)->default('');
        $table->string('position', 100)->nullable();
        $table->string('workplace', 100)->nullable();
        $table->datetime('dateAdd')->nullable();
        $table->foreign('user_id')->references('id')->on('users');
    });

    Schema::create('users_expert', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->integer('expert_id');
        $table->foreign('user_id')->references('id')->on('users');
    });
}

public function down(): void
    {
    Schema::dropIfExists('users_expert');
    Schema::dropIfExists('boardexes');
    Schema::dropIfExists('workexes');
    Schema::dropIfExists('has_hsps');
    Schema::dropIfExists('has_proceedings');
    Schema::dropIfExists('has_lecturers');
    Schema::dropIfExists('has_trainings');
    Schema::dropIfExists('has_academics');
    Schema::dropIfExists('has_awards');
    Schema::dropIfExists('has_patents');
    Schema::dropIfExists('has_books');
    Schema::dropIfExists('has_journals');
    Schema::dropIfExists('has_researches');
    Schema::dropIfExists('has_interests');
    Schema::dropIfExists('educations');
    Schema::dropIfExists('has_experts');
    Schema::dropIfExists('positions');
    Schema::dropIfExists('position_types');
    Schema::dropIfExists('research_levels');
    Schema::dropIfExists('research_pmu_types');
    Schema::dropIfExists('research_types');
    Schema::dropIfExists('journal_types');
    Schema::dropIfExists('degrees');
    Schema::dropIfExists('sub_departments');
    Schema::dropIfExists('departments');
    }
};