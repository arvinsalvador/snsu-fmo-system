<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-gray-900">Create work order</h1></x-slot>
    <form method="POST" action="{{ route('work-orders.store') }}" class="max-w-4xl space-y-7">
        @csrf
        <section class="grid gap-5 border-b border-gray-200 pb-7 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-700" for="title">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" required class="mt-1 w-full rounded-md border-gray-300">
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-700" for="description">Description</label>
                <textarea id="description" name="description" rows="5" required class="mt-1 w-full rounded-md border-gray-300">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700" for="category_id">Category</label>
                <select id="category_id" name="category_id" required class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">Select category</option>
                    @foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700" for="priority_id">Priority</label>
                <select id="priority_id" name="priority_id" required class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">Select priority</option>
                    @foreach ($priorities as $priority)<option value="{{ $priority->id }}" @selected(old('priority_id') == $priority->id)>{{ $priority->name }}</option>@endforeach
                </select>
            </div>
        </section>

        <section class="grid gap-5 border-b border-gray-200 pb-7 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-gray-700" for="department_id">Department or office</label>
                <select id="department_id" name="department_id" class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">Select department</option>
                    @foreach ($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700" for="building_id">Building</label>
                <select id="building_id" name="building_id" required class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">Select building</option>
                    @foreach ($buildings as $building)<option value="{{ $building->id }}" @selected(old('building_id') == $building->id)>{{ $building->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700" for="floor_id">Floor</label>
                <select id="floor_id" name="floor_id" class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">Select floor</option>
                    @foreach ($buildings as $building) @foreach ($building->floors as $floor)<option value="{{ $floor->id }}" @selected(old('floor_id') == $floor->id)>{{ $building->name }} - {{ $floor->floor_name }}</option>@endforeach @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700" for="room_id">Room</label>
                <select id="room_id" name="room_id" class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">Select room</option>
                    @foreach ($buildings as $building) @foreach ($building->floors as $floor) @foreach ($floor->rooms as $room)<option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $building->name }} - {{ $room->room_name }}</option>@endforeach @endforeach @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700" for="preferred_staff_id">Preferred staff (optional)</label>
                <select id="preferred_staff_id" name="preferred_staff_id" class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">No preference</option>
                    @foreach ($staff as $profile)<option value="{{ $profile->id }}" @selected(old('preferred_staff_id') == $profile->id)>{{ $profile->user?->name }} - {{ $profile->designation }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700" for="target_completion_date">Target date (optional)</label>
                <input id="target_completion_date" type="date" name="target_completion_date" value="{{ old('target_completion_date') }}" class="mt-1 w-full rounded-md border-gray-300">
            </div>
        </section>
        <div class="flex justify-end gap-3">
            <a href="{{ route('my-requests') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</a>
            <button class="rounded-md bg-emerald-700 px-5 py-2 text-sm font-semibold text-white">Submit request</button>
        </div>
    </form>
</x-app-layout>
