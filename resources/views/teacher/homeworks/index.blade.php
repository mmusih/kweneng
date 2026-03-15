<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Homework
            </h2>
            <a href="{{ route('teacher.dashboard') }}"
                class="text-white hover:text-blue-100 text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold">Create Homework</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Create homework for a class and subject you are assigned to teach.
                        </p>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('teacher.homeworks.store') }}">
                        @csrf

                        <div class="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                            <h4 class="text-lg font-semibold mb-4 text-gray-800">Step 1: Select Class</h4>

                            @if (count($classes) > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach ($classes as $classData)
                                        <div class="border border-gray-200 rounded-xl p-4 bg-white hover:shadow-md hover:border-indigo-400 transition-all duration-200 cursor-pointer class-card"
                                            data-class-id="{{ $classData['class']->id }}"
                                            data-academic-year-id="{{ $classData['academic_year']->id }}"
                                            data-subjects='@json($classData['subjects']->map(fn($subject) => ['id' => $subject->id, 'name' => $subject->name, 'code' => $subject->code])->values())'>
                                            <h5 class="font-semibold text-gray-800 text-lg">
                                                {{ $classData['class']->name }}
                                            </h5>
                                            <p class="text-sm text-gray-600 mt-1">
                                                {{ $classData['academic_year']->year_name }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-2">
                                                {{ $classData['subjects']->count() }} subject(s)
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <p class="text-yellow-800">You are not assigned to any classes for homework.</p>
                                </div>
                            @endif
                        </div>

                        <input type="hidden" id="class_id" name="class_id" value="{{ old('class_id') }}">
                        <input type="hidden" id="academic_year_id" name="academic_year_id"
                            value="{{ old('academic_year_id') }}">

                        <div id="homework-form-section" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="subject_id" :value="__('Subject')" />
                                <select id="subject_id" name="subject_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Select subject</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="term_id" :value="__('Term')" />
                                <select id="term_id" name="term_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Select term</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="title" :value="__('Homework Title / Topic')" />
                                <x-text-input id="title" class="block mt-1 w-full" type="text" name="title"
                                    :value="old('title')" required />
                            </div>

                            <div>
                                <x-input-label for="total_marks" :value="__('Total Marks')" />
                                <x-text-input id="total_marks" class="block mt-1 w-full" type="number" step="0.01"
                                    min="0.01" name="total_marks" :value="old('total_marks')" required />
                            </div>

                            <div>
                                <x-input-label for="assigned_date" :value="__('Assigned Date')" />
                                <x-text-input id="assigned_date" class="block mt-1 w-full" type="date"
                                    name="assigned_date" :value="old('assigned_date', now()->format('Y-m-d'))" required />
                            </div>

                            <div>
                                <x-input-label for="due_date" :value="__('Due Date')" />
                                <x-text-input id="due_date" class="block mt-1 w-full" type="date" name="due_date"
                                    :value="old('due_date')" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="description" :value="__('Description / Instructions')" />
                                <textarea id="description" name="description" rows="4"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button>
                                {{ __('Create Homework') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold">Homework Records</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            View homework you created and enter student marks.
                        </p>
                    </div>

                    @if ($homeworks->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Title</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Class</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Subject</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Total Marks</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Assigned</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($homeworks as $homework)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $homework->title }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $homework->class->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $homework->subject->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $homework->term->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ number_format($homework->total_marks, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $homework->assigned_date?->format('Y-m-d') }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <a href="{{ route('teacher.homeworks.marks', $homework) }}"
                                                    class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                                    Enter Marks
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No homework records found yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const classCards = document.querySelectorAll('.class-card');
                const subjectSelect = document.getElementById('subject_id');
                const termSelect = document.getElementById('term_id');
                const classIdInput = document.getElementById('class_id');
                const academicYearInput = document.getElementById('academic_year_id');
                const formSection = document.getElementById('homework-form-section');

                classCards.forEach(card => {
                    card.addEventListener('click', function() {
                        classCards.forEach(item => {
                            item.classList.remove('ring-2', 'ring-indigo-500',
                                'border-indigo-500', 'bg-indigo-50');
                        });

                        this.classList.add('ring-2', 'ring-indigo-500', 'border-indigo-500',
                            'bg-indigo-50');

                        const classId = this.getAttribute('data-class-id');
                        const academicYearId = this.getAttribute('data-academic-year-id');
                        const subjects = JSON.parse(this.getAttribute('data-subjects') || '[]');

                        classIdInput.value = classId;
                        academicYearInput.value = academicYearId;

                        subjectSelect.innerHTML = '<option value="">Select subject</option>';
                        subjects.forEach(subject => {
                            const option = document.createElement('option');
                            option.value = subject.id;
                            option.textContent = subject.name + (subject.code ? ' (' + subject
                                .code + ')' : '');
                            subjectSelect.appendChild(option);
                        });

                        fetch("{{ url('/teacher/marks/terms') }}/" + academicYearId)
                            .then(response => response.json())
                            .then(data => {
                                termSelect.innerHTML = '<option value="">Select term</option>';
                                data.forEach(term => {
                                    const option = document.createElement('option');
                                    option.value = term.id;
                                    option.textContent = term.name;
                                    termSelect.appendChild(option);
                                });
                            });

                        formSection.classList.remove('hidden');
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
