@if (isset($links) && count($links) > 0)
    <div class="flex items-center text-sm text-gray-500 mt-2 dark:text-brand-400">
        @foreach ($links as $link)
            @if (!$loop->last)
                <a href="{{ $link['url'] }}" class="hover:text-gray-700 hover:underline dark:hover:text-white">
                    {{ $link['label'] }}
                </a>
                <span class="mx-2 text-gray-400 dark:text-brand-400">›</span>
            @else
                <span class="text-gray-700 font-medium dark:text-brand-200">
                    {{ $link['label'] }}
                </span>
            @endif
        @endforeach
    </div>
@endif
