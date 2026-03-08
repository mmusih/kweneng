<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Enter Marks
                </h2>
                <a href="{{ route('teacher.dashboard') }}"
                   class="text-white hover:text-blue-100 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Marks Entry</h3>
                        <p class="text-gray-600">Enter midterm and endterm marks for your students.</p>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Step 1: Select Class -->
                    <div class="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800">Step 1: Select Class</h4>

                        @if(count($classes) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($classes as $classData)
                                    <div class="border border-gray-200 rounded-xl p-4 bg-white hover:shadow-md hover:border-indigo-400 transition-all duration-200 cursor-pointer class-card"
                                         data-class-id="{{ $classData['class']->id }}"
                                         data-academic-year-id="{{ $classData['academic_year']->id }}">
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
                                <p class="text-yellow-800">You are not assigned to any classes for marks entry.</p>
                                <p class="text-sm text-yellow-700 mt-1">Please contact your administrator to assign you to classes.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Step 2: Select Subject and Term -->
                    <div id="step2" class="hidden mb-8 p-6 bg-blue-50 rounded-xl border border-blue-200">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800">Step 2: Select Subject and Term</h4>

                        <form id="marks-form-step2">
                            @csrf
                            <input type="hidden" id="selected-class-id" name="class_id">
                            <input type="hidden" id="selected-academic-year-id" name="academic_year_id">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="subject_id" :value="__('Subject')" />
                                    <select id="subject_id" name="subject_id"
                                        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm"
                                        required>
                                        <option value="">Select Subject</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="term_id" :value="__('Term')" />
                                    <select id="term_id" name="term_id"
                                        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm"
                                        required>
                                        <option value="">Select Term</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-primary-button type="button" id="load-students-btn">
                                    {{ __('Load Students') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <!-- Step 3: Enter Marks -->
                    <div id="step3" class="hidden">
                        <div class="mb-4">
                            <h4 class="text-xl font-semibold text-gray-800">Step 3: Enter Marks</h4>
                            <p class="text-sm text-gray-600 mt-1">
                                The selected student's row will turn blue, and the active input cell will turn green.
                            </p>
                        </div>

                        <form id="marks-entry-form" method="POST" action="{{ route('teacher.marks.store') }}">
                            @csrf
                            <input type="hidden" id="form-class-id" name="class_id">
                            <input type="hidden" id="form-subject-id" name="subject_id">
                            <input type="hidden" id="form-academic-year-id" name="academic_year_id">
                            <input type="hidden" id="form-term-id" name="term_id">

                            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                                <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-gray-200">
                                    <h5 class="text-base font-semibold text-gray-800">Student Marks List</h5>
                                    <p class="text-sm text-gray-500 mt-1">Type marks directly. Use Tab or Enter to move between fields.</p>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Student</th>
                                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Midterm (0-100)</th>
                                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Endterm (0-100)</th>
                                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody id="students-table-body" class="divide-y divide-gray-100 bg-white">
                                            <!-- Students will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <a href="{{ route('teacher.marks.index') }}"
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                                    Cancel
                                </a>
                                <x-primary-button>
                                    {{ __('Save Marks') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <style>
        /* Remove number input arrows */
        input.no-spinner::-webkit-outer-spin-button,
        input.no-spinner::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input.no-spinner[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupEventHandlers();
        });

        function setupEventHandlers() {
            const classCards = document.querySelectorAll('.class-card');

            classCards.forEach(function(card) {
                card.addEventListener('click', function() {
                    handleClassSelection(this);
                });
            });

            const loadStudentsBtn = document.getElementById('load-students-btn');
            if (loadStudentsBtn) {
                loadStudentsBtn.addEventListener('click', function() {
                    loadStudentsHandler();
                });
            }
        }

        function handleClassSelection(card) {
            const classId = card.getAttribute('data-class-id');
            const academicYearId = card.getAttribute('data-academic-year-id');

            if (!classId || !academicYearId) {
                alert('Error: Missing class data');
                return;
            }

            document.querySelectorAll('.class-card').forEach(function(item) {
                item.classList.remove('ring-2', 'ring-indigo-500', 'border-indigo-500', 'bg-indigo-50');
            });

            card.classList.add('ring-2', 'ring-indigo-500', 'border-indigo-500', 'bg-indigo-50');

            document.getElementById('selected-class-id').value = classId;
            document.getElementById('selected-academic-year-id').value = academicYearId;
            document.getElementById('form-class-id').value = classId;
            document.getElementById('form-academic-year-id').value = academicYearId;

            loadSubjects(classId, academicYearId);
            loadTermsFromBackend(academicYearId);

            document.getElementById('step2').classList.remove('hidden');
            document.getElementById('step3').classList.add('hidden');

            setTimeout(() => {
                document.getElementById('step2').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }

        function loadSubjects(classId, academicYearId) {
            fetch("{{ route('teacher.marks.class-subjects') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    class_id: parseInt(classId),
                    academic_year_id: parseInt(academicYearId),
                    _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load subjects');
                }
                return response.json();
            })
            .then(data => {
                populateSubjectsDropdown(data.subjects || []);
            })
            .catch(error => {
                alert('Error loading subjects: ' + error.message);
            });
        }

        function loadTermsFromBackend(academicYearId) {
            fetch("{{ url('/teacher/marks/terms') }}/" + academicYearId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to load terms');
                    }
                    return response.json();
                })
                .then(data => {
                    populateTermsDropdown(data);
                })
                .catch(error => {
                    alert('Error loading terms: ' + error.message);
                });
        }

        function populateSubjectsDropdown(subjects) {
            const subjectSelect = document.getElementById('subject_id');
            if (subjectSelect) {
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                subjects.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject.id;
                    option.textContent = subject.name + ' (' + subject.code + ')';
                    subjectSelect.appendChild(option);
                });
            }
        }

        function populateTermsDropdown(terms) {
            const termSelect = document.getElementById('term_id');
            if (termSelect) {
                termSelect.innerHTML = '<option value="">Select Term</option>';
                terms.forEach(term => {
                    const option = document.createElement('option');
                    option.value = term.id;
                    option.textContent = term.name;
                    termSelect.appendChild(option);
                });
            }
        }

        function loadStudentsHandler() {
            const classId = document.getElementById('selected-class-id').value;
            const subjectId = document.getElementById('subject_id').value;
            const academicYearId = document.getElementById('selected-academic-year-id').value;
            const termId = document.getElementById('term_id').value;

            if (!classId || !subjectId || !academicYearId || !termId) {
                alert('Please select class, subject, and term first');
                return;
            }

            loadStudents(classId, subjectId, academicYearId, termId);
        }

        function loadStudents(classId, subjectId, academicYearId, termId) {
            fetch("{{ route('teacher.marks.students') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    class_id: parseInt(classId),
                    subject_id: parseInt(subjectId),
                    academic_year_id: parseInt(academicYearId),
                    term_id: parseInt(termId),
                    _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load students');
                }
                return response.json();
            })
            .then(data => {
                if (data.students) {
                    populateStudentsTable(data.students, data.existing_marks || {});

                    document.getElementById('form-subject-id').value = subjectId;
                    document.getElementById('form-term-id').value = termId;

                    document.getElementById('step3').classList.remove('hidden');

                    setTimeout(() => {
                        document.getElementById('step3').scrollIntoView({ behavior: 'smooth', block: 'start' });

                        const firstInput = document.querySelector('#students-table-body input.mark-input');
                        if (firstInput) {
                            firstInput.focus();
                            firstInput.select();
                        }
                    }, 100);
                } else {
                    alert('No students found for this selection');
                }
            })
            .catch(error => {
                alert('Error loading students: ' + error.message);
            });
        }

        function populateStudentsTable(students, existingMarks) {
            const tbody = document.getElementById('students-table-body');

            if (tbody) {
                tbody.innerHTML = '';

                if (students.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 bg-gray-50">
                                <div class="text-sm font-medium">No students found</div>
                                <div class="text-xs text-gray-400 mt-1">Please check the selected class, subject, and term.</div>
                            </td>
                        </tr>
                    `;
                    return;
                }

                students.forEach(function(studentData, index) {
                    const student = studentData.student;
                    const existingMark = existingMarks[student.id] || {};

                    const row = document.createElement('tr');
                    row.className = 'student-row transition-colors duration-150';

                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap align-middle">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white text-sm font-bold shadow-sm">
                                    ${student.user.name.charAt(0).toUpperCase()}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900">${student.user.name}</div>
                                    <div class="text-xs text-gray-500">Adm No: ${student.admission_no}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 align-middle input-cell transition-colors duration-150">
                            <input type="number"
                                   name="marks[${student.id}][midterm]"
                                   class="mark-input no-spinner block w-full max-w-[120px] px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900 bg-white focus:outline-none cursor-text"
                                   min="0" max="100" step="0.01"
                                   value="${existingMark.midterm_score || ''}"
                                   placeholder="Midterm"
                                   ${index === 0 ? 'autofocus' : ''}>
                        </td>

                        <td class="px-6 py-4 align-middle input-cell transition-colors duration-150">
                            <input type="number"
                                   name="marks[${student.id}][endterm]"
                                   class="mark-input no-spinner block w-full max-w-[120px] px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900 bg-white focus:outline-none cursor-text"
                                   min="0" max="100" step="0.01"
                                   value="${existingMark.endterm_score || ''}"
                                   placeholder="Endterm">
                        </td>

                        <td class="px-6 py-4 align-middle input-cell transition-colors duration-150">
                            <input type="text"
                                   name="marks[${student.id}][remarks]"
                                   class="mark-input block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900 bg-white focus:outline-none cursor-text"
                                   maxlength="500"
                                   placeholder="Optional remarks"
                                   value="${existingMark.remarks || ''}">
                        </td>
                    `;

                    tbody.appendChild(row);
                });

                enhanceInputNavigation();
            }
        }

        function clearActiveStates() {
            document.querySelectorAll('.student-row').forEach(function(row) {
                row.classList.remove('bg-blue-100');
            });

            document.querySelectorAll('.input-cell').forEach(function(cell) {
                cell.classList.remove('bg-green-100');
            });

            document.querySelectorAll('.mark-input').forEach(function(input) {
                input.classList.remove('border-green-500', 'ring-2', 'ring-green-200');
                input.classList.add('border-gray-300');
            });
        }

        function enhanceInputNavigation() {
            const inputs = Array.from(document.querySelectorAll('#students-table-body .mark-input'));

            inputs.forEach((input, index) => {
                input.addEventListener('focus', function() {
                    clearActiveStates();

                    const row = this.closest('.student-row');
                    const cell = this.closest('.input-cell');

                    if (row) {
                        row.classList.add('bg-blue-100');
                    }

                    if (cell) {
                        cell.classList.add('bg-green-100');
                    }

                    this.classList.remove('border-gray-300');
                    this.classList.add('border-green-500', 'ring-2', 'ring-green-200');

                    if (this.type !== 'text') {
                        this.select();
                    }
                });

                input.addEventListener('click', function() {
                    this.focus();
                    if (this.type !== 'text') {
                        this.select();
                    }
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const nextInput = inputs[index + 1];
                        if (nextInput) {
                            nextInput.focus();
                            if (nextInput.type !== 'text') {
                                nextInput.select();
                            }
                        }
                    }
                });
            });
        }
    </script>
    @endpush
</x-app-layout>