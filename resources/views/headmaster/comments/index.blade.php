<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Headmaster Comments
            </h2>
            <p class="text-blue-100 text-sm mt-1">
                Review student performance before writing official term comments
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(!$activeAcademicYear)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    No active academic year found. Please ask the administrator to activate an academic year.
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form method="GET" action="{{ route('headmaster.comments.index') }}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="class_id" class="block text-sm font-medium text-gray-700">Class</label>
                                <select
                                    id="class_id"
                                    name="class_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ (string)$selectedClassId === (string)$class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="term_id" class="block text-sm font-medium text-gray-700">Term</label>
                                <select
                                    id="term_id"
                                    name="term_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select term</option>
                                    @foreach($terms as $term)
                                        <option value="{{ $term->id }}" {{ (string)$selectedTermId === (string)$term->id ? 'selected' : '' }}>
                                            {{ $term->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-end">
                                <button
                                    type="submit"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md"
                                >
                                    Load Students
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if($selectedClassId && $selectedTermId)
                    <div class="space-y-6">
                        @forelse($students as $student)
                            @php
                                $existingComment = $student->headmasterComments->first();
                            @endphp

                            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                                <div class="border-b bg-gray-50 px-6 py-4">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                {{ $student->user->name }}
                                            </h3>
                                            <p class="text-sm text-gray-500">
                                                Admission No: {{ $student->admission_no }} |
                                                Class: {{ $student->currentClass->name ?? 'N/A' }}
                                            </p>
                                        </div>

                                        <div class="text-left md:text-right">
                                            <p class="text-sm text-gray-500">Student Average</p>
                                            <p class="text-2xl font-bold {{ $student->student_average !== null && $student->student_average < 40 ? 'text-red-600' : 'text-indigo-600' }}">
                                                {{ $student->student_average !== null ? number_format($student->student_average, 2) . '%' : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                        <div class="bg-blue-50 rounded-lg p-4">
                                            <h4 class="text-sm font-semibold text-blue-900 mb-3">Student Summary</h4>
                                            <div class="space-y-2 text-sm text-gray-700">
                                                <p><span class="font-medium">Full Name:</span> {{ $student->user->name }}</p>
                                                <p><span class="font-medium">Admission No:</span> {{ $student->admission_no }}</p>
                                                <p><span class="font-medium">Class:</span> {{ $student->currentClass->name ?? 'N/A' }}</p>
                                                <p><span class="font-medium">Performance Level:</span>
                                                    @if($student->student_average === null)
                                                        No marks available
                                                    @elseif($student->student_average >= 80)
                                                        Excellent
                                                    @elseif($student->student_average >= 70)
                                                        Very Good
                                                    @elseif($student->student_average >= 60)
                                                        Good
                                                    @elseif($student->student_average >= 50)
                                                        Fair
                                                    @else
                                                        Needs Improvement
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <div class="bg-green-50 rounded-lg p-4">
                                            <h4 class="text-sm font-semibold text-green-900 mb-3">Strongest Subjects</h4>
                                            @if($student->top_subjects->count())
                                                <div class="space-y-2">
                                                    @foreach($student->top_subjects as $subject)
                                                        <div class="flex items-center justify-between text-sm">
                                                            <span class="text-gray-800">{{ $subject['subject_name'] }}</span>
                                                            <span class="font-semibold text-green-700">
                                                                {{ number_format($subject['average'], 2) }}%
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500">No performance data available.</p>
                                            @endif
                                        </div>

                                        <div class="bg-red-50 rounded-lg p-4">
                                            <h4 class="text-sm font-semibold text-red-900 mb-3">Weakest Subjects</h4>
                                            @if($student->weak_subjects->count())
                                                <div class="space-y-2">
                                                    @foreach($student->weak_subjects as $subject)
                                                        <div class="flex items-center justify-between text-sm">
                                                            <span class="text-gray-800">{{ $subject['subject_name'] }}</span>
                                                            <span class="font-semibold text-red-700">
                                                                {{ number_format($subject['average'], 2) }}%
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500">No performance data available.</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="bg-white border rounded-lg p-4">
                                        <h4 class="text-sm font-semibold text-gray-900 mb-4">Subject Performance Breakdown</h4>

                                        @if($student->subject_breakdown->count())
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Midterm</th>
                                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Endterm</th>
                                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Average</th>
                                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher Remark</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200">
                                                        @foreach($student->subject_breakdown as $row)
                                                            <tr>
                                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row['subject_name'] }}</td>
                                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                                    {{ $row['midterm_score'] !== null ? number_format($row['midterm_score'], 2) : '—' }}
                                                                </td>
                                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                                    {{ $row['endterm_score'] !== null ? number_format($row['endterm_score'], 2) : '—' }}
                                                                </td>
                                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                                    {{ $row['average'] !== null ? number_format($row['average'], 2) . '%' : '—' }}
                                                                </td>
                                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row['grade'] ?? '—' }}</td>
                                                                <td class="px-4 py-3 text-sm text-gray-600">{{ $row['remarks'] ?? '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500">No marks available for this student in the selected term.</p>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-100">
                                            <h4 class="text-sm font-semibold text-yellow-900 mb-2">Attendance Information</h4>
                                            <p class="text-sm text-gray-600">
                                                Attendance summary will appear here once the attendance module is connected to the headmaster dashboard.
                                            </p>
                                        </div>

                                        <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                                            <h4 class="text-sm font-semibold text-purple-900 mb-2">Behaviour Information</h4>
                                            <p class="text-sm text-gray-600">
                                                Behaviour/discipline summary will appear here once the behaviour module is implemented.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="border rounded-lg p-4">
                                        <h4 class="text-sm font-semibold text-gray-900 mb-4">Official Headmaster Comment</h4>

                                        <form method="POST" action="{{ route('headmaster.comments.store') }}">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">

                                            <div>
                                                <textarea
                                                    name="comment"
                                                    rows="5"
                                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    placeholder="Write the official headmaster comment based on performance, conduct, attendance and general progress..."
                                                    required
                                                >{{ old('student_id') == $student->id ? old('comment') : ($existingComment->comment ?? '') }}</textarea>

                                                @if($errors->any() && old('student_id') == $student->id)
                                                    <div class="mt-2 text-sm text-red-600">
                                                        @foreach($errors->all() as $error)
                                                            <p>{{ $error }}</p>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="mt-4 flex justify-end">
                                                <button
                                                    type="submit"
                                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md"
                                                >
                                                    {{ $existingComment ? 'Update Comment' : 'Save Comment' }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="bg-white shadow-sm rounded-lg p-6">
                                <p class="text-gray-500">No students found for the selected class.</p>
                            </div>
                        @endforelse
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>