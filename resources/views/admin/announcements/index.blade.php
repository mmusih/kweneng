<x-app-layout>
    @php($announcementRoutePrefix = $announcementRoutePrefix ?? (request()->routeIs('office.*') ? 'office' : 'admin'))
    <x-slot name="header">
        <div class="mt-16 p-5 rounded-2xl kw-page-header flex justify-between items-center gap-4">
            <h2 class="font-semibold text-xl text-white leading-tight">
                Notices
            </h2>
            <a href="{{ route($announcementRoutePrefix . '.announcements.create') }}"
                class="inline-flex items-center px-4 py-2 bg-white text-slate-800 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-slate-100">
                Create New Notice
            </a>
        </div>
    </x-slot>

    <div class="py-12 kw-soft-section min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white kw-panel overflow-hidden">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Manage Announcements</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Create targeted announcements and track parent read/acknowledgement status.
                        </p>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-4">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Audience</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published</th>
                                    <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($announcements as $announcement)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $announcement->title }}</div>
                                            @if ($announcement->message)
                                                <div class="text-sm text-gray-500 truncate max-w-xs">
                                                    {{ Str::limit($announcement->message, 50) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php($color = \App\Models\Announcement::getTypeColor($announcement->type))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                                {{ ucfirst($announcement->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ \App\Models\Announcement::getAudienceLabel($announcement->audience) }}

                                            @if ($announcement->targets && $announcement->targets->isNotEmpty())
                                                <div class="text-xs text-gray-400 mt-1">
                                                    @foreach ($announcement->targets->take(3) as $target)
                                                        @if ($target->target_type === 'form_level')
                                                            <span class="inline-block mr-1">Form: {{ $target->target_value }}</span>
                                                        @elseif ($target->target_type === 'class_group')
                                                            <span class="inline-block mr-1">Class ID: {{ $target->target_id }}</span>
                                                        @elseif ($target->target_type === 'parent')
                                                            <span class="inline-block mr-1">Parent ID: {{ $target->target_id }}</span>
                                                        @endif
                                                    @endforeach

                                                    @if ($announcement->targets->count() > 3)
                                                        <span>+{{ $announcement->targets->count() - 3 }} more</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($announcement->is_published)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Published
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Draft
                                                </span>
                                            @endif

                                            @if ($announcement->push_sent_at)
                                                <div class="text-xs text-gray-400 mt-1">Push sent</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if ($announcement->publish_at)
                                                {{ $announcement->publish_at->format('M j, Y H:i') }}
                                            @else
                                                {{ $announcement->created_at->format('M j, Y H:i') }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route($announcementRoutePrefix . '.announcements.show', $announcement) }}"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>

                                            <a href="{{ route($announcementRoutePrefix . '.announcements.tracking', $announcement) }}"
                                                class="text-green-600 hover:text-green-900 mr-3">Tracking</a>

                                            <a href="{{ route($announcementRoutePrefix . '.announcements.edit', $announcement) }}"
                                                class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>

                                            <form action="{{ route($announcementRoutePrefix . '.announcements.destroy', $announcement) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Are you sure you want to delete this announcement?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No announcements found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $announcements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
