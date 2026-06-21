<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminWebService
{
    public function roles(): Collection
    {
        return Role::query()->where('guard_name', 'web')->orderBy('name')->get();
    }

    public function permissions(): Collection
    {
        return Permission::query()->where('guard_name', 'web')->orderBy('name')->get();
    }

    public function skills(): Collection
    {
        return Skill::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function usersWithoutStaffProfile(?User $selected = null): Collection
    {
        return User::query()
            ->where(function ($query) use ($selected): void {
                $query->whereDoesntHave('staffProfile');
                if ($selected) {
                    $query->orWhereKey($selected->id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function csv(string $filename, array $headers, iterable $records, callable $row): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $records, $row): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($records as $record) {
                fputcsv($output, $row($record));
            }
            fclose($output);
        }, $filename.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
