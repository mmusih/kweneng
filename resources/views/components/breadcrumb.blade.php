@if (isset($links) && count($links) > 0)
    <div class="flex items-center text-sm text-gray-500 mt-2">
        @foreach ($links as $link)
            @if (!$loop->last)
                <a href="{{ $link['url'] }}" class="hover:text-gray-700 hover:underline">
                    {{ $link['label'] }}
                </a>
                <span class="mx-2 text-gray-400">›</span>
            @else
                <span class="text-gray-700 font-medium">
                    {{ $link['label'] }}
                </span>
            @endif
        @endforeach
    </div>
@endif
