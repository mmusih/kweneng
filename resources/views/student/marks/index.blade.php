<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-white rounded-lg shadow-md border border-gray-200">
            <h2 class="font-bold text-2xl text-center text-gray-800 leading-tight">
                My Marks
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">View Your Marks</h3>
                        <p class="text-gray-600">Browse your academic performance across different terms.</p>
                    </div>

                    @if($academicYears->count() > 0)
                        @foreach($academicYears as $academicYear)
                            <div class="mb-8">
                                <h4 class="text-xl font-semibold text-gray-800 mb-4">
                                    {{ $academicYear->year_name }}
                                </h4>
                                
                                @if($academicYear->terms->count() > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($academicYear->terms as $term)
                                            @php
                                                $hasMarks = \App\Models\Mark::where('student_id', auth()->user()->student->id)
                                                    ->where('term_id', $term->id)
                                                    ->exists();
                                            @endphp
                                            
                                            <a href="{{ route('student.marks.show', [$academicYear->id, $term->id]) }}" 
                                               class="block border rounded-lg p-4 hover:shadow-md transition-shadow {{ $hasMarks ? 'hover:border-indigo-300' : 'opacity-50' }}">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <h5 class="font-medium text-gray-800">{{ $term->name }}</h5>
                                                        <p class="text-sm text-gray-600">
                                                            {{ $term->start_date->format('M j, Y') }} - {{ $term->end_date->format('M j, Y') }}
                                                        </p>
                                                    </div>
                                                    @if($term->status === 'locked')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            Locked
                                                        </span>
                                                    @elseif($term->status === 'finalized')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            Finalized
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            Active
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                @if($hasMarks)
                                                    <div class="mt-3 text-sm text-indigo-600 font-medium">
                                                        View Marks →
                                                    </div>
                                                @else
                                                    <div class="mt-3 text-sm text-gray-500">
                                                        No marks available
                                                    </div>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-gray-600 text-center">No terms available for this academic year.</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="bg-gray-50 rounded-lg p-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No marks available</h3>
                            <p class="mt-1 text-sm text-gray-500">You don't have any marks recorded yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
