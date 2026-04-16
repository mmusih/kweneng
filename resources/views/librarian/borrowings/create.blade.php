<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Library Issue / Return
                    </h2>
                    <p class="text-emerald-100 text-sm mt-1">
                        Scan barcode or type it manually. Select class, then student for student borrowing.
                    </p>
                </div>

                <a href="{{ route('librarian.dashboard') }}"
                    class="inline-flex items-center text-white hover:text-emerald-100 text-sm font-medium">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg">
                    <div class="font-semibold mb-2">Please fix the following:</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                {{-- ISSUE BOOK --}}
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Issue Book</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Choose borrower, scan barcode, confirm dates, then issue.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('librarian.borrowings.issue') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="borrower_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Borrower Type
                            </label>
                            <select id="borrower_type" name="borrower_type"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="student"
                                    {{ old('borrower_type', 'student') === 'student' ? 'selected' : '' }}>Student
                                </option>
                                <option value="teacher" {{ old('borrower_type') === 'teacher' ? 'selected' : '' }}>
                                    Teacher</option>
                            </select>
                        </div>

                        {{-- STUDENT SELECTOR --}}
                        <div id="student-borrower-panel" class="space-y-4">
                            <div>
                                <label for="class_id_selector" class="block text-sm font-medium text-gray-700 mb-1">
                                    Class
                                </label>
                                <select id="class_id_selector"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">Select class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}">
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Student
                                </label>
                                <select id="student_id" name="student_id"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">Select student</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    Select a class first to load students.
                                </p>
                            </div>
                        </div>

                        {{-- TEACHER SEARCH --}}
                        <div id="teacher-borrower-panel" class="space-y-4 hidden">
                            <div>
                                <label for="teacher_search" class="block text-sm font-medium text-gray-700 mb-1">
                                    Search Teacher
                                </label>
                                <input type="text" id="teacher_search" autocomplete="off"
                                    placeholder="Type teacher name..."
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">

                                <input type="hidden" id="teacher_id" name="teacher_id" value="{{ old('teacher_id') }}">

                                <div id="teacher_results"
                                    class="hidden mt-2 border border-gray-200 rounded-md bg-white shadow-sm max-h-56 overflow-y-auto">
                                </div>

                                <p id="teacher_selected_label" class="text-xs text-emerald-700 mt-2 hidden"></p>
                            </div>
                        </div>

                        <div>
                            <label for="issue_barcode" class="block text-sm font-medium text-gray-700 mb-1">
                                Book Barcode
                            </label>
                            <input type="text" id="issue_barcode" name="barcode" value="{{ old('barcode') }}"
                                placeholder="Scan or type barcode"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div id="book-copy-preview" class="hidden rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">Book Copy</h4>
                            <div class="text-sm text-gray-700 space-y-1">
                                <p><span class="font-medium">Title:</span> <span id="copy_title"></span></p>
                                <p><span class="font-medium">Author:</span> <span id="copy_author"></span></p>
                                <p><span class="font-medium">Barcode:</span> <span id="copy_barcode"></span></p>
                                <p><span class="font-medium">Accession No:</span> <span id="copy_accession"></span></p>
                                <p><span class="font-medium">Shelf:</span> <span id="copy_shelf"></span></p>
                                <p><span class="font-medium">Status:</span> <span id="copy_status"></span></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="issued_at" class="block text-sm font-medium text-gray-700 mb-1">
                                    Issued Date
                                </label>
                                <input type="date" id="issued_at" name="issued_at"
                                    value="{{ old('issued_at', now()->toDateString()) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label for="due_at" class="block text-sm font-medium text-gray-700 mb-1">
                                    Due Date
                                </label>
                                <input type="date" id="due_at" name="due_at"
                                    value="{{ old('due_at', now()->addDays(14)->toDateString()) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div>
                            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-1">
                                Remarks
                            </label>
                            <textarea id="remarks" name="remarks" rows="3"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Optional notes...">{{ old('remarks') }}</textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-md shadow-sm">
                                Issue Book
                            </button>
                        </div>
                    </form>
                </div>

                {{-- RETURN BOOK --}}
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Return Book</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Scan or type the barcode to return a book.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('librarian.borrowings.return') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="return_barcode" class="block text-sm font-medium text-gray-700 mb-1">
                                Book Barcode
                            </label>
                            <input type="text" id="return_barcode" name="barcode"
                                placeholder="Scan or type barcode"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div id="return-copy-preview" class="hidden rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">Current Borrowing</h4>
                            <div class="text-sm text-gray-700 space-y-1">
                                <p><span class="font-medium">Title:</span> <span id="return_copy_title"></span></p>
                                <p><span class="font-medium">Borrower:</span> <span id="return_borrower_name"></span>
                                </p>
                                <p><span class="font-medium">Borrower Type:</span> <span
                                        id="return_borrower_type"></span></p>
                                <p><span class="font-medium">Issued:</span> <span id="return_issued_at"></span></p>
                                <p><span class="font-medium">Due:</span> <span id="return_due_at"></span></p>
                                <p><span class="font-medium">Status:</span> <span id="return_status"></span></p>
                            </div>
                        </div>

                        <div>
                            <label for="returned_at" class="block text-sm font-medium text-gray-700 mb-1">
                                Returned Date
                            </label>
                            <input type="date" id="returned_at" name="returned_at"
                                value="{{ now()->toDateString() }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div class="pt-2 flex flex-wrap gap-3">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md shadow-sm">
                                Return Book
                            </button>

                            <a href="{{ route('librarian.borrowings.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold rounded-md shadow-sm">
                                View Borrowings
                            </a>
                        </div>
                    </form>

                    <div class="mt-8 border-t pt-6">
                        <form method="POST" action="{{ route('librarian.borrowings.mark-overdue') }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-md shadow-sm">
                                Mark Overdue Borrowings
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const borrowerType = document.getElementById('borrower_type');
            const studentPanel = document.getElementById('student-borrower-panel');
            const teacherPanel = document.getElementById('teacher-borrower-panel');

            const classSelector = document.getElementById('class_id_selector');
            const studentSelect = document.getElementById('student_id');

            const teacherSearch = document.getElementById('teacher_search');
            const teacherId = document.getElementById('teacher_id');
            const teacherResults = document.getElementById('teacher_results');
            const teacherSelectedLabel = document.getElementById('teacher_selected_label');

            const issueBarcode = document.getElementById('issue_barcode');
            const returnBarcode = document.getElementById('return_barcode');

            const issuePreview = document.getElementById('book-copy-preview');
            const returnPreview = document.getElementById('return-copy-preview');

            let teacherSearchTimeout = null;

            function updateBorrowerPanels() {
                const type = borrowerType.value;

                if (type === 'student') {
                    studentPanel.classList.remove('hidden');
                    teacherPanel.classList.add('hidden');
                    teacherId.value = '';
                    teacherSearch.value = '';
                    teacherResults.innerHTML = '';
                    teacherResults.classList.add('hidden');
                    teacherSelectedLabel.classList.add('hidden');
                    teacherSelectedLabel.textContent = '';
                } else {
                    teacherPanel.classList.remove('hidden');
                    studentPanel.classList.add('hidden');
                    studentSelect.value = '';
                }
            }

            async function loadStudentsByClass(classId) {
                studentSelect.innerHTML = '<option value="">Loading students...</option>';

                if (!classId) {
                    studentSelect.innerHTML = '<option value="">Select student</option>';
                    return;
                }

                try {
                    const response = await fetch(
                        `{{ url('/librarian/borrowings/classes') }}/${classId}/students`);
                    const data = await response.json();

                    studentSelect.innerHTML = '<option value="">Select student</option>';

                    (data.students || []).forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.id;
                        option.textContent = `${student.name} (${student.admission_no})`;
                        studentSelect.appendChild(option);
                    });
                } catch (error) {
                    studentSelect.innerHTML = '<option value="">Failed to load students</option>';
                }
            }

            async function searchTeachers(query) {
                if (!query || query.trim().length < 2) {
                    teacherResults.classList.add('hidden');
                    teacherResults.innerHTML = '';
                    return;
                }

                try {
                    const url = new URL(`{{ route('librarian.borrowings.search-teachers') }}`);
                    url.searchParams.set('q', query);

                    const response = await fetch(url.toString());
                    const data = await response.json();

                    teacherResults.innerHTML = '';

                    if (!data.teachers || data.teachers.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'px-3 py-2 text-sm text-gray-500';
                        empty.textContent = 'No teachers found';
                        teacherResults.appendChild(empty);
                    } else {
                        data.teachers.forEach(teacher => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-50';
                            button.textContent = teacher.name;
                            button.addEventListener('click', function() {
                                teacherId.value = teacher.id;
                                teacherSearch.value = teacher.name;
                                teacherSelectedLabel.textContent = `Selected: ${teacher.name}`;
                                teacherSelectedLabel.classList.remove('hidden');
                                teacherResults.classList.add('hidden');
                            });
                            teacherResults.appendChild(button);
                        });
                    }

                    teacherResults.classList.remove('hidden');
                } catch (error) {
                    teacherResults.innerHTML =
                    '<div class="px-3 py-2 text-sm text-red-500">Search failed</div>';
                    teacherResults.classList.remove('hidden');
                }
            }

            function populateIssuePreview(data) {
                const copy = data.copy;
                issuePreview.classList.remove('hidden');

                document.getElementById('copy_title').textContent = copy.book.title || 'N/A';
                document.getElementById('copy_author').textContent = copy.book.author || 'N/A';
                document.getElementById('copy_barcode').textContent = copy.barcode || 'N/A';
                document.getElementById('copy_accession').textContent = copy.accession_no || 'N/A';
                document.getElementById('copy_shelf').textContent = copy.shelf_location || 'N/A';
                document.getElementById('copy_status').textContent = copy.status || 'N/A';
            }

            function populateReturnPreview(data) {
                const copy = data.copy;
                returnPreview.classList.remove('hidden');

                document.getElementById('return_copy_title').textContent = copy.book.title || 'N/A';
                document.getElementById('return_borrower_name').textContent = copy.active_borrowing
                    ?.borrower_name || 'N/A';
                document.getElementById('return_borrower_type').textContent = copy.active_borrowing
                    ?.borrower_type || 'N/A';
                document.getElementById('return_issued_at').textContent = copy.active_borrowing?.issued_at || 'N/A';
                document.getElementById('return_due_at').textContent = copy.active_borrowing?.due_at || 'N/A';
                document.getElementById('return_status').textContent = copy.active_borrowing?.status || copy
                    .status || 'N/A';
            }

            async function lookupBarcode(barcode, mode = 'issue') {
                if (!barcode || barcode.trim() === '') {
                    if (mode === 'issue') issuePreview.classList.add('hidden');
                    if (mode === 'return') returnPreview.classList.add('hidden');
                    return;
                }

                try {
                    const url = new URL(`{{ route('librarian.borrowings.lookup-book-copy') }}`);
                    url.searchParams.set('barcode', barcode);

                    const response = await fetch(url.toString());
                    const data = await response.json();

                    if (!data.found) {
                        if (mode === 'issue') issuePreview.classList.add('hidden');
                        if (mode === 'return') returnPreview.classList.add('hidden');
                        return;
                    }

                    if (mode === 'issue') populateIssuePreview(data);
                    if (mode === 'return') populateReturnPreview(data);
                } catch (error) {
                    if (mode === 'issue') issuePreview.classList.add('hidden');
                    if (mode === 'return') returnPreview.classList.add('hidden');
                }
            }

            borrowerType.addEventListener('change', updateBorrowerPanels);

            classSelector.addEventListener('change', function() {
                loadStudentsByClass(this.value);
            });

            teacherSearch.addEventListener('input', function() {
                teacherId.value = '';
                teacherSelectedLabel.classList.add('hidden');
                teacherSelectedLabel.textContent = '';

                clearTimeout(teacherSearchTimeout);
                teacherSearchTimeout = setTimeout(() => {
                    searchTeachers(this.value);
                }, 250);
            });

            issueBarcode.addEventListener('change', function() {
                lookupBarcode(this.value, 'issue');
            });

            issueBarcode.addEventListener('blur', function() {
                lookupBarcode(this.value, 'issue');
            });

            returnBarcode.addEventListener('change', function() {
                lookupBarcode(this.value, 'return');
            });

            returnBarcode.addEventListener('blur', function() {
                lookupBarcode(this.value, 'return');
            });

            document.addEventListener('click', function(event) {
                if (!teacherResults.contains(event.target) && event.target !== teacherSearch) {
                    teacherResults.classList.add('hidden');
                }
            });

            updateBorrowerPanels();
        });
    </script>
</x-app-layout>
