<div class="overflow-x-auto border-b border-gray-200">
    <nav class="flex min-w-max gap-1" aria-label="Administration sections">
        @can('manage_users')<a href="{{ route('admin.users.index') }}" class="border-b-2 px-3 py-3 text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}">Users</a>@endcan
        @can('manage_staff_profiles')<a href="{{ route('admin.staff.index') }}" class="border-b-2 px-3 py-3 text-sm font-medium {{ request()->routeIs('admin.staff.*') ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}">Staff Profiles</a>@endcan
        @can('manage_skills')<a href="{{ route('admin.skills.index') }}" class="border-b-2 px-3 py-3 text-sm font-medium {{ request()->routeIs('admin.skills.*') ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}">Skills</a>@endcan
        @can('manage_roles')<a href="{{ route('admin.roles.index') }}" class="border-b-2 px-3 py-3 text-sm font-medium {{ request()->routeIs('admin.roles.*') ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}">Roles</a>@endcan
        @can('manage_permissions')<a href="{{ route('admin.permissions.index') }}" class="border-b-2 px-3 py-3 text-sm font-medium {{ request()->routeIs('admin.permissions.*') ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}">Permissions</a>@endcan
    </nav>
</div>
