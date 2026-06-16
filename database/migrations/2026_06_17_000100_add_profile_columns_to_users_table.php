<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('employee_no')->nullable()->unique()->after('uuid');
            $table->string('student_no')->nullable()->unique()->after('employee_no');
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('suffix')->nullable()->after('last_name');
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('mobile_number')->nullable()->after('username');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'employee_no',
                'student_no',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'username',
                'mobile_number',
            ]);
        });
    }
};
