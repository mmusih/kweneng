<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Enter Marks
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
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Marks Entry</h3>
                        <p class="text-gray-600">Enter midterm and endterm marks for your students.</p>
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

                    <!-- Step 1: Select Class -->
                    <div class="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800">Step 1: Select Class</h4>

                        @if (count($classes) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($classes as $classData)
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
                                <p class="text-sm text-yellow-700 mt-1">Please contact your administrator to assign you
                                    to classes.</p>
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
                                        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm data-select"
                                        required>
                                        <option value="">Select Subject</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="term_id" :value="__('Term')" />
                                    <select id="term_id" name="term_id"
                                        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm data-select"
                                        required>
                                        <option value="">Select Term</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-2">
                                        Term labels show whether Midterm and Endterm are locked.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-primary-button type="button" id="load-students-btn">
                                    {{ __('Load Students') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <!-- Import Marks -->
                    <div id="import-section" class="hidden mb-8 p-6 bg-cyan-50 rounded-xl border border-cyan-200">
                        <h4 class="text-lg font-semibold mb-2 text-gray-800">Import Marks (Per Subject)</h4>
                        <p class="text-sm text-gray-600 mb-4">
                            Upload one CSV file for one selected class, subject, term, and exam type.
                            Use:
                            <span class="font-medium">surname,name,score</span>
                            and optionally
                            <span class="font-medium">remarks</span>.
                        </p>

                        <form method="POST" action="{{ route('teacher.marks.import') }}" enctype="multipart/form-data"
                            id="marks-import-form">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                                <div>
                                    <x-input-label for="import_exam_type" :value="__('Exam Type')" />
                                    <select id="import_exam_type" name="exam_type"
                                        class="block mt-1 w-full border-gray-300 focus:border-cyan-500 focus:ring-cyan-500 rounded-lg shadow-sm data-select">
                                        <option value="">Select Exam Type</option>
                                        <option value="midterm">Midterm</option>
                                        <option value="endterm">Endterm</option>
                                    </select>
                                </div>

                                <div class="xl:col-span-2">
                                    <x-input-label for="marks_import_file" :value="__('CSV File')" />
                                    <input id="marks_import_file" name="marks_import_file" type="file"
                                        accept=".csv,text/csv"
                                        class="block mt-1 w-full rounded-lg border border-gray-300 bg-white p-2 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 file-input-with-caret" />
                                </div>

                                <div class="flex items-end">
                                    <button type="button"
                                        class="inline-flex items-center px-4 py-2 border border-cyan-600 text-sm font-medium rounded-lg text-cyan-700 bg-white hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition"
                                        id="import-preview-btn">
                                        Import Marks
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 rounded-lg bg-white border border-cyan-200 p-4 text-sm text-gray-700">
                                <p class="font-semibold text-gray-800 mb-2">Expected CSV format</p>
                                <pre class="text-xs text-gray-600 whitespace-pre-wrap">surname,name,score,remarks
Lesetedi,Manisha Areka,78,Good effort
Stanley,Hailey,88,Excellent work</pre>
                                <p class="mt-3 text-xs text-gray-500">
                                    Students are matched by <span class="font-medium">surname first</span>, then
                                    <span class="font-medium">name(s)</span>,
                                    within the selected class.
                                </p>
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

                        <div id="lock-status-container" class="hidden mb-4 space-y-3">
                            <div id="term-locked-alert"
                                class="hidden rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                                <div class="font-semibold">This term is fully locked.</div>
                                <div class="text-sm mt-1">No marks can be edited for this term.</div>
                            </div>

                            <div id="midterm-locked-alert"
                                class="hidden rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800">
                                <div class="font-semibold">Midterm marks are locked.</div>
                                <div class="text-sm mt-1">Midterm scores are read-only. Endterm and remarks may still
                                    be
                                    editable if allowed.</div>
                            </div>

                            <div id="endterm-locked-alert"
                                class="hidden rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                                <div class="font-semibold">Endterm marks are locked.</div>
                                <div class="text-sm mt-1">Endterm scores are read-only. Midterm and remarks may still
                                    be
                                    editable if allowed.</div>
                            </div>
                        </div>

                        <form id="marks-entry-form" method="POST" action="{{ route('teacher.marks.store') }}">
                            @csrf
                            <input type="hidden" id="form-class-id" name="class_id">
                            <input type="hidden" id="form-subject-id" name="subject_id">
                            <input type="hidden" id="form-academic-year-id" name="academic_year_id">
                            <input type="hidden" id="form-term-id" name="term_id">

                            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                                <div
                                    class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-gray-200">
                                    <h5 class="text-base font-semibold text-gray-800">Student Marks List</h5>
                                    <p class="text-sm text-gray-500 mt-1">Type marks directly. Use Tab or Enter to move
                                        between fields.</p>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th
                                                    class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                                    Student</th>
                                                <th
                                                    class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                                    Midterm (0-100)</th>
                                                <th
                                                    class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                                    Endterm (0-100)</th>
                                                <th
                                                    class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                                    Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody id="students-table-body" class="divide-y divide-gray-100 bg-white">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <a href="{{ route('teacher.marks.index') }}"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                                    Cancel
                                </a>
                                <x-primary-button id="save-marks-button">
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
            input.no-spinner::-webkit-outer-spin-button,
            input.no-spinner::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            input.no-spinner[type=number] {
                -moz-appearance: textfield;
                appearance: textfield;
            }

            .locked-input {
                background-color: #f3f4f6 !important;
                color: #6b7280 !important;
                cursor: not-allowed !important;
            }

            .cursor-blink:focus,
            .data-select:focus,
            .file-input-with-caret:focus {
                caret-color: #16a34a;
                animation: caretPulse 0.9s step-end infinite;
            }

            @keyframes caretPulse {
                50% {
                    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
                }
            }

            .comment-extra {
                display: none;
            }

            .comment-extra.show {
                display: block;
            }
        </style>

        <script>
            let currentLocks = {
                term_locked: false,
                midterm_locked: false,
                endterm_locked: false
            };

            const presetComments = [
                '',
                'Excellent work',
                'Very good effort',
                'Good effort',
                'Satisfactory progress',
                'Can do better',
                'Needs more practice',
                'Needs improvement',
                'Incomplete work',
                '__custom__'
            ];

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

                const importPreviewBtn = document.getElementById('import-preview-btn');
                if (importPreviewBtn) {
                    importPreviewBtn.addEventListener('click', function() {
                        handleImportButton();
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
                document.getElementById('import-section').classList.remove('hidden');
                document.getElementById('step3').classList.add('hidden');

                setTimeout(() => {
                    document.getElementById('step2').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
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

                        let label = term.name;
                        let lockParts = [];

                        if (term.midterm_locked) {
                            lockParts.push('Midterm Locked');
                        }

                        if (term.endterm_locked) {
                            lockParts.push('Endterm Locked');
                        }

                        if (lockParts.length > 0) {
                            label += ' (' + lockParts.join(', ') + ')';
                        }

                        option.textContent = label;
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

            function handleImportButton() {
                const classId = document.getElementById('selected-class-id').value;
                const subjectId = document.getElementById('subject_id').value;
                const academicYearId = document.getElementById('selected-academic-year-id').value;
                const termId = document.getElementById('term_id').value;
                const examType = document.getElementById('import_exam_type').value;
                const fileInput = document.getElementById('marks_import_file');
                const importForm = document.getElementById('marks-import-form');

                if (!classId || !subjectId || !academicYearId || !termId) {
                    alert('Please select class, subject, and term before importing marks.');
                    return;
                }

                if (!examType) {
                    alert('Please select the exam type to import.');
                    document.getElementById('import_exam_type').focus();
                    return;
                }

                if (!fileInput.files.length) {
                    alert('Please choose a CSV file to import.');
                    fileInput.focus();
                    return;
                }

                let hiddenClass = importForm.querySelector('input[name="class_id"]');
                let hiddenSubject = importForm.querySelector('input[name="subject_id"]');
                let hiddenYear = importForm.querySelector('input[name="academic_year_id"]');
                let hiddenTerm = importForm.querySelector('input[name="term_id"]');

                if (!hiddenClass) {
                    hiddenClass = document.createElement('input');
                    hiddenClass.type = 'hidden';
                    hiddenClass.name = 'class_id';
                    importForm.appendChild(hiddenClass);
                }

                if (!hiddenSubject) {
                    hiddenSubject = document.createElement('input');
                    hiddenSubject.type = 'hidden';
                    hiddenSubject.name = 'subject_id';
                    importForm.appendChild(hiddenSubject);
                }

                if (!hiddenYear) {
                    hiddenYear = document.createElement('input');
                    hiddenYear.type = 'hidden';
                    hiddenYear.name = 'academic_year_id';
                    importForm.appendChild(hiddenYear);
                }

                if (!hiddenTerm) {
                    hiddenTerm = document.createElement('input');
                    hiddenTerm.type = 'hidden';
                    hiddenTerm.name = 'term_id';
                    importForm.appendChild(hiddenTerm);
                }

                hiddenClass.value = classId;
                hiddenSubject.value = subjectId;
                hiddenYear.value = academicYearId;
                hiddenTerm.value = termId;

                importForm.submit();
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
                            currentLocks = data.locks || {
                                term_locked: false,
                                midterm_locked: false,
                                endterm_locked: false
                            };

                            updateLockAlerts();
                            populateStudentsTable(data.students, data.existing_marks || {}, currentLocks);

                            document.getElementById('form-subject-id').value = subjectId;
                            document.getElementById('form-term-id').value = termId;

                            document.getElementById('step3').classList.remove('hidden');

                            setTimeout(() => {
                                document.getElementById('step3').scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });

                                const firstEnabledInput = document.querySelector(
                                    '#students-table-body input.mark-input:not([disabled]), #students-table-body select.mark-input:not([disabled])'
                                );

                                if (firstEnabledInput) {
                                    firstEnabledInput.focus();
                                    if (firstEnabledInput.tagName === 'INPUT' && firstEnabledInput.type !==
                                        'text') {
                                        firstEnabledInput.select();
                                    }
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

            function updateLockAlerts() {
                const lockContainer = document.getElementById('lock-status-container');
                const termLockedAlert = document.getElementById('term-locked-alert');
                const midtermLockedAlert = document.getElementById('midterm-locked-alert');
                const endtermLockedAlert = document.getElementById('endterm-locked-alert');
                const saveButton = document.getElementById('save-marks-button');

                lockContainer.classList.add('hidden');
                termLockedAlert.classList.add('hidden');
                midtermLockedAlert.classList.add('hidden');
                endtermLockedAlert.classList.add('hidden');

                if (currentLocks.term_locked || currentLocks.midterm_locked || currentLocks.endterm_locked) {
                    lockContainer.classList.remove('hidden');
                }

                if (currentLocks.term_locked) {
                    termLockedAlert.classList.remove('hidden');
                } else {
                    if (currentLocks.midterm_locked) {
                        midtermLockedAlert.classList.remove('hidden');
                    }

                    if (currentLocks.endterm_locked) {
                        endtermLockedAlert.classList.remove('hidden');
                    }
                }

                if (saveButton) {
                    if (currentLocks.term_locked || (currentLocks.midterm_locked && currentLocks.endterm_locked)) {
                        saveButton.classList.add('hidden');
                    } else {
                        saveButton.classList.remove('hidden');
                    }
                }
            }

            function buildCommentOptions(selectedValue = '') {
                return presetComments.map(comment => {
                    if (comment === '__custom__') {
                        return `<option value="__custom__" ${selectedValue && !presetComments.includes(selectedValue) ? 'selected' : ''}>Custom comment...</option>`;
                    }

                    const label = comment === '' ? 'System generated / Select preset comment' : comment;
                    const isSelected = selectedValue === comment ? 'selected' : '';
                    return `<option value="${escapeHtml(comment)}" ${isSelected}>${escapeHtml(label)}</option>`;
                }).join('');
            }

            function performancePhrase(score) {
                if (score === null || score === undefined) {
                    return 'no recorded performance';
                }
                if (score >= 90) return 'excellent performance';
                if (score >= 80) return 'very good performance';
                if (score >= 70) return 'good performance';
                if (score >= 60) return 'fair performance';
                if (score >= 50) return 'satisfactory performance';
                if (score >= 40) return 'below expectation performance';
                return 'weak performance';
            }

            function generateTeacherComment(midterm, endterm) {
                if (midterm === null && endterm === null) {
                    return '';
                }

                if (midterm !== null && endterm === null) {
                    return `The learner showed ${performancePhrase(midterm)} in the midterm assessment but did not write the end-of-term assessment.`;
                }

                if (midterm === null && endterm !== null) {
                    return `The learner showed ${performancePhrase(endterm)} in the end-of-term assessment.`;
                }

                const difference = endterm - midterm;

                if (difference >= 10) {
                    if (endterm >= 60) {
                        return 'The learner has shown clear improvement since midterm. This progress is encouraging; continued effort is needed.';
                    }
                    return 'The learner has improved since midterm, but more effort is still needed to reach the expected standard.';
                }

                if (difference >= 3) {
                    if (endterm >= 70) {
                        return 'The learner has improved and is making steady progress. More consistent effort can lead to even better results.';
                    }
                    return 'The learner has shown some improvement since midterm. Continued practice is encouraged.';
                }

                if (difference <= -10) {
                    return 'The learner’s performance has declined significantly since midterm. Immediate improvement and greater commitment are required.';
                }

                if (difference <= -3) {
                    return 'The learner’s performance has dropped since midterm. More focus and consistency are needed.';
                }

                if (endterm >= 80) {
                    return 'The learner has maintained a very good standard throughout the term. Keep up the good work.';
                }

                if (endterm >= 60) {
                    return 'The learner’s performance has remained fairly steady. More effort can lead to better results.';
                }

                if (endterm >= 40) {
                    return 'The learner’s performance remains below expectation. More effort and support are required.';
                }

                return 'The learner’s performance is weak and requires immediate improvement.';
            }

            function parseNullableNumber(value) {
                if (value === '' || value === null || value === undefined) {
                    return null;
                }

                const parsed = Number(value);
                return Number.isNaN(parsed) ? null : parsed;
            }

            function populateStudentsTable(students, existingMarks, locks) {
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

                        const midtermDisabled = locks.term_locked || locks.midterm_locked;
                        const endtermDisabled = locks.term_locked || locks.endterm_locked;
                        const remarksDisabled = locks.term_locked;

                        const midtermValue = existingMark.midterm_score ?? '';
                        const endtermValue = existingMark.endterm_score ?? '';
                        const existingRemark = existingMark.remarks ?? '';
                        const generatedComment = generateTeacherComment(
                            parseNullableNumber(midtermValue),
                            parseNullableNumber(endtermValue)
                        );

                        const useExistingRemark = existingRemark !== '';
                        const initialRemark = useExistingRemark ? existingRemark : generatedComment;
                        const useCustomRemark = initialRemark && !presetComments.includes(initialRemark);

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
                                   class="mark-input no-spinner cursor-blink block w-full max-w-[120px] px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900 bg-white focus:outline-none cursor-text ${midtermDisabled ? 'locked-input' : ''}"
                                   min="0" max="100" step="0.01"
                                   value="${midtermValue}"
                                   placeholder="Midterm"
                                   data-student-id="${student.id}"
                                   data-field="midterm"
                                   ${midtermDisabled ? 'disabled' : ''}
                                   ${index === 0 && !midtermDisabled ? 'autofocus' : ''}>
                        </td>

                        <td class="px-6 py-4 align-middle input-cell transition-colors duration-150">
                            <input type="number"
                                   name="marks[${student.id}][endterm]"
                                   class="mark-input no-spinner cursor-blink block w-full max-w-[120px] px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900 bg-white focus:outline-none cursor-text ${endtermDisabled ? 'locked-input' : ''}"
                                   min="0" max="100" step="0.01"
                                   value="${endtermValue}"
                                   placeholder="Endterm"
                                   data-student-id="${student.id}"
                                   data-field="endterm"
                                   ${endtermDisabled ? 'disabled' : ''}>
                        </td>

                        <td class="px-6 py-4 align-middle input-cell transition-colors duration-150">
                            <div class="space-y-2">
                                <select
                                    class="mark-input preset-comment-select data-select block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900 bg-white focus:outline-none ${remarksDisabled ? 'locked-input' : ''}"
                                    data-student-id="${student.id}"
                                    ${remarksDisabled ? 'disabled' : ''}>
                                    ${buildCommentOptions(initialRemark)}
                                </select>

                                <input type="text"
                                       class="mark-input custom-comment-input cursor-blink block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900 bg-white focus:outline-none ${useCustomRemark ? 'comment-extra show' : 'comment-extra'} ${remarksDisabled ? 'locked-input' : ''}"
                                       data-student-id="${student.id}"
                                       maxlength="500"
                                       placeholder="Write custom remark"
                                       value="${useCustomRemark ? escapeHtml(initialRemark) : ''}"
                                       ${remarksDisabled ? 'disabled' : ''}>

                                <input type="hidden"
                                       name="marks[${student.id}][remarks]"
                                       class="remarks-hidden-input"
                                       data-student-id="${student.id}"
                                       data-manual-override="${useExistingRemark ? 'true' : 'false'}"
                                       value="${escapeHtml(initialRemark)}">
                            </div>
                        </td>
                    `;

                        tbody.appendChild(row);
                    });

                    wireCommentFields();
                    wireAutoCommentGeneration();
                    enhanceInputNavigation();
                }
            }

            function wireCommentFields() {
                const selects = document.querySelectorAll('.preset-comment-select');

                selects.forEach(select => {
                    const studentId = select.getAttribute('data-student-id');
                    const customInput = document.querySelector(`.custom-comment-input[data-student-id="${studentId}"]`);
                    const hiddenInput = document.querySelector(`.remarks-hidden-input[data-student-id="${studentId}"]`);

                    syncCommentField(select, customInput, hiddenInput);

                    select.addEventListener('change', function() {
                        if (hiddenInput) {
                            hiddenInput.dataset.manualOverride = 'true';
                        }
                        syncCommentField(select, customInput, hiddenInput);
                    });

                    if (customInput) {
                        customInput.addEventListener('input', function() {
                            if (hiddenInput) {
                                hiddenInput.value = customInput.value;
                                hiddenInput.dataset.manualOverride = 'true';
                            }
                        });
                    }
                });
            }

            function wireAutoCommentGeneration() {
                const scoreInputs = document.querySelectorAll('input[data-field="midterm"], input[data-field="endterm"]');

                scoreInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        const studentId = this.getAttribute('data-student-id');
                        const hiddenInput = document.querySelector(
                            `.remarks-hidden-input[data-student-id="${studentId}"]`);
                        const presetSelect = document.querySelector(
                            `.preset-comment-select[data-student-id="${studentId}"]`);
                        const customInput = document.querySelector(
                            `.custom-comment-input[data-student-id="${studentId}"]`);
                        const midtermInput = document.querySelector(
                            `input[data-student-id="${studentId}"][data-field="midterm"]`);
                        const endtermInput = document.querySelector(
                            `input[data-student-id="${studentId}"][data-field="endterm"]`);

                        if (!hiddenInput || hiddenInput.dataset.manualOverride === 'true') {
                            return;
                        }

                        const midterm = parseNullableNumber(midtermInput ? midtermInput.value : null);
                        const endterm = parseNullableNumber(endtermInput ? endtermInput.value : null);
                        const generated = generateTeacherComment(midterm, endterm);

                        hiddenInput.value = generated;

                        if (presetSelect) {
                            if (presetComments.includes(generated)) {
                                presetSelect.value = generated;
                                if (customInput) {
                                    customInput.classList.remove('show');
                                    customInput.value = '';
                                }
                            } else {
                                presetSelect.value = generated ? '__custom__' : '';
                                if (customInput) {
                                    if (generated) {
                                        customInput.classList.add('show');
                                        customInput.value = generated;
                                    } else {
                                        customInput.classList.remove('show');
                                        customInput.value = '';
                                    }
                                }
                            }
                        }
                    });
                });
            }

            function syncCommentField(select, customInput, hiddenInput) {
                if (!select || !hiddenInput) {
                    return;
                }

                if (select.value === '__custom__') {
                    if (customInput) {
                        customInput.classList.add('show');
                        hiddenInput.value = customInput.value || '';
                    }
                } else {
                    if (customInput) {
                        customInput.classList.remove('show');
                        customInput.value = '';
                    }
                    hiddenInput.value = select.value;
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
                    if (!input.classList.contains('locked-input')) {
                        input.classList.add('border-gray-300');
                    }
                });
            }

            function enhanceInputNavigation() {
                const inputs = Array.from(document.querySelectorAll(
                    '#students-table-body .mark-input:not([disabled])'
                ));

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

                        if (this.tagName === 'INPUT' && this.type !== 'text') {
                            this.select();
                        }
                    });

                    input.addEventListener('click', function() {
                        this.focus();
                        if (this.tagName === 'INPUT' && this.type !== 'text') {
                            this.select();
                        }
                    });

                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            const nextInput = inputs[index + 1];
                            if (nextInput) {
                                nextInput.focus();
                                if (nextInput.tagName === 'INPUT' && nextInput.type !== 'text') {
                                    nextInput.select();
                                }
                            }
                        }
                    });
                });
            }

            function escapeHtml(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
        </script>
    @endpush
</x-app-layout>
