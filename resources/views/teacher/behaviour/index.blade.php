<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-rose-500 to-pink-600 rounded-lg shadow-lg flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Behaviour Records
                </h2>
                <p class="text-rose-100 text-sm mt-1">
                    Record behaviour incidents for your class
                </p>
            </div>
            <a href="{{ route('teacher.dashboard') }}" class="text-white hover:text-rose-100 text-sm font-medium">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (!$activeAcademicYear)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    No active academic year found. Please ask the administrator to activate an academic year.
                </div>
            @elseif(!$activeTerm)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    No active term found. Behaviour cannot be recorded until a term is active.
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form method="GET" action="{{ route('teacher.behaviour.index') }}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="class_id" class="block text-sm font-medium text-gray-700">Class</label>
                                <select id="class_id" name="class_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                    required>
                                    <option value="">Select class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ (string) $selectedClassId === (string) $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="student_id" class="block text-sm font-medium text-gray-700">Student</label>
                                <select id="student_id" name="student_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500">
                                    <option value="">All students</option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}"
                                            {{ (string) $selectedStudentId === (string) $student->id ? 'selected' : '' }}>
                                            {{ $student->admission_no }} - {{ $student->user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-end">
                                <button type="submit"
                                    class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2 px-4 rounded-md">
                                    Load Records
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if ($selectedClassId)
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                New Behaviour Record
                            </h3>

                            <form method="POST" action="{{ route('teacher.behaviour.store') }}">
                                @csrf

                                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">

                                <div class="space-y-4">
                                    <div>
                                        <label for="record_date"
                                            class="block text-sm font-medium text-gray-700">Date</label>
                                        <input type="date" id="record_date" name="record_date"
                                            value="{{ old('record_date', $selectedDate) }}"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                            required>
                                    </div>

                                    <div>
                                        <label for="student_id_form"
                                            class="block text-sm font-medium text-gray-700">Student</label>
                                        <select id="student_id_form" name="student_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                            required>
                                            <option value="">Select student</option>
                                            @foreach ($students as $student)
                                                <option value="{{ $student->id }}"
                                                    {{ old('student_id', $selectedStudentId) == $student->id ? 'selected' : '' }}>
                                                    {{ $student->admission_no }} - {{ $student->user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="category"
                                            class="block text-sm font-medium text-gray-700">Category</label>
                                        <select id="category" name="category"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                            required>
                                            <option value="">Select category</option>
                                            @foreach (\App\Models\BehaviourRecord::categories() as $category)
                                                <option value="{{ $category }}"
                                                    {{ old('category') === $category ? 'selected' : '' }}>
                                                    {{ ucwords(str_replace('_', ' ', $category)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="severity"
                                            class="block text-sm font-medium text-gray-700">Severity</label>
                                        <select id="severity" name="severity"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                            required>
                                            <option value="">Select severity</option>
                                            <option value="minor" {{ old('severity') === 'minor' ? 'selected' : '' }}>
                                                Minor</option>
                                            <option value="moderate"
                                                {{ old('severity') === 'moderate' ? 'selected' : '' }}>Moderate
                                            </option>
                                            <option value="major" {{ old('severity') === 'major' ? 'selected' : '' }}>
                                                Major</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="incident" class="block text-sm font-medium text-gray-700">Incident
                                            Description</label>
                                        <textarea id="incident" name="incident" rows="4"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                            placeholder="Describe the incident..." required>{{ old('incident') }}</textarea>
                                    </div>

                                    <div>
                                        <label for="action_taken" class="block text-sm font-medium text-gray-700">Action
                                            Taken</label>
                                        <textarea id="action_taken" name="action_taken" rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                            placeholder="Describe any action taken...">{{ old('action_taken') }}</textarea>
                                    </div>

                                    <div>
                                        <label for="remarks"
                                            class="block text-sm font-medium text-gray-700">Remarks</label>
                                        <textarea id="remarks" name="remarks" rows="2"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                                            placeholder="Optional remarks...">{{ old('remarks') }}</textarea>
                                    </div>
                                </div>

                                @if ($errors->any())
                                    <div class="mt-4 rounded-lg bg-red-50 p-4 text-red-800">
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="mt-6 flex justify-end">
                                    <button type="submit"
                                        class="bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2 px-6 rounded-md">
                                        Save Behaviour Record
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                Recent Behaviour Records
                            </h3>

                            @if ($records->count() > 0)
                                <div class="space-y-4">
                                    @foreach ($records as $record)
                                        <div class="border rounded-lg p-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900">
                                                        {{ $record->student->user->name ?? 'Unknown Student' }}
                                                    </h4>
                                                    <p class="text-sm text-gray-500">
                                                        {{ $record->record_date?->format('d M Y') }} ·
                                                        {{ ucwords(str_replace('_', ' ', $record->category)) }}
                                                    </p>
                                                </div>

                                                <span
                                                    class="px-2 py-1 text-xs font-semibold rounded-full
                                                    @if ($record->severity === 'major') bg-red-100 text-red-800
                                                    @elseif($record->severity === 'moderate')
                                                        bg-yellow-100 text-yellow-800
                                                    @else
                                                        bg-blue-100 text-blue-800 @endif">
                                                    {{ ucfirst($record->severity) }}
                                                </span>
                                            </div>

                                            <div class="mt-3 space-y-2 text-sm">
                                                <p><span class="font-medium text-gray-700">Incident:</span> <span
                                                        class="text-gray-800">{{ $record->incident }}</span></p>

                                                @if ($record->action_taken)
                                                    <p><span class="font-medium text-gray-700">Action Taken:</span>
                                                        <span class="text-gray-800">{{ $record->action_taken }}</span>
                                                    </p>
                                                @endif

                                                @if ($record->remarks)
                                                    <p><span class="font-medium text-gray-700">Remarks:</span> <span
                                                            class="text-gray-800">{{ $record->remarks }}</span></p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No behaviour records found for the selected class/student.</p>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
