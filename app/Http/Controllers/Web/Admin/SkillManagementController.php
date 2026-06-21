<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Skills\StoreSkillRequest;
use App\Http\Requests\Api\V1\Skills\UpdateSkillRequest;
use App\Models\Skill;
use App\Services\AdminWebService;
use App\Services\SkillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SkillManagementController extends Controller
{
    public function __construct(private readonly SkillService $skills, private readonly AdminWebService $web) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Skill::class);
        $filters = $request->only(['search', 'status', 'sort', 'direction', 'per_page']);

        return view('admin.skills.index', ['skills' => $this->skills->paginate($filters), 'filters' => $filters]);
    }

    public function create(): View
    {
        Gate::authorize('create', Skill::class);

        return view('admin.skills.form', ['skill' => null]);
    }

    public function store(StoreSkillRequest $request): RedirectResponse
    {
        Gate::authorize('create', Skill::class);
        $skill = $this->skills->create($request->validated());

        return redirect()->route('admin.skills.show', $skill)->with('success', 'Skill created successfully.');
    }

    public function show(Skill $skill): View
    {
        Gate::authorize('view', $skill);

        return view('admin.skills.show', ['skill' => $skill->load('staffProfiles.user')->loadCount('staffProfiles')]);
    }

    public function edit(Skill $skill): View
    {
        Gate::authorize('update', $skill);

        return view('admin.skills.form', ['skill' => $skill]);
    }

    public function update(UpdateSkillRequest $request, Skill $skill): RedirectResponse
    {
        Gate::authorize('update', $skill);
        $this->skills->update($skill, $request->validated());

        return redirect()->route('admin.skills.show', $skill)->with('success', 'Skill updated successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Skill::class);
        $records = $this->skills->records($request->only(['search', 'status', 'sort', 'direction']));

        return $this->web->csv('skills', ['Name', 'Description', 'Staff Count', 'Active'], $records, fn (Skill $skill): array => [
            $skill->name, $skill->description, $skill->staff_profiles_count, $skill->is_active ? 'Yes' : 'No',
        ]);
    }
}
