<div class="overflow-x-auto border-b border-gray-200">
    <nav class="flex min-w-max gap-1" aria-label="Master data sections">
        @foreach ($modules as $key => $item)
            <a href="{{ route("admin.master-data.{$key}.index") }}" class="border-b-2 px-3 py-3 text-sm font-medium {{ $moduleKey === $key ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</div>
