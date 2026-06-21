<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-medium text-emerald-700">Administration / Staff / Skills</p><h1 class="text-2xl font-semibold text-gray-900">{{ $profile->user?->name }}</h1></div></x-slot>
    <div class="mx-auto max-w-3xl space-y-5">@include('admin.partials.tabs')
        <form method="POST" action="{{ route('admin.staff.skills.update', $profile) }}" class="space-y-5 rounded-md border border-gray-200 bg-white p-6">@csrf @method('PUT')
            <div><h2 class="font-semibold text-gray-900">Staff skill assignment</h2><p class="mt-1 text-sm text-gray-500">Select the maintenance capabilities used for assignment recommendations.</p></div>
            <input type="hidden" name="skills_present" value="1"><div class="grid gap-2 sm:grid-cols-2">@forelse($skills as $skill)<label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-3 text-sm"><input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}" @checked(in_array($skill->id, old('skill_ids', $profile->skills->pluck('id')->all()), true)) class="rounded border-gray-300 text-emerald-700">{{ $skill->name }}</label>@empty<p class="text-sm text-gray-500">No active skills are available.</p>@endforelse</div>
            @error('skill_ids')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <div class="flex justify-end gap-3 border-t border-gray-200 pt-5"><a href="{{ route('admin.staff.show', $profile) }}" class="px-3 py-2 text-sm font-semibold text-gray-600">Cancel</a><button class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Save skills</button></div>
        </form>
    </div>
</x-app-layout>
