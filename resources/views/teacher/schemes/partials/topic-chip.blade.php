<div class="topic-chip inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white border border-gray-200 shadow-sm text-sm text-gray-800 hover:bg-gray-50" data-item-id="{{ $item->id }}">
    <span class="text-gray-400">⋮⋮</span>
    <span class="max-w-[260px] truncate {{ $item->status === 'completed' ? 'line-through text-gray-400' : '' }}">{{ $item->title }}</span>
    @if ($item->subtopics->count())
        <span class="text-xs text-gray-400">{{ $item->subtopics->count() }}</span>
    @endif
    <form method="POST" action="{{ route('teacher.schemes.topics.destroy', [$scheme, $item]) }}" onsubmit="return confirm('Remove this topic from the scheme?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-gray-400 hover:text-red-600">×</button>
    </form>
</div>
