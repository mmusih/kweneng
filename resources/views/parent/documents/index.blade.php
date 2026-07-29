<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-white rounded-lg shadow-md border border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800">School Documents</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Download timetables, booklists, and other school resources
                    </p>
                </div>
                <a href="{{ route('parent.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @forelse ($documents as $category => $docs)
                @php
                    $color = \App\Models\SchoolDocument::getCategoryColor($category);
                    $icon = \App\Models\SchoolDocument::getCategoryIcon($category);
                    $label = \App\Models\SchoolDocument::getCategoryLabel($category);
                @endphp

                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    {{-- Category header --}}
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div
                            class="h-9 w-9 rounded-lg flex items-center justify-center text-lg
                            @if ($color === 'blue') bg-blue-50
                            @elseif($color === 'purple') bg-purple-50
                            @elseif($color === 'green') bg-green-50
                            @elseif($color === 'orange') bg-orange-50
                            @else bg-gray-50 @endif">
                            {{ $icon }}
                        </div>
                        <h3 class="text-base font-semibold text-gray-800">{{ $label }}</h3>
                    </div>

                    {{-- Documents list --}}
                    <ul class="divide-y divide-gray-100">
                        @foreach ($docs as $doc)
                            <li class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    <svg class="h-8 w-8 flex-shrink-0 text-gray-300" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                                    </svg>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $doc->title }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $doc->original_filename }}
                                            @if ($doc->academicYear)
                                                &middot; {{ $doc->academicYear->year_name }}
                                            @endif
                                            &middot; Added {{ $doc->created_at->format('M j, Y') }}
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ route('parent.documents.download', $doc) }}"
                                    class="ml-4 flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition"
                                    style="background:#2C3E6B;" onmouseover="this.style.background='#1e2d4f'"
                                    onmouseout="this.style.background='#2C3E6B'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

            @empty
                <div class="bg-white shadow-sm rounded-lg p-16 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-3 text-sm text-gray-500">No documents have been uploaded yet.</p>
                    <p class="text-xs text-gray-400 mt-1">Check back later or contact the school office.</p>
                </div>
            @endforelse

        </div>
    </div>
</x-app-layout>
