<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Manage Marks
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 text-sm text-gray-600">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600">Dashboard</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-800 font-medium">Manage Marks</span>
                    </div>

                    <div class="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-2xl font-semibold">Marks Management</h3>
                            <p class="text-gray-600">View, manage, and bulk import student marks from class summary CSV
                                files.</p>
                        </div>
                        <a href="{{ route('admin.dashboard') }}"
                            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium whitespace-nowrap">
                            ← Back to Dashboard
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- CSV Import -->
                    <div class="mb-8 rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                        <div class="mb-4">
                            <h4 class="text-lg font-semibold text-emerald-900">Bulk Import from Class Summary CSV</h4>
                            <p class="text-sm text-emerald-800 mt-1">
                                Upload one class summary sheet and preview either midterm or endterm marks before saving
                                them.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('admin.marks.import-preview') }}"
                            enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            @csrf

                            <div>
                                <x-input-label for="import_academic_year_id" :value="__('Academic Year')" />
                                <select id="import_academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Academic Year</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="import_term_id" :value="__('Term')" />
                                <select id="import_term_id" name="term_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Term</option>
                                    @if (request('academic_year_id'))
                                        @php
                                            $importTerms = \App\Models\Term::where(
                                                'academic_year_id',
                                                request('academic_year_id'),
                                            )->get();
                                        @endphp
                                        @foreach ($importTerms as $term)
                                            <option value="{{ $term->id }}"
                                                {{ request('term_id') == $term->id ? 'selected' : '' }}>
                                                {{ $term->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div>
                                <x-input-label for="import_class_id" :value="__('Class')" />
                                <select id="import_class_id" name="class_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="assessment_type" :value="__('Assessment Type')" />
                                <select id="assessment_type" name="assessment_type"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Type</option>
                                    <option value="midterm">Midterm</option>
                                    <option value="endterm">Endterm</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="csv_file" :value="__('CSV File')" />
                                <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt"
                                    class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            <div class="md:col-span-5 flex justify-between items-center">
                                <a href="{{ route('admin.dashboard') }}"
                                    class="text-sm text-gray-600 hover:text-indigo-600">
                                    ← Back to Dashboard
                                </a>

                                <button type="submit"
                                    class="inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Preview CSV Import
                                </button>
                            </div>
                        </form>

                        @if (!empty($marksImportPreview))
                            <div class="mt-6 rounded-xl border border-emerald-300 bg-white p-4">
                                <div class="mb-4">
                                    <h5 class="text-base font-semibold text-gray-900">Import Preview</h5>
                                    <p class="text-sm text-gray-600">
                                        Review the grouped preview below before applying the import.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                    <div class="rounded-lg bg-green-50 border border-green-200 p-4">
                                        <div class="text-sm text-green-700">Matched Students</div>
                                        <div class="text-2xl font-bold text-green-800">
                                            {{ $marksImportPreview['matched_students_count'] ?? 0 }}</div>
                                    </div>

                                    <div class="rounded-lg bg-blue-50 border border-blue-200 p-4">
                                        <div class="text-sm text-blue-700">Matched Subject Marks</div>
                                        <div class="text-2xl font-bold text-blue-800">
                                            {{ $marksImportPreview['matched_cells_count'] ?? 0 }}</div>
                                    </div>

                                    <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                                        <div class="text-sm text-yellow-700">Issues</div>
                                        <div class="text-2xl font-bold text-yellow-800">
                                            {{ count($marksImportPreview['issues'] ?? []) }}</div>
                                    </div>

                                    <div class="rounded-lg bg-purple-50 border border-purple-200 p-4">
                                        <div class="text-sm text-purple-700">Unknown Subject Codes</div>
                                        <div class="text-2xl font-bold text-purple-800">
                                            {{ count($marksImportPreview['unknown_subject_codes'] ?? []) }}</div>
                                    </div>
                                </div>

                                <div class="mb-4 text-sm text-gray-700">
                                    <strong>Academic Year:</strong>
                                    {{ $marksImportPreview['context']['academic_year_name'] ?? '' }}
                                    <span class="mx-2">|</span>
                                    <strong>Term:</strong> {{ $marksImportPreview['context']['term_name'] ?? '' }}
                                    <span class="mx-2">|</span>
                                    <strong>Class:</strong> {{ $marksImportPreview['context']['class_name'] ?? '' }}
                                    <span class="mx-2">|</span>
                                    <strong>Assessment:</strong>
                                    {{ ucfirst($marksImportPreview['context']['assessment_type'] ?? '') }}
                                </div>

                                @if (!empty($marksImportPreview['unknown_subject_codes']))
                                    <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                                        <h6 class="font-semibold text-yellow-900 mb-2">Unknown Subject Codes</h6>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($marksImportPreview['unknown_subject_codes'] as $code)
                                                <span
                                                    class="inline-flex rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">
                                                    {{ $code }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if (!empty($marksImportPreview['issues']))
                                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4">
                                        <h6 class="font-semibold text-red-900 mb-2">Import Issues</h6>
                                        <div class="space-y-2 text-sm text-red-800 max-h-56 overflow-y-auto">
                                            @foreach ($marksImportPreview['issues'] as $issue)
                                                <div>
                                                    Row {{ $issue['row_number'] }} — {{ $issue['message'] }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if (!empty($marksImportPreview['students']))
                                    <div class="space-y-4 mb-4">
                                        @foreach ($marksImportPreview['students'] as $studentRow)
                                            <div class="rounded-lg border border-gray-200 overflow-hidden">
                                                <div
                                                    class="bg-gray-50 px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                                    <div>
                                                        <div class="font-semibold text-gray-900">
                                                            {{ $studentRow['student_name'] }}</div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ $studentRow['admission_no'] }}</div>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        CSV row(s): {{ implode(', ', $studentRow['row_numbers']) }}
                                                    </div>
                                                </div>

                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full divide-y divide-gray-200">
                                                        <thead class="bg-white">
                                                            <tr>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-600">
                                                                    Subject</th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-600">
                                                                    Score</th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-bold uppercase text-gray-600">
                                                                    Grade</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100 bg-white">
                                                            @foreach ($studentRow['subjects'] as $subjectRow)
                                                                <tr>
                                                                    <td class="px-4 py-3 text-sm text-gray-900">
                                                                        {{ $subjectRow['subject_code'] }} -
                                                                        {{ $subjectRow['subject_name'] }}
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm text-gray-800">
                                                                        {{ $subjectRow['score'] }}
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm text-gray-800">
                                                                        {{ $subjectRow['grade'] ?? '—' }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <form method="POST" action="{{ route('admin.marks.import-apply') }}"
                                        class="flex justify-between items-center">
                                        @csrf
                                        <input type="hidden" name="academic_year_id"
                                            value="{{ $marksImportPreview['context']['academic_year_id'] }}">
                                        <input type="hidden" name="term_id"
                                            value="{{ $marksImportPreview['context']['term_id'] }}">
                                        <input type="hidden" name="class_id"
                                            value="{{ $marksImportPreview['context']['class_id'] }}">
                                        <input type="hidden" name="assessment_type"
                                            value="{{ $marksImportPreview['context']['assessment_type'] }}">

                                        <a href="{{ route('admin.marks.index') }}"
                                            class="text-sm text-gray-600 hover:text-indigo-600">
                                            ← Back to Marks List
                                        </a>

                                        <button type="submit"
                                            class="inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            Apply Import
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Filters -->
                    <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                        <h4 class="text-lg font-semibold mb-4">Filter Marks</h4>
                        <form method="GET" action="{{ route('admin.marks.index') }}"
                            class="grid grid-cols-1 md:grid-cols-3 gap-4" id="marks-filter-form">
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Academic Years</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="term_id" :value="__('Term')" />
                                <select id="term_id" name="term_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Terms</option>
                                    @if (request('academic_year_id'))
                                        @php
                                            $terms = \App\Models\Term::where(
                                                'academic_year_id',
                                                request('academic_year_id'),
                                            )->get();
                                        @endphp
                                        @foreach ($terms as $term)
                                            <option value="{{ $term->id }}"
                                                {{ request('term_id') == $term->id ? 'selected' : '' }}>
                                                {{ $term->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div>
                                <x-input-label for="class_id" :value="__('Class')" />
                                <select id="class_id" name="class_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Classes</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="subject_id" :value="__('Subject')" />
                                <select id="subject_id" name="subject_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Subjects</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}"
                                            {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }} ({{ $subject->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="teacher_id" :value="__('Teacher')" />
                                <select id="teacher_id" name="teacher_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">All Teachers</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}"
                                            {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-end">
                                <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                                    Filter
                                </button>
                                <a href="{{ route('admin.marks.index') }}"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    Clear
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Marks Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Subject</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Term</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Midterm</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Endterm</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Grade</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Teacher</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($marks as $mark)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $mark->student->user->name ?? 'Unknown Student' }}</div>
                                            <div class="text-sm text-gray-500">
                                                {{ $mark->student->admission_no ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $mark->subject->name ?? 'Unknown Subject' }}
                                            ({{ $mark->subject->code ?? 'N/A' }})
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $mark->class->name ?? 'Unknown Class' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $mark->term->name ?? 'Unknown Term' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $mark->midterm_score ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $mark->endterm_score ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($mark->grade)
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @switch($mark->grade)
                                                    @case('A*') bg-green-100 text-green-800 @break
                                                    @case('A') bg-green-100 text-green-800 @break
                                                    @case('B') bg-blue-100 text-blue-800 @break
                                                    @case('C') bg-yellow-100 text-yellow-800 @break
                                                    @case('D') bg-orange-100 text-orange-800 @break
                                                    @case('E') bg-red-100 text-red-800 @break
                                                    @case('F') bg-red-100 text-red-800 @break
                                                    @default bg-gray-100 text-gray-800
                                                @endswitch">
                                                    {{ $mark->grade }}
                                                </span>
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $mark->teacher->user->name ?? 'Unknown Teacher' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.marks.show', $mark) }}"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                View
                                            </a>
                                            <a href="{{ route('admin.marks.edit', $mark) }}"
                                                class="text-blue-600 hover:text-blue-900 mr-3">
                                                Edit
                                            </a>
                                            <form action="{{ route('admin.marks.destroy', $mark) }}" method="POST"
                                                class="inline"
                                                onsubmit="return confirm('Are you sure you want to delete this mark?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No marks found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $marks->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const academicYearSelect = document.getElementById('academic_year_id');
                const termSelect = document.getElementById('term_id');
                const importAcademicYearSelect = document.getElementById('import_academic_year_id');
                const importTermSelect = document.getElementById('import_term_id');

                if (academicYearSelect) {
                    academicYearSelect.addEventListener('change', function() {
                        const academicYearId = this.value;

                        if (academicYearId) {
                            loadTerms(academicYearId, termSelect, false);
                        } else {
                            termSelect.innerHTML = '<option value="">All Terms</option>';
                        }
                    });
                }

                if (importAcademicYearSelect) {
                    importAcademicYearSelect.addEventListener('change', function() {
                        const academicYearId = this.value;

                        if (academicYearId) {
                            loadTerms(academicYearId, importTermSelect, true);
                        } else {
                            importTermSelect.innerHTML = '<option value="">Select Term</option>';
                        }
                    });
                }

                function loadTerms(academicYearId, targetSelect, importMode = false) {
                    targetSelect.innerHTML = '<option value="">Loading...</option>';

                    fetch(`/admin/terms/by-academic-year/${academicYearId}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            targetSelect.innerHTML = importMode ?
                                '<option value="">Select Term</option>' :
                                '<option value="">All Terms</option>';

                            if (!Array.isArray(data) || data.length === 0) {
                                targetSelect.innerHTML = importMode ?
                                    '<option value="">No Terms Found</option>' :
                                    '<option value="">No Terms Found</option>';
                                return;
                            }

                            data.forEach(term => {
                                const option = document.createElement('option');
                                option.value = term.id;
                                option.textContent = term.name;
                                targetSelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Error loading terms:', error);
                            targetSelect.innerHTML = '<option value="">Error loading terms</option>';
                        });
                }
            });
        </script>
    @endpush
</x-app-layout>
