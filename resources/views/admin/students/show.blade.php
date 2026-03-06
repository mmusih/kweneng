<x-app-layout>
<x-slot name="header">
    <div class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
        <h2 class="font-semibold text-2xl text-white leading-tight">
            Student Profile
        </h2>
    </div>
</x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- Student Basic Details -->
                    <div class="border-b border-gray-200 pb-5 mb-6">
                        <div class="flex flex-col md:flex-row items-start md:items-center">
                            <!-- Photo -->
                            <div class="mb-4 md:mb-0 md:mr-6">
                                @if($student->photo)
                                    <img src="{{ Storage::url($student->photo) }}" alt="{{ $student->user->name }}" class="h-24 w-24 rounded-full object-cover border-2 border-gray-300">
                                @else
                                    <div class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center border-2 border-gray-300">
                                        <span class="text-2xl text-gray-500">{{ substr($student->user->name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Basic Info -->
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900">{{ $student->user->name }}</h3>
                                <p class="text-gray-600">{{ $student->user->email }}</p>
                                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-2">
                                    <div>
                                        <span class="text-sm text-gray-500">Admission No:</span>
                                        <span class="ml-1 font-medium">{{ $student->admission_no }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-500">Gender:</span>
                                        <span class="ml-1 font-medium capitalize">{{ $student->gender }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-500">DOB:</span>
                                        <span class="ml-1 font-medium">{{ $student->date_of_birth->format('M j, Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flags Section -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Access Flags</h4>
                        <div class="flex flex-wrap gap-4">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full {{ $student->results_access ? 'bg-green-500' : 'bg-red-500' }}"></div>
                                <span class="ml-2 text-sm font-medium">
                                    {{ $student->results_access ? 'Results Access Enabled' : 'Results Access Blocked' }}
                                </span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full {{ $student->fees_blocked ? 'bg-red-500' : 'bg-green-500' }}"></div>
                                <span class="ml-2 text-sm font-medium">
                                    {{ $student->fees_blocked ? 'Fees Access Blocked' : 'Fees Access Enabled' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Current Class Information -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Current Enrollment</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            @if($student->currentClass)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-sm text-gray-500">Current Class:</span>
                                        <span class="ml-1 font-medium">{{ $student->currentClass->name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-500">Academic Year:</span>
                                        <span class="ml-1 font-medium">
                                            {{ $student->currentClass->academicYear->year_name ?? 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <p class="text-gray-500 italic">No current class assigned</p>
                            @endif
                        </div>
                    </div>

                    <!-- Academic History Table -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Academic History</h4>
                        @if($student->classHistory->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($student->classHistory->sortByDesc('academicYear.year_name') as $history)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $history->academicYear->year_name ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $history->class->name ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($history->is_current)
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Current
                                                        </span>
                                                    @else
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                            Past
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500 italic">No academic history available</p>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 flex justify-end space-x-3">
                        <a href="{{ route('admin.students.edit', $student) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Edit Student
                        </a>
                        <a href="{{ route('admin.students.index') }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
