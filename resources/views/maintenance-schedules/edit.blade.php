@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Edit Maintenance Schedule</h1>
        <p class="mt-1 text-sm text-zinc-600">Update frequency, due dates, and schedule status.</p>
    </div>

    <form method="POST" action="{{ route('maintenance-schedules.update', $schedule) }}" class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
        @method('PUT')
        @include('maintenance-schedules._form')
    </form>
@endsection
