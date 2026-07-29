<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 kw-page-header rounded-2xl shadow-sm">
            <h2 class="font-semibold text-2xl text-white leading-tight">Create Scheme of Work</h2>
            <p class="text-white/80 text-sm mt-1">Choose a teaching assignment and optionally start from an existing syllabus bank.</p>
        </div>
    </x-slot>

    <div class="py-8 kw-soft-section min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('teacher.schemes.store') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Teaching Assignment</label>
                    <select name="teacher_subject_id" required class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select class and subject</option>
                        @foreach ($assignments as $assignment)
                            <option value="{{ $assignment->id }}" @selected(old('teacher_subject_id') == $assignment->id)>
                                {{ $assignment->class?->name }} — {{ $assignment->subject?->name }} — {{ $assignment->academicYear?->year_name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($assignments->isEmpty())
                        <p class="text-sm text-red-600 mt-2">No teaching assignments were found for your teacher profile.</p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Scheme Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Leave blank to generate from class and subject" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Start from Existing Syllabus Bank</label>
                    <select name="syllabus_id" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">No, start with an empty topic bank</option>
                        @foreach ($syllabuses as $syllabus)
                            <option value="{{ $syllabus->id }}" @selected(old('syllabus_id') == $syllabus->id)>
                                {{ $syllabus->title }} @if($syllabus->subject) — {{ $syllabus->subject->name }} @endif @if($syllabus->class) — {{ $syllabus->class->name }} @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-2">If you select a syllabus bank, its topics and subtopics will be copied into your scheme as unplanned items.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes') }}</textarea>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <a href="{{ route('teacher.schemes.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Cancel</a>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700" @disabled($assignments->isEmpty())>
                        Create Scheme
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
