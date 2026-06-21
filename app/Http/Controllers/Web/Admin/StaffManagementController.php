<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StaffProfiles\StoreStaffProfileRequest;
use App\Http\Requests\Web\SyncAdminStaffSkillsRequest;
use App\Http\Requests\Web\UpdateAdminStaffProfileRequest;
use App\Models\StaffProfile;
use App\Services\AdminWebService;
use App\Services\StaffProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffManagementController extends Controller
{
    public function __construct(private readonly StaffProfileService $staff, private readonly AdminWebService $web) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StaffProfile::class);
        $filters = $request->only(['search', 'skill', 'availability', 'status', 'sort', 'direction', 'per_page']);

        return view('admin.staff.index', ['profiles' => $this->staff->paginate($filters), 'skills' => $this->web->skills(), 'filters' => $filters]);
    }

    public function create(): View
    {
        Gate::authorize('create', StaffProfile::class);

        return view('admin.staff.form', ['profile' => null, 'users' => $this->web->usersWithoutStaffProfile(), 'skills' => $this->web->skills()]);
    }

    public function store(StoreStaffProfileRequest $request): RedirectResponse
    {
        Gate::authorize('create', StaffProfile::class);
        $profile = $this->staff->create($request->validated());

        return redirect()->route('admin.staff.show', $profile)->with('success', 'Staff profile created successfully.');
    }

    public function show(StaffProfile $staffProfile): View
    {
        Gate::authorize('view', $staffProfile);

        return view('admin.staff.show', ['profile' => $staffProfile->load('user.roles', 'skills')]);
    }

    public function edit(StaffProfile $staffProfile): View
    {
        Gate::authorize('update', $staffProfile);

        return view('admin.staff.form', [
            'profile' => $staffProfile->load('user', 'skills'),
            'users' => $this->web->usersWithoutStaffProfile($staffProfile->user),
            'skills' => $this->web->skills(),
        ]);
    }

    public function update(UpdateAdminStaffProfileRequest $request, StaffProfile $staffProfile): RedirectResponse
    {
        Gate::authorize('update', $staffProfile);
        $this->staff->update($staffProfile, $request->validated());

        return redirect()->route('admin.staff.show', $staffProfile)->with('success', 'Staff profile updated successfully.');
    }

    public function editSkills(StaffProfile $staffProfile): View
    {
        Gate::authorize('update', $staffProfile);

        return view('admin.staff.skills', ['profile' => $staffProfile->load('user', 'skills'), 'skills' => $this->web->skills()]);
    }

    public function syncSkills(SyncAdminStaffSkillsRequest $request, StaffProfile $staffProfile): RedirectResponse
    {
        Gate::authorize('update', $staffProfile);
        $this->staff->syncSkills($staffProfile, $request->validated()['skill_ids']);

        return redirect()->route('admin.staff.show', $staffProfile)->with('success', 'Staff skills updated successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', StaffProfile::class);
        $records = $this->staff->records($request->only(['search', 'skill', 'availability', 'status', 'sort', 'direction']));

        return $this->web->csv('staff-profiles', ['Employee Code', 'Name', 'Position', 'Designation', 'Employment Status', 'Availability', 'Skills'], $records, fn (StaffProfile $profile): array => [
            $profile->employee_code, $profile->user?->name, $profile->position, $profile->designation, $profile->employment_status, $profile->availability_status, $profile->skills->pluck('name')->implode('; '),
        ]);
    }
}
