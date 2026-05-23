<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Announcement Details
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.announcements.edit', $announcement) }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Edit
                </a>
                <a href="{{ route('admin.announcements.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Back to Announcements
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ $announcement->title }}</h1>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-100 text-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-800">
                                        {{ ucfirst($announcement->type) }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ \App\Models\Announcement::getAudienceLabel($announcement->audience) }}
                                    </span>
                                    @if ($announcement->is_published)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Published
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Draft
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Created by</div>
                                <div class="font-medium">{{ $announcement->author->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ $announcement->created_at->format('M j, Y') }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if ($announcement->publish_at || $announcement->expires_at)
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">Schedule</h3>
                                    <div class="mt-2 text-sm text-gray-500">
                                        @if ($announcement->publish_at)
                                            <div><strong>Publish:</strong>
                                                {{ $announcement->publish_at->format('M j, Y g:i A') }}</div>
                                        @endif
                                        @if ($announcement->expires_at)
                                            <div><strong>Expires:</strong>
                                                {{ $announcement->expires_at->format('M j, Y g:i A') }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($announcement->classModel || $announcement->subject)
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">Target Audience</h3>
                                    <div class="mt-2 text-sm text-gray-500">
                                        @if ($announcement->classModel)
                                            <div><strong>Class:</strong> {{ $announcement->classModel->name }}</div>
                                        @endif
                                        @if ($announcement->subject)
                                            <div><strong>Subject:</strong> {{ $announcement->subject->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="mt-6">
                            <h3 class="text-sm font-medium text-gray-900">Message</h3>
                            <div class="mt-2 text-gray-600 prose max-w-none">
                                {!! nl2br(e($announcement->message)) !!}
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                                onclick="return confirm('Are you sure you want to delete this announcement?')">
                                Delete Announcement
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
