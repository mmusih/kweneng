<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-white rounded-lg shadow-md border border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ $child->user->name ?? 'Unknown Student' }} - {{ $academicYear->year_name }} - {{ $term->name }}
                </h2>
                <a href="{{ route('parent.children.marks.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Marks Overview
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-2xl font-semibold">Detailed Marks</h3>
                            <p class="text-gray-600">
                                {{ $child->user->name ?? 'Unknown Student' }} - {{ $academicYear->year_name }} - {{ $term->name }}
                                @if($term->status === 'locked')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">
                                        Locked
                                    </span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('parent.dashboard') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                            ← Back to Dashboard
                        </a>
                    </div>

                    <!-- Averages Summary -->
                    @if($averages['midterm'] || $averages['endterm'])
                        <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($averages['midterm'])
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-100">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-lg font-medium text-gray-900">Midterm Average</h4>
                                            <p class="text-3xl font-bold text-blue-600 mt-2">{{ number_format($averages['midterm'], 2) }}</p>
                                        </div>
                                        <div class="rounded-full bg-blue-500 p-3">
                                            <svg class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            @if($averages['endterm'])
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg border border-green-100">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-lg font-medium text-gray-900">Endterm Average</h4>
                                            <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($averages['endterm'], 2) }}</p>
                                        </div>
                                        <div class="rounded-full bg-green-500 p-3">
                                            <svg class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Marks Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Midterm</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endterm</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($studentSubjects as $studentSubject)
                                    @php
                                        $mark = $marks[$studentSubject->subject_id] ?? null;
                                        $average = null;
                                        $grade = null;
                                        
                                        if ($mark) {
                                            if ($mark->midterm_score !== null && $mark->endterm_score !== null) {
                                                $average = ($mark->midterm_score + $mark->endterm_score) / 2;
                                            } elseif ($mark->midterm_score !== null) {
                                                $average = $mark->midterm_score;
                                            } elseif ($mark->endterm_score !== null) {
                                                $average = $mark->endterm_score;
                                            }
                                            $grade = $mark->grade;
                                        }
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $studentSubject->subject->name ?? 'Unknown Subject' }}</div>
                                            <div class="text-sm text-gray-500">{{ $studentSubject->subject->code ?? 'N/A' }}</div>
                                            @if($studentSubject->is_elective)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-1">
                                                    Elective
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $mark->midterm_score ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $mark->endterm_score ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $average !== null ? number_format($average, 2) : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($grade)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    @switch($grade)
                                                        @case('A*') bg-green-100 text-green-800 @break
                                                        @case('A') bg-green-100 text-green-800 @break
                                                        @case('B') bg-blue-100 text-blue-800 @break
                                                        @case('C') bg-yellow-100 text-yellow-800 @break
                                                        @case('D') bg-orange-100 text-orange-800 @break
                                                        @case('E') bg-red-100 text-red-800 @break
                                                        @case('F') bg-red-100 text-red-800 @break
                                                        @default bg-gray-100 text-gray-800
                                                    @endswitch">
                                                    {{ $grade }}
                                                </span>
                                            @else
                                                <span class="text-gray-500 text-sm">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $mark->teacher->user->name ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No subjects assigned for this term.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Legend -->
                    <div class="mt-8 p-6 bg-gray-50 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Grading Scale</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="flex items-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 mr-2">A*</span>
                                <span class="text-sm text-gray-600">90-100</span>
                            </div>
                            <div class="flex items-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 mr-2">A</span>
                                <span class="text-sm text-gray-600">80-89</span>
                            </div>
                            <div class="flex items-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 mr-2">B</span>
                                <span class="text-sm text-gray-600">70-79</span>
                            </div>
                            <div class="flex items-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 mr-2">C</span>
                                <span class="text-sm text-gray-600">60-69</span>
                            </div>
                            <div class="flex items-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 mr-2">D</span>
                                <span class="text-sm text-gray-600">50-59</span>
                            </div>
                            <div class="flex items-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 mr-2">E</span>
                                <span class="text-sm text-gray-600">40-49</span>
                            </div>
                            <div class="flex items-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 mr-2">F</span>
                                <span class="text-sm text-gray-600">0-39</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
