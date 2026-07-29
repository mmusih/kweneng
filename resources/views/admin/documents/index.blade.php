<x-app-layout>
    <x-slot name="header">
        <h2 class="mt-16 font-bold text-2xl text-gray-800">School Documents</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Upload form --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Upload New Document</h3>
                <form action="{{ route('admin.documents.store') }}" method="POST" enctype="multipart/form-data"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" name="title" value="{{ old('title') }}" required maxlength="255"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                            placeholder="e.g. Term 1 2026 Timetable">
                        @error('title')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Select category —</option>
                            <option value="timetable" {{ old('category') === 'timetable' ? 'selected' : '' }}>🗓️
                                Timetable</option>
                            <option value="prospectus" {{ old('category') === 'prospectus' ? 'selected' : '' }}>📖
                                School Prospectus</option>
                            <option value="booklist" {{ old('category') === 'booklist' ? 'selected' : '' }}>📚 Book List
                            </option>
                            <option value="uniform" {{ old('category') === 'uniform' ? 'selected' : '' }}>👕 Uniform
                                Price List</option>
                        </select>
                        @error('category')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year <span
                                class="text-gray-400 font-normal">(optional)</span></label>
                        <select name="academic_year_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— All years / General —</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}"
                                    {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                    {{ $year->year_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">File <span
                                class="text-gray-400 font-normal">(PDF, Word, Excel — max 10 MB)</span></label>
                        <input type="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx"
                            class="w-full text-sm text-gray-600 border border-gray-300 rounded-md px-3 py-2 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700">
                        @error('file')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Upload Document
                        </button>
                    </div>
                </form>
            </div>

            {{-- Documents list grouped by category --}}
            @forelse ($documents as $category => $docs)
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <span class="text-lg">{{ \App\Models\SchoolDocument::getCategoryIcon($category) }}</span>
                        <h3 class="text-base font-semibold text-gray-800">
                            {{ \App\Models\SchoolDocument::getCategoryLabel($category) }}</h3>
                        <span class="text-xs text-gray-400 ml-1">({{ $docs->count() }})</span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Title</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Academic Year</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Uploaded</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Visible to Parents</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($docs as $doc)
                                <tr class="{{ !$doc->is_active ? 'opacity-50' : '' }}">
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-900">{{ $doc->title }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $doc->original_filename }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $doc->academicYear->year_name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $doc->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <form action="{{ route('admin.documents.toggle', $doc) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition
                                                        {{ $doc->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                                {{ $doc->is_active ? 'Visible' : 'Hidden' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <form action="{{ route('admin.documents.destroy', $doc) }}" method="POST"
                                            onsubmit="return confirm('Delete this document?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="text-red-500 hover:text-red-700 transition text-xs font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="bg-white shadow-sm rounded-lg p-12 text-center">
                    <p class="text-gray-400 text-sm">No documents uploaded yet.</p>
                </div>
            @endforelse

        </div>
    </div>
</x-app-layout>
