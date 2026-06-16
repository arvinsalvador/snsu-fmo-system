<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $skills = [
        'Carpentry',
        'Plumbing',
        'Electrical',
        'Masonry',
        'Painting',
        'Air Conditioning',
        'Welding',
        'Civil Works',
        'Safety Equipment',
        'General Maintenance',
    ];

    public function run(): void
    {
        foreach ($this->skills as $skill) {
            Skill::query()->firstOrCreate(
                ['name' => $skill],
                [
                    'description' => "{$skill} facilities maintenance skill.",
                    'is_active' => true,
                ],
            );
        }
    }
}
