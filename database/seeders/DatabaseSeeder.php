<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);
        $this->call(SkillSeeder::class);
        $this->call(MasterDataSeeder::class);
        $this->call(ReportTemplateSeeder::class);

        $user = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'employee_no' => 'EMP-0001',
                'student_no' => null,
                'first_name' => 'Test',
                'middle_name' => null,
                'last_name' => 'User',
                'suffix' => null,
                'name' => 'Test User',
                'username' => 'test.user',
                'mobile_number' => null,
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );

        $user->assignRole('Super Admin');
    }
}
