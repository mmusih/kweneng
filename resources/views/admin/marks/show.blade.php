<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mark Details
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Mark Details</h3>
                        <p class="text-gray-600">View detailed information about this mark.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="border rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-4">Student Information</h4>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Name:</span>
                                    <span class="ml-2">{{ $mark->student->user->name }}</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Admission No:</span>
                                    <span class="ml-2">{{ $mark->student->admission_no }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-4">Subject Information</h4>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Subject:</span>
                                    <span class="ml-2">{{ $mark->subject->name }} ({{ $mark->subject->code }})</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Class:</span>
                                    <span class="ml-2">{{ $mark->class->name }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-4">Academic Information</h4>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Academic Year:</span>
                                    <span class="ml-2">{{ $mark->academicYear->year_name }}</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Term:</span>
                                    <span class="ml-2">{{ $mark->term->name }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-4">Mark Details</h4>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Midterm Score:</span>
                                    <span class="ml-2">{{ $mark->midterm_score ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Endterm Score:</span>
                                    <span class="ml-2">{{ $mark->endterm_score ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Grade:</span>
                                    <span class="ml-2">
                                        @if($mark->grade)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @switch($mark->grade)
                                                    @case('A*') bg-green-100 text-green-800 @break
                                                    @case('A') bg-green-100 text-green-800 @break
                                                    @case('B') bg-blue-100 text-blue-800 @break
                                                    @case('C') bg-yellow-100 text-yellow-800 @break
                                                    @case('D') bg-orange-100 text-orange-800 @break
                                                    @case('E') bg-red-100 text-red-800 @break
                                                    @case('F') bg-red-100 text-red-800 @break
                                                @endswitch">
                                                {{ $mark->grade }}
                                            </span>
                                        @else
                                            <span class="text-gray-500">N/A</span>
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Entered By:</span>
                                    <span class="ml-2">{{ $mark->teacher->user->name }}</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Created:</span>
                                    <span class="ml-2">{{ $mark->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Last Updated:</span>
                                    <span class="ml-2">{{ $mark->updated_at->format('M j, Y g:i A') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($mark->remarks)
                        <div class="mt-6 border rounded-lg p-6">
                            <h4 class="text-lg font-semibold mb-2">Remarks</h4>
                            <p class="text-gray-700">{{ $mark->remarks }}</p>
                        </div>
                    @endif
                    
                    <div class="mt-8 flex justify-end space-x-3">
                        <a href="{{ route('admin.marks.index') }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                            Back to List
                        </a>
                        <a href="{{ route('admin.marks.edit', $mark) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Edit Mark
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
