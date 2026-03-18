<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Assign Students to Subjects
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Assign Students to Subjects</h3>
                        <p class="text-gray-600">Select students and assign them to a subject under a specific teacher.
                        </p>
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

                    <form method="POST" action="{{ route('admin.student-subjects.store') }}" id="assignment-form">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Academic Year</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('academic_year_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="class_id" :value="__('Class')" />
                                <select id="class_id" name="class_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Class</option>
                                </select>
                                <x-input-error :messages="$errors->get('class_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="subject_id" :value="__('Subject')" />
                                <select id="subject_id" name="subject_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Subject</option>
                                </select>
                                <x-input-error :messages="$errors->get('subject_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="teacher_id" :value="__('Teacher')" />
                                <select id="teacher_id" name="teacher_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Teacher</option>
                                </select>
                                <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="is_elective" :value="__('Subject Type')" />
                                <select id="is_elective" name="is_elective"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="0" {{ old('is_elective') == '0' ? 'selected' : '' }}>Core
                                        Subject</option>
                                    <option value="1" {{ old('is_elective') == '1' ? 'selected' : '' }}>Elective
                                        Subject</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-8">
                            <x-input-label :value="__('Students')" />
                            <div class="mt-2 border rounded-lg p-4 max-h-96 overflow-y-auto" id="students-container">
                                <p class="text-gray-500 text-center">Please select academic year and class first</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('admin.student-subjects.index') }}"
                                class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>

                            <x-primary-button id="submit-btn" disabled>
                                {{ __('Assign Students') }}
                            </x-primary-button>
                        </div>
                    </form>
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
                const studentsContainer = document.getElementById('students-container');
                const submitBtn = document.getElementById('submit-btn');

                academicYearSelect.addEventListener('change', function() {
                    const academicYearId = this.value;
                    if (academicYearId) {
                        loadClasses(academicYearId);
                    } else {
                        resetDropdowns();
                    }
                });

                classSelect.addEventListener('change', function() {
                    const classId = this.value;
                    const academicYearId = academicYearSelect.value;

                    teacherSelect.innerHTML = '<option value="">Select Teacher</option>';

                    if (classId && academicYearId) {
                        loadSubjects(classId, academicYearId);
                        loadStudents(classId, academicYearId);
                    } else {
                        subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                        teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                        studentsContainer.innerHTML =
                            '<p class="text-gray-500 text-center">Please select academic year and class first</p>';
                        submitBtn.disabled = true;
                    }
                });

                subjectSelect.addEventListener('change', function() {
                    const subjectId = this.value;
                    const classId = classSelect.value;
                    const academicYearId = academicYearSelect.value;

                    if (subjectId && classId && academicYearId) {
                        loadTeachers(classId, subjectId, academicYearId);
                    } else {
                        teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                    }
                });

                teacherSelect.addEventListener('change', function() {
                    validateReadyToSubmit();
                });

                function resetDropdowns() {
                    classSelect.innerHTML = '<option value="">Select Class</option>';
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                    teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                    studentsContainer.innerHTML =
                        '<p class="text-gray-500 text-center">Please select academic year and class first</p>';
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
                                classSelect.appendChild(option);
                            });

                            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                            teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                            studentsContainer.innerHTML =
                                '<p class="text-gray-500 text-center">Please select a class</p>';
                            submitBtn.disabled = true;
                        })
                        .catch(error => {
                            console.error('Error loading classes:', error);
                            classSelect.innerHTML = '<option value="">Error loading classes</option>';
                            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                            teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                            studentsContainer.innerHTML =
                                '<p class="text-red-500 text-center">Error loading classes</p>';
                            submitBtn.disabled = true;
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
                                option.textContent = subject.name + ' (' + subject.code + ')';
                                subjectSelect.appendChild(option);
                            });
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
                                teacherSelect.appendChild(option);
                            });

                            validateReadyToSubmit();
                        })
                        .catch(error => {
                            console.error('Error loading teachers:', error);
                            teacherSelect.innerHTML = '<option value="">Error loading teachers</option>';
                            submitBtn.disabled = true;
                        });
                }

                function loadStudents(classId, academicYearId) {
                    fetch(`/admin/student-subjects/students/${classId}/${academicYearId}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) throw new Error(data.error);

                            if (data.length > 0) {
                                studentsContainer.innerHTML = '';

                                const grid = document.createElement('div');
                                grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3';

                                data.forEach(student => {
                                    const div = document.createElement('div');
                                    div.className = 'flex items-center';
                                    div.innerHTML = `
                                <input type="checkbox"
                                       name="student_ids[]"
                                       value="${student.id}"
                                       id="student_${student.id}"
                                       class="student-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <label for="student_${student.id}" class="ml-2 block text-sm text-gray-900">
                                    ${student.name} (${student.admission_no})
                                </label>
                            `;
                                    grid.appendChild(div);
                                });

                                studentsContainer.appendChild(grid);

                                const selectAllDiv = document.createElement('div');
                                selectAllDiv.className = 'mb-3';
                                selectAllDiv.innerHTML = `
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <label for="select-all" class="ml-2 text-sm text-gray-700">Select All</label>
                        `;
                                studentsContainer.insertBefore(selectAllDiv, grid);

                                document.getElementById('select-all').addEventListener('change', function() {
                                    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                                        checkbox.checked = this.checked;
                                    });
                                    validateReadyToSubmit();
                                });

                                document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                                    checkbox.addEventListener('change', function() {
                                        const total = document.querySelectorAll('.student-checkbox')
                                            .length;
                                        const checked = document.querySelectorAll(
                                            '.student-checkbox:checked').length;
                                        document.getElementById('select-all').checked = (total ===
                                            checked);
                                        validateReadyToSubmit();
                                    });
                                });

                                validateReadyToSubmit();
                            } else {
                                studentsContainer.innerHTML =
                                    '<p class="text-gray-500 text-center">No students found in this class for the selected academic year</p>';
                                submitBtn.disabled = true;
                            }
                        })
                        .catch(error => {
                            console.error('Error loading students:', error);
                            studentsContainer.innerHTML =
                                '<p class="text-red-500 text-center">Error loading students: ' + error.message +
                                '</p>';
                            submitBtn.disabled = true;
                        });
                }

                function validateReadyToSubmit() {
                    const hasTeacher = teacherSelect.value !== '';
                    const checkedStudents = document.querySelectorAll('.student-checkbox:checked').length;
                    submitBtn.disabled = !(hasTeacher && checkedStudents > 0);
                }
            });
        </script>
    @endpush
</x-app-layout>
