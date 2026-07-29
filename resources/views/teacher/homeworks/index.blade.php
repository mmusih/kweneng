<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-cyan-600 to-blue-700 rounded-lg shadow-lg flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">Homework</h2>
                <p class="text-cyan-100 text-sm mt-1">Create homework and track submission records</p>
            </div>
            <a href="{{ route('teacher.dashboard') }}" class="text-white hover:text-cyan-100 text-sm font-medium">Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($activeAcademicYear && $activeTerm)
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900">
                    <div class="font-semibold">Current homework context</div>
                    <div class="text-sm mt-1">
                        {{ $activeAcademicYear->year_name }} · {{ $activeTerm->name }}. This is selected automatically for every new homework.
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-900">
                    <div class="font-semibold">Current year or term is missing</div>
                    <div class="text-sm mt-1">Homework can be created only after the administrator activates the current academic year and term.</div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold">Create Homework</h3>
                        <p class="text-sm text-gray-500 mt-1">Select the class and subject, add instructions, and optionally attach a photo of the homework. The current academic year and term are selected automatically.</p>
                    </div>

                    <form method="POST" action="{{ route('teacher.homeworks.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                            <h4 class="text-lg font-semibold mb-4 text-gray-800">Step 1: Select Class</h4>

                            @if (count($classes) > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach ($classes as $classData)
                                        <div class="border border-gray-200 rounded-xl p-4 bg-white hover:shadow-md hover:border-cyan-400 transition-all duration-200 cursor-pointer class-card"
                                            data-class-id="{{ $classData['class']->id }}"
                                            data-academic-year-id="{{ $classData['academic_year']->id }}"
                                            data-subjects='@json($classData['subjects']->map(fn($subject) => ['id' => $subject->id, 'name' => $subject->name, 'code' => $subject->code])->values())'>
                                            <h5 class="font-semibold text-gray-800 text-lg">{{ $classData['class']->name }}</h5>
                                            <p class="text-sm text-gray-600 mt-1">{{ $classData['academic_year']->year_name }}</p>
                                            <p class="text-xs text-gray-500 mt-2">{{ $classData['subjects']->count() }} subject(s)</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-yellow-800">
                                    You are not assigned to any classes for homework.
                                </div>
                            @endif
                        </div>

                        <input type="hidden" id="class_id" name="class_id" value="{{ old('class_id') }}">
                        <input type="hidden" id="academic_year_id" name="academic_year_id" value="{{ old('academic_year_id', $activeAcademicYear?->id) }}">
                        <input type="hidden" id="term_id" name="term_id" value="{{ old('term_id', $activeTerm?->id) }}">

                        <div id="homework-form-section" class="hidden">
                            <div class="mb-4 p-4 rounded-lg bg-cyan-50 border border-cyan-200 text-cyan-900 text-sm">
                                Submission tracking is created automatically for all eligible learners. Every learner starts as <strong>Submitted</strong>; update only the learners who are late, copied, or did not submit.
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="subject_id" :value="__('Subject')" />
                                    <select id="subject_id" name="subject_id" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>
                                        <option value="">Select subject</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label :value="__('Term')" />
                                    <div class="mt-1 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-900">
                                        {{ $activeTerm?->name ?? 'No active term' }}
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Teachers do not need to choose the term; the active term is used.</p>
                                </div>

                                <div>
                                    <x-input-label for="title" :value="__('Homework Title / Topic')" />
                                    <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required />
                                </div>

                                <div>
                                    <x-input-label for="total_marks" :value="__('Total Marks (Optional)')" />
                                    <x-text-input id="total_marks" class="block mt-1 w-full" type="number" step="0.01" min="0.01" name="total_marks" :value="old('total_marks')" />
                                    <p class="text-xs text-gray-500 mt-1">Leave empty when you only want to track submission.</p>
                                </div>

                                <div>
                                    <x-input-label for="assigned_date" :value="__('Assigned Date')" />
                                    <x-text-input id="assigned_date" class="block mt-1 w-full" type="date" name="assigned_date" :value="old('assigned_date', now()->format('Y-m-d'))" required />
                                </div>

                                <div>
                                    <x-input-label for="due_date" :value="__('Due Date')" />
                                    <x-text-input id="due_date" class="block mt-1 w-full" type="date" name="due_date" :value="old('due_date')" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="homework_image" :value="__('Photo of Homework (Optional)')" />
                                    <input id="homework_image" name="homework_image" type="file" accept="image/jpeg,image/png,image/webp" class="block mt-1 w-full text-sm text-gray-700 border border-gray-300 rounded-md cursor-pointer focus:outline-none focus:border-cyan-500 focus:ring-cyan-500">
                                    <p class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, WEBP. Maximum 8MB.</p>
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="description" :value="__('Description / Instructions')" />
                                    <textarea id="description" name="description" rows="4" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500">{{ old('description') }}</textarea>
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-6">
                                <x-primary-button>{{ __('Create Homework') }}</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold">Homework Records</h3>
                        <p class="text-sm text-gray-500 mt-1">Each homework has a submission register. Marks are optional.</p>
                    </div>

                    @if ($homeworks->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Homework</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class / Subject</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tracking</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($homeworks as $homework)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 min-w-[240px]">
                                                <div class="font-semibold">{{ $homework->title }}</div>
                                                <div class="text-xs text-gray-500 mt-1">{{ $homework->term->name ?? 'N/A' }} · {{ $homework->hasMarksConfigured() ? number_format($homework->total_marks, 2) . ' marks' : 'No marks configured' }}</div>
                                                @if ($homework->hasAttachment())
                                                    <a href="{{ route('teacher.homeworks.attachment', $homework) }}" class="inline-flex mt-2 text-xs font-semibold text-cyan-700 hover:text-cyan-900">View attached photo</a>
                                                @elseif ($homework->attachmentWasPurged())
                                                    <span class="inline-flex mt-2 text-xs font-semibold text-gray-500">Attachment removed after term close</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <div>{{ $homework->class->name ?? 'N/A' }}</div>
                                                <div class="text-xs text-gray-500">{{ $homework->subject->name ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                                <div>Assigned: {{ $homework->assigned_date?->format('Y-m-d') }}</div>
                                                <div class="text-xs text-gray-500">Due: {{ $homework->due_date?->format('Y-m-d') ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-700 min-w-[260px]">
                                                <div class="grid grid-cols-2 gap-2">
                                                    <span class="rounded bg-green-50 text-green-800 px-2 py-1">Submitted: {{ $homework->submitted_count }}</span>
                                                    <span class="rounded bg-yellow-50 text-yellow-800 px-2 py-1">Late: {{ $homework->late_submission_count }}</span>
                                                    <span class="rounded bg-orange-50 text-orange-800 px-2 py-1">Copied: {{ $homework->copied_count }}</span>
                                                    <span class="rounded bg-red-50 text-red-800 px-2 py-1">Not submitted: {{ $homework->not_submitted_count }}</span>
                                                </div>
                                                <div class="mt-2 text-gray-500">Marked: {{ $homework->marked_count }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <div class="flex flex-col gap-2">
                                                    <a href="{{ route('teacher.homeworks.marks', $homework) }}" class="inline-flex items-center justify-center px-3 py-2 bg-cyan-600 text-white text-sm font-semibold rounded-md hover:bg-cyan-700">
                                                        Track Submissions
                                                    </a>

                                                    @if ($homework->term?->isActive() && ! $homework->term?->isLocked())
                                                        <form method="POST" action="{{ route('teacher.homeworks.destroy', $homework) }}" onsubmit="return confirm('Delete this homework? The uploaded photo/file will be removed and parents will no longer see this homework.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 border border-red-200 text-red-700 bg-red-50 text-sm font-semibold rounded-md hover:bg-red-100">
                                                                Delete Homework
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
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
                const classIdInput = document.getElementById('class_id');
                const academicYearInput = document.getElementById('academic_year_id');
                const formSection = document.getElementById('homework-form-section');

                classCards.forEach(card => {
                    card.addEventListener('click', function() {
                        classCards.forEach(item => item.classList.remove('ring-2', 'ring-cyan-500', 'border-cyan-500', 'bg-cyan-50'));
                        this.classList.add('ring-2', 'ring-cyan-500', 'border-cyan-500', 'bg-cyan-50');

                        const classId = this.getAttribute('data-class-id');
                        const academicYearId = this.getAttribute('data-academic-year-id');
                        const subjects = JSON.parse(this.getAttribute('data-subjects') || '[]');

                        classIdInput.value = classId;
                        academicYearInput.value = academicYearId;

                        subjectSelect.innerHTML = '<option value="">Select subject</option>';
                        subjects.forEach(subject => {
                            const option = document.createElement('option');
                            option.value = subject.id;
                            option.textContent = subject.name + (subject.code ? ' (' + subject.code + ')' : '');
                            subjectSelect.appendChild(option);
                        });

                        formSection.classList.remove('hidden');
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
