<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Announcements</h2>
            <div class="flex gap-2">
                <a href="{{ route('parent.events.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    📅 Events
                </a>
                <a href="{{ route('parent.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    ← Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Urgent / Important --}}
            @if ($urgentAnnouncements->count() > 0)
                <div class="rounded-xl border border-red-200 bg-gradient-to-r from-red-50 to-orange-50 p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🚨</span>
                        <p class="text-sm font-bold text-red-800 uppercase tracking-wide">Important Notices</p>
                    </div>
                    @foreach ($urgentAnnouncements as $announcement)
                        <div
                            class="bg-white rounded-lg p-4 border-l-4 border-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-500">
                            <div class="flex justify-between items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="text-base">{!! \App\Models\Announcement::getTypeIcon($announcement->type) !!}</span>
                                        <p class="text-sm font-semibold text-gray-900">{{ $announcement->title }}</p>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            bg-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-100
                                            text-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-800">
                                            {{ ucfirst($announcement->type) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ $announcement->message }}</p>
                                    <p class="text-xs text-gray-400 mt-2">
                                        by {{ $announcement->author->name ?? 'Admin' }} ·
                                        {{ $announcement->publish_at?->format('M j, Y') ?? $announcement->created_at->format('M j, Y') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- General Announcements --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-lg">📢</span>
                    <p class="text-sm font-semibold text-gray-700">School Announcements</p>
                    <span class="ml-auto text-xs text-gray-400">{{ $announcements->count() }} total</span>
                </div>

                @forelse($announcements as $announcement)
                    <div class="flex items-start gap-4 py-4 border-b border-gray-100 last:border-0">
                        <div class="flex-shrink-0 mt-0.5">
                            <span class="text-xl">{!! \App\Models\Announcement::getTypeIcon($announcement->type) !!}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <p class="text-sm font-medium text-gray-900">{{ $announcement->title }}</p>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    bg-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-100
                                    text-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-800">
                                    {{ ucfirst($announcement->type) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">{{ $announcement->message }}</p>
                            <p class="text-xs text-gray-400 mt-2">
                                by {{ $announcement->author->name ?? 'Admin' }} ·
                                {{ $announcement->publish_at?->format('M j, Y g:i A') ?? $announcement->created_at->format('M j, Y g:i A') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <p class="text-gray-400 text-sm">No announcements at this time.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
