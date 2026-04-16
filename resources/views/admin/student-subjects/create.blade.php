<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Assign Students to Subjects
            </h2>
        </div>
    </x-slot>

    @php
        $selectedAcademicYearId = old('academic_year_id', request('academic_year_id'));
        $selectedClassId = old('class_id', request('class_id'));
        $selectedSubjectId = old('subject_id', request('subject_id'));
        $selectedTeacherId = old('teacher_id', request('teacher_id'));
        $selectedIsElective = old('is_elective', request('is_elective', 0));
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 text-sm text-gray-600">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600">Dashboard</a>
                        <span class="mx-2">/</span>
                        <a href="{{ route('admin.student-subjects.index') }}" class="hover:text-indigo-600">Student
                            Subject Assignments</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-800 font-medium">Assign Students</span>
                    </div>

                    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="text-2xl font-semibold">Assign Students to Subjects</h3>
                            <p class="text-gray-600">
                                Work one class at a time. Choose a subject and teacher group, then assign manually or
                                import by CSV.
                            </p>
                        </div>

                        <a href="{{ route('admin.student-subjects.index') }}"
                            class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            ← Back to Assignment List
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Step 1 -->
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 mb-8">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800">Step 1: Select Academic Year and Class</h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Academic Year</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ (string) $selectedAcademicYearId === (string) $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="class_id" :value="__('Class')" />
                                <select id="class_id" name="class_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800">Step 2: Subject and Teacher Group
                                    </h4>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Pick the subject and teacher, then assign the correct students to that group.
                                    </p>
                                </div>

                                <a href="{{ route('admin.student-subjects.create') }}"
                                    class="text-sm text-gray-600 hover:text-indigo-600 font-medium">
                                    ← Change Class / Academic Year
                                </a>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <x-input-label for="subject_id" :value="__('Subject')" />
                                    <select id="subject_id" name="subject_id"
                                        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        required>
                                        <option value="">Select Subject</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="teacher_id" :value="__('Teacher')" />
                                    <select id="teacher_id" name="teacher_id"
                                        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        required>
                                        <option value="">Select Teacher</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="is_elective" :value="__('Subject Type')" />
                                    <select id="is_elective" name="is_elective"
                                        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="0"
                                            {{ (string) $selectedIsElective === '0' ? 'selected' : '' }}>
                                            Core Subject
                                        </option>
                                        <option value="1"
                                            {{ (string) $selectedIsElective === '1' ? 'selected' : '' }}>
                                            Elective Subject
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div id="selection-summary"
                                class="hidden mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 text-sm">
                            </div>

                            <!-- CSV Import -->
                            <div class="mb-8 rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-emerald-900">CSV Import</h4>
                                        <p class="text-sm text-emerald-800">
                                            Upload a CSV with columns <strong>Surname</strong> and
                                            <strong>Name</strong>.
                                            The system previews matches before saving.
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.student-subjects.import-preview') }}"
                                    enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4"
                                    id="csv-import-form">
                                    @csrf
                                    <input type="hidden" name="academic_year_id" id="import_academic_year_id"
                                        value="{{ $selectedAcademicYearId }}">
                                    <input type="hidden" name="class_id" id="import_class_id"
                                        value="{{ $selectedClassId }}">
                                    <input type="hidden" name="subject_id" id="import_subject_id"
                                        value="{{ $selectedSubjectId }}">
                                    <input type="hidden" name="teacher_id" id="import_teacher_id"
                                        value="{{ $selectedTeacherId }}">
                                    <input type="hidden" name="is_elective" id="import_is_elective"
                                        value="{{ $selectedIsElective }}">

                                    <div class="md:col-span-2">
                                        <x-input-label for="csv_file" :value="__('CSV File')" />
                                        <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt"
                                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <div class="flex items-end">
                                        <button type="submit"
                                            class="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                            id="csv-preview-btn" disabled>
                                            Preview CSV Import
                                        </button>
                                    </div>
                                </form>

                                @if (!empty($importPreview))
                                    <div class="mt-6 rounded-xl border border-emerald-300 bg-white p-4">
                                        <div class="mb-4">
                                            <h5 class="text-base font-semibold text-gray-900">Import Preview</h5>
                                            <p class="text-sm text-gray-600">
                                                Review the matches below before applying the import.
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                            <div class="rounded-lg bg-green-50 border border-green-200 p-4">
                                                <div class="text-sm text-green-700">Matched Rows</div>
                                                <div class="text-2xl font-bold text-green-800">
                                                    {{ count($importPreview['matched'] ?? []) }}</div>
                                            </div>

                                            <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                                                <div class="text-sm text-yellow-700">Unmatched Rows</div>
                                                <div class="text-2xl font-bold text-yellow-800">
                                                    {{ count($importPreview['unmatched'] ?? []) }}</div>
                                            </div>

                                            <div class="rounded-lg bg-blue-50 border border-blue-200 p-4">
                                                <div class="text-sm text-blue-700">Teacher Group</div>
                                                <div class="text-sm font-semibold text-blue-800">
                                                    Preview ready for selected subject/teacher
                                                </div>
                                            </div>
                                        </div>

                                        @if (!empty($importPreview['matched']))
                                            <div class="overflow-x-auto mb-4">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-600">
                                                                CSV Surname</th>
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-600">
                                                                CSV Name</th>
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-600">
                                                                Matched Student</th>
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-600">
                                                                Match Type</th>
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-600">
                                                                Score</th>
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-600">
                                                                Current Subject Teacher</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 bg-white">
                                                        @foreach ($importPreview['matched'] as $row)
                                                            <tr>
                                                                <td class="px-4 py-3 text-sm text-gray-800">
                                                                    {{ $row['csv_surname'] }}</td>
                                                                <td class="px-4 py-3 text-sm text-gray-800">
                                                                    {{ $row['csv_name'] }}</td>
                                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                                    <div class="font-medium">
                                                                        {{ $row['student_name'] }}</div>
                                                                    <div class="text-xs text-gray-500">
                                                                        {{ $row['admission_no'] }}</div>
                                                                </td>
                                                                <td class="px-4 py-3 text-sm">
                                                                    @if ($row['match_type'] === 'Exact')
                                                                        <span
                                                                            class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Exact</span>
                                                                    @else
                                                                        <span
                                                                            class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">Fuzzy</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 text-sm text-gray-800">
                                                                    {{ $row['score'] }}%</td>
                                                                <td class="px-4 py-3 text-sm text-gray-800">
                                                                    @if (!empty($row['current_teacher_name']))
                                                                        @if (!empty($row['already_with_selected_teacher']))
                                                                            <span
                                                                                class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                                                                                Already with selected teacher
                                                                            </span>
                                                                        @else
                                                                            <span
                                                                                class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                                                                                Currently with
                                                                                {{ $row['current_teacher_name'] }}
                                                                            </span>
                                                                        @endif
                                                                    @else
                                                                        <span
                                                                            class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                                                            Unassigned
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            <form method="POST"
                                                action="{{ route('admin.student-subjects.import-apply') }}"
                                                class="flex justify-end">
                                                @csrf
                                                <input type="hidden" name="academic_year_id"
                                                    value="{{ $importPreview['context']['academic_year_id'] }}">
                                                <input type="hidden" name="class_id"
                                                    value="{{ $importPreview['context']['class_id'] }}">
                                                <input type="hidden" name="subject_id"
                                                    value="{{ $importPreview['context']['subject_id'] }}">
                                                <input type="hidden" name="teacher_id"
                                                    value="{{ $importPreview['context']['teacher_id'] }}">
                                                <input type="hidden" name="is_elective"
                                                    value="{{ $importPreview['context']['is_elective'] ? 1 : 0 }}">

                                                @foreach ($importPreview['matched'] as $row)
                                                    <input type="hidden" name="student_ids[]"
                                                        value="{{ $row['student_id'] }}">
                                                @endforeach

                                                <button type="submit"
                                                    class="inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                    Apply CSV Import
                                                </button>
                                            </form>
                                        @endif

                                        @if (!empty($importPreview['unmatched']))
                                            <div class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                                                <h6 class="font-semibold text-yellow-900 mb-2">Unmatched Rows</h6>
                                                <div class="space-y-2 text-sm text-yellow-800">
                                                    @foreach ($importPreview['unmatched'] as $row)
                                                        <div>
                                                            Row {{ $row['row_number'] }}:
                                                            <strong>{{ $row['surname'] }}</strong>,
                                                            <strong>{{ $row['name'] }}</strong>
                                                            — {{ $row['reason'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Manual Assignment -->
                            <form method="POST" action="{{ route('admin.student-subjects.store') }}"
                                id="assignment-form">
                                @csrf

                                <input type="hidden" name="academic_year_id" id="manual_academic_year_id"
                                    value="{{ $selectedAcademicYearId }}">
                                <input type="hidden" name="class_id" id="manual_class_id"
                                    value="{{ $selectedClassId }}">

                                <div class="mb-8">
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                                        <x-input-label :value="__('Manual Student Assignment')" />

                                        <div class="flex items-center gap-3">
                                            <input type="text" id="student-search"
                                                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder="Search student...">

                                            <button type="button" id="clear-search"
                                                class="text-sm text-gray-600 hover:text-indigo-600">
                                                Clear
                                            </button>
                                        </div>
                                    </div>

                                    <input type="hidden" name="subject_id" id="manual_subject_id"
                                        value="{{ $selectedSubjectId }}">
                                    <input type="hidden" name="teacher_id" id="manual_teacher_id"
                                        value="{{ $selectedTeacherId }}">

                                    <div class="mb-4">
                                        <label for="manual_is_elective"
                                            class="block text-sm font-medium text-gray-700">Subject Type</label>
                                        <select id="manual_is_elective" name="is_elective"
                                            class="block mt-1 w-full md:w-80 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                            <option value="0"
                                                {{ (string) $selectedIsElective === '0' ? 'selected' : '' }}>Core
                                                Subject</option>
                                            <option value="1"
                                                {{ (string) $selectedIsElective === '1' ? 'selected' : '' }}>Elective
                                                Subject</option>
                                        </select>
                                    </div>

                                    <div class="mt-2 border rounded-lg p-4 max-h-[32rem] overflow-y-auto"
                                        id="students-container">
                                        <p class="text-gray-500 text-center">Please select academic year, class,
                                            subject, and teacher first.</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <a href="{{ route('admin.student-subjects.index') }}"
                                        class="text-gray-600 hover:text-gray-800">
                                        ← Back to Assignment List
                                    </a>

                                    <button type="submit" id="submit-btn" disabled
                                        class="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Save This Group
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const academicYearSelect = document.getElementById('academic_year_id');
                const classSelect = document.getElementById('class_id');
                const subjectSelect = document.getElementById('subject_id');
                const teacherSelect = document.getElementById('teacher_id');
                const manualIsElective = document.getElementById('manual_is_elective');
                const studentsContainer = document.getElementById('students-container');
                const submitBtn = document.getElementById('submit-btn');
                const studentSearch = document.getElementById('student-search');
                const clearSearch = document.getElementById('clear-search');
                const selectionSummary = document.getElementById('selection-summary');
                const csvPreviewBtn = document.getElementById('csv-preview-btn');

                const initialAcademicYearId = @json($selectedAcademicYearId);
                const initialClassId = @json($selectedClassId);
                const initialSubjectId = @json($selectedSubjectId);
                const initialTeacherId = @json($selectedTeacherId);

                const importAcademicYear = document.getElementById('import_academic_year_id');
                const importClass = document.getElementById('import_class_id');
                const importSubject = document.getElementById('import_subject_id');
                const importTeacher = document.getElementById('import_teacher_id');
                const importIsElective = document.getElementById('import_is_elective');

                const manualAcademicYear = document.getElementById('manual_academic_year_id');
                const manualClass = document.getElementById('manual_class_id');
                const manualSubject = document.getElementById('manual_subject_id');
                const manualTeacher = document.getElementById('manual_teacher_id');

                academicYearSelect.addEventListener('change', function() {
                    const academicYearId = this.value;
                    syncContextFields();

                    if (academicYearId) {
                        loadClasses(academicYearId);
                    } else {
                        resetDropdowns();
                    }
                });

                classSelect.addEventListener('change', function() {
                    const classId = this.value;
                    const academicYearId = academicYearSelect.value;

                    syncContextFields();
                    resetSubjectTeacherAndStudents();

                    if (classId && academicYearId) {
                        loadSubjects(classId, academicYearId);
                    } else {
                        studentsContainer.innerHTML =
                            '<p class="text-gray-500 text-center">Please select academic year and class first.</p>';
                        submitBtn.disabled = true;
                    }
                });

                subjectSelect.addEventListener('change', function() {
                    const subjectId = this.value;
                    const classId = classSelect.value;
                    const academicYearId = academicYearSelect.value;

                    syncContextFields();
                    teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                    clearStudentsState();

                    if (subjectId && classId && academicYearId) {
                        loadTeachers(classId, subjectId, academicYearId);
                    } else {
                        syncContextFields();
                    }
                });

                teacherSelect.addEventListener('change', function() {
                    const teacherId = this.value;
                    const classId = classSelect.value;
                    const academicYearId = academicYearSelect.value;
                    const subjectId = subjectSelect.value;

                    syncContextFields();

                    if (teacherId && classId && academicYearId && subjectId) {
                        loadAssignmentStudents(classId, academicYearId, subjectId, teacherId);
                    } else {
                        clearStudentsState();
                    }
                });

                manualIsElective.addEventListener('change', function() {
                    importIsElective.value = this.value;
                });

                if (studentSearch) {
                    studentSearch.addEventListener('input', function() {
                        filterStudents(this.value);
                    });
                }

                if (clearSearch) {
                    clearSearch.addEventListener('click', function() {
                        studentSearch.value = '';
                        filterStudents('');
                    });
                }

                function syncContextFields() {
                    importAcademicYear.value = academicYearSelect.value;
                    importClass.value = classSelect.value;
                    importSubject.value = subjectSelect.value;
                    importTeacher.value = teacherSelect.value;
                    importIsElective.value = manualIsElective.value;

                    manualAcademicYear.value = academicYearSelect.value;
                    manualClass.value = classSelect.value;
                    manualSubject.value = subjectSelect.value;
                    manualTeacher.value = teacherSelect.value;

                    csvPreviewBtn.disabled = !(academicYearSelect.value && classSelect.value && subjectSelect.value &&
                        teacherSelect.value);
                }

                function resetDropdowns() {
                    classSelect.innerHTML = '<option value="">Select Class</option>';
                    resetSubjectTeacherAndStudents();
                    syncContextFields();
                }

                function resetSubjectTeacherAndStudents() {
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                    teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                    clearStudentsState();
                    syncContextFields();
                }

                function clearStudentsState() {
                    studentsContainer.innerHTML =
                        '<p class="text-gray-500 text-center">Select subject and teacher to load students.</p>';
                    selectionSummary.classList.add('hidden');
                    selectionSummary.innerHTML = '';
                    submitBtn.disabled = true;
                }

                function loadClasses(academicYearId) {
                    fetch(`/admin/student-subjects/classes/${academicYearId}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) throw new Error(data.error);

                            classSelect.innerHTML = '<option value="">Select Class</option>';
                            data.forEach(cls => {
                                const option = document.createElement('option');
                                option.value = cls.id;
                                option.textContent = cls.name;
                                if (String(initialClassId) === String(cls.id)) {
                                    option.selected = true;
                                }
                                classSelect.appendChild(option);
                            });

                            syncContextFields();

                            if (classSelect.value) {
                                loadSubjects(classSelect.value, academicYearId);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading classes:', error);
                            classSelect.innerHTML = '<option value="">Error loading classes</option>';
                            resetSubjectTeacherAndStudents();
                        });
                }

                function loadSubjects(classId, academicYearId) {
                    fetch(`/admin/student-subjects/subjects/${classId}/${academicYearId}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) throw new Error(data.error);

                            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                            data.forEach(subject => {
                                const option = document.createElement('option');
                                option.value = subject.id;
                                option.textContent = `${subject.name} (${subject.code})`;
                                if (String(initialSubjectId) === String(subject.id)) {
                                    option.selected = true;
                                }
                                subjectSelect.appendChild(option);
                            });

                            syncContextFields();

                            if (subjectSelect.value) {
                                loadTeachers(classId, subjectSelect.value, academicYearId);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading subjects:', error);
                            subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                        });
                }

                function loadTeachers(classId, subjectId, academicYearId) {
                    fetch(`/admin/student-subjects/teachers/${classId}/${subjectId}/${academicYearId}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) throw new Error(data.error);

                            teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                            data.forEach(teacher => {
                                const option = document.createElement('option');
                                option.value = teacher.id;
                                option.textContent = teacher.name;
                                if (String(initialTeacherId) === String(teacher.id)) {
                                    option.selected = true;
                                }
                                teacherSelect.appendChild(option);
                            });

                            syncContextFields();

                            if (teacherSelect.value) {
                                loadAssignmentStudents(classId, academicYearId, subjectId, teacherSelect.value);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading teachers:', error);
                            teacherSelect.innerHTML = '<option value="">Error loading teachers</option>';
                            clearStudentsState();
                        });
                }

                function loadAssignmentStudents(classId, academicYearId, subjectId, teacherId) {
                    fetch(
                            `/admin/student-subjects/assignment-students/${classId}/${academicYearId}/${subjectId}/${teacherId}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) throw new Error(data.error);

                            renderStudents(data);
                            renderSummary(data);
                            validateReadyToSubmit();
                        })
                        .catch(error => {
                            console.error('Error loading assignment students:', error);
                            studentsContainer.innerHTML =
                                `<p class="text-red-500 text-center">Error loading students: ${error.message}</p>`;
                            selectionSummary.classList.add('hidden');
                            submitBtn.disabled = true;
                        });
                }

                function renderStudents(data) {
                    if (!data.length) {
                        studentsContainer.innerHTML =
                            '<p class="text-gray-500 text-center">No students found in this class for the selected academic year.</p>';
                        return;
                    }

                    studentsContainer.innerHTML = '';

                    const selectedCount = data.filter(student => student.assigned_to_current_teacher).length;

                    const selectAllDiv = document.createElement('div');
                    selectAllDiv.className = 'mb-4 flex items-center justify-between border-b pb-3';
                    selectAllDiv.innerHTML = `
                        <div>
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <label for="select-all" class="ml-2 text-sm text-gray-700">Select All for this teacher group</label>
                        </div>
                        <div class="text-sm text-gray-500">
                            ${selectedCount} currently assigned to this teacher
                        </div>
                    `;
                    studentsContainer.appendChild(selectAllDiv);

                    const grid = document.createElement('div');
                    grid.className = 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3';

                    data.forEach(student => {
                        let badgeHtml =
                            '<span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Unassigned</span>';

                        if (student.assigned_teacher_name && student.assigned_to_current_teacher) {
                            badgeHtml =
                                `<span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Already with this teacher</span>`;
                        } else if (student.assigned_teacher_name) {
                            badgeHtml =
                                `<span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Currently with ${student.assigned_teacher_name}</span>`;
                        }

                        const div = document.createElement('label');
                        div.className =
                            'student-row border rounded-lg p-3 flex items-start gap-3 cursor-pointer hover:bg-gray-50';
                        div.setAttribute('data-student-text', `${student.name} ${student.admission_no}`
                            .toLowerCase());

                        div.innerHTML = `
                            <input type="checkbox"
                                   name="student_ids[]"
                                   value="${student.id}"
                                   id="student_${student.id}"
                                   class="student-checkbox mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                   ${student.assigned_to_current_teacher ? 'checked' : ''}>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900">${student.name}</div>
                                <div class="text-xs text-gray-500">${student.admission_no}</div>
                                <div class="mt-2">${badgeHtml}</div>
                            </div>
                        `;

                        grid.appendChild(div);
                    });

                    studentsContainer.appendChild(grid);

                    document.getElementById('select-all').addEventListener('change', function() {
                        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                        validateReadyToSubmit();
                    });

                    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            const total = document.querySelectorAll('.student-checkbox').length;
                            const checked = document.querySelectorAll('.student-checkbox:checked')
                                .length;
                            document.getElementById('select-all').checked = (total === checked);
                            validateReadyToSubmit();
                        });
                    });

                    const total = document.querySelectorAll('.student-checkbox').length;
                    const checked = document.querySelectorAll('.student-checkbox:checked').length;
                    document.getElementById('select-all').checked = (total > 0 && total === checked);

                    filterStudents(studentSearch.value || '');
                }

                function renderSummary(data) {
                    const alreadyWithCurrentTeacher = data.filter(student => student.assigned_to_current_teacher)
                    .length;
                    const assignedElsewhere = data.filter(student => student.assigned_teacher_name && !student
                        .assigned_to_current_teacher).length;
                    const unassigned = data.filter(student => !student.assigned_teacher_name).length;

                    selectionSummary.innerHTML = `
                        <div class="flex flex-wrap gap-4">
                            <span><strong>${alreadyWithCurrentTeacher}</strong> already with this teacher</span>
                            <span><strong>${assignedElsewhere}</strong> currently with another teacher</span>
                            <span><strong>${unassigned}</strong> unassigned for this subject</span>
                        </div>
                        <div class="mt-2 text-xs">
                            Selecting a student who is currently with another teacher will move that student to the selected teacher.
                        </div>
                    `;
                    selectionSummary.classList.remove('hidden');
                }

                function filterStudents(term) {
                    const rows = document.querySelectorAll('.student-row');
                    const searchTerm = (term || '').toLowerCase().trim();

                    rows.forEach(row => {
                        const text = row.getAttribute('data-student-text') || '';
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                }

                function validateReadyToSubmit() {
                    const hasTeacher = teacherSelect.value !== '';
                    const checkedStudents = document.querySelectorAll('.student-checkbox:checked').length;
                    submitBtn.disabled = !(hasTeacher && checkedStudents > 0);
                }

                syncContextFields();

                if (initialAcademicYearId) {
                    academicYearSelect.value = initialAcademicYearId;
                    loadClasses(initialAcademicYearId);
                }
            });
        </script>
    @endpush
</x-app-layout>
