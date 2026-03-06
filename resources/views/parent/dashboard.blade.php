<x-app-layout>
    <x-slot name="header">
        <div class="pt-12">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Parent Dashboard
        </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Welcome, {{ auth()->user()->name }}!</h3>
                        <p class="text-gray-600">Monitor your child's progress and academic performance.</p>
                    </div>
                    
                    <!-- Children Overview -->
                    @if($children && $children->count() > 0)
                        <div class="mb-8">
                            <h4 class="text-xl font-semibold text-gray-800 mb-4">Your Children</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach($children as $child)
                                    <div class="border border-gray-200 rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center mb-3">
                                            @if($child->photo)
                                                <img src="{{ Storage::url($child->photo) }}" alt="{{ $child->user->name }}" class="h-12 w-12 rounded-full object-cover">
                                            @else
                                                <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span class="text-gray-500">{{ substr($child->user->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <div class="ml-3">
                                                <h5 class="font-semibold text-gray-900">{{ $child->user->name }}</h5>
                                                <p class="text-sm text-gray-500">{{ ucfirst($child->pivot->relationship ?? 'Child') }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <div>
                                                <span class="text-sm text-gray-500">Current Class:</span>
                                                <p class="font-medium">
                                                    {{ $child->currentClass->name ?? 'Not Assigned' }}
                                                </p>
                                            </div>
                                            
                                            <div>
                                                <span class="text-sm text-gray-500">Admission No:</span>
                                                <p class="font-medium">{{ $child->admission_no }}</p>
                                            </div>
                                            
                                            <div class="flex items-center mt-2">
                                                <div class="w-2 h-2 rounded-full {{ $child->results_access ? 'bg-green-500' : 'bg-red-500' }} mr-2"></div>
                                                <span class="text-xs">
                                                    {{ $child->results_access ? 'Results Access' : 'No Results Access' }}
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center">
                                                <div class="w-2 h-2 rounded-full {{ $child->fees_blocked ? 'bg-red-500' : 'bg-green-500' }} mr-2"></div>
                                                <span class="text-xs">
                                                    {{ $child->fees_blocked ? 'Fees Blocked' : 'Fees Access' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <p class="text-yellow-800">You are not currently linked to any students. Please contact the school administration.</p>
                        </div>
                    @endif
                    
                    <!-- CHILDREN'S MARKS OVERVIEW SECTION (NEW) -->
                    <div class="mt-8">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Children's Marks Overview</h4>
                            <a href="{{ route('parent.children.marks') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                View All Marks →
                            </a>
                        </div>
                        
                        @if($children && $children->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($children as $child)
                                    <div class="border rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h5 class="font-medium text-gray-800">{{ $child->user->name }}</h5>
                                            <span class="text-sm text-gray-600">{{ $child->admission_no }}</span>
                                        </div>
                                        
                                        @php
                                            $currentTerm = \App\Models\Term::where('status', 'active')->first();
                                            $marks = collect();
                                            $averages = ['midterm' => null, 'endterm' => null];
                                            
                                            if ($currentTerm) {
                                                $marks = \App\Models\Mark::where('student_id', $child->id)
                                                    ->where('term_id', $currentTerm->id)
                                                    ->with('subject')
                                                    ->get();
                                                
                                                $midtermScores = $marks->pluck('midterm_score')->filter(fn($score) => $score !== null);
                                                $endtermScores = $marks->pluck('endterm_score')->filter(fn($score) => $score !== null);
                                                
                                                $averages['midterm'] = $midtermScores->isNotEmpty() ? round($midtermScores->avg(), 2) : null;
                                                $averages['endterm'] = $endtermScores->isNotEmpty() ? round($endtermScores->avg(), 2) : null;
                                            }
                                        @endphp
                                        
                                        @if($currentTerm)
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="bg-blue-50 p-3 rounded">
                                                    <p class="text-xs text-blue-700">Midterm Avg</p>
                                                    <p class="text-xl font-bold text-blue-600">
                                                        {{ $averages['midterm'] ? number_format($averages['midterm'], 2) : 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="bg-green-50 p-3 rounded">
                                                    <p class="text-xs text-green-700">Endterm Avg</p>
                                                    <p class="text-xl font-bold text-green-600">
                                                        {{ $averages['endterm'] ? number_format($averages['endterm'], 2) : 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3 text-sm text-gray-600">
                                                {{ $marks->count() }} subjects marked
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500">No active term currently</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-6 text-center">
                                <p class="text-gray-600">No children linked to your account.</p>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Academic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                        <div class="bg-blue-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-blue-800">Current Academic Year</h4>
                            @if($currentAcademicYear)
                                <p class="text-2xl font-bold text-blue-600">
                                    {{ $currentAcademicYear->year_name }}
                                </p>
                                @if($currentAcademicYear->active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-200 text-blue-800 mt-1">
                                        Active
                                    </span>
                                @endif
                            @else
                                <p class="text-lg text-blue-600">Not Set</p>
                            @endif
                        </div>
                        
                        <div class="bg-green-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-green-800">Current Term</h4>
                            @if($currentTerm)
                                <p class="text-2xl font-bold text-green-600">
                                    {{ $currentTerm->name }}
                                </p>
                                <p class="text-sm text-green-700 mt-1">
                                    {{ $currentTerm->start_date->format('M j') }} - {{ $currentTerm->end_date->format('M j, Y') }}
                                </p>
                                @if($currentTerm->locked)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-200 text-yellow-800 mt-1">
                                        Locked
                                    </span>
                                @endif
                            @else
                                <p class="text-lg text-green-600">No Active Term</p>
                            @endif
                        </div>
                        
                        <div class="bg-purple-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-purple-800">Quick Links</h4>
                            <div class="mt-2 space-y-2">
                                <a href="#" class="block text-purple-600 hover:text-purple-800 text-sm">View All Children</a>
                                <a href="#" class="block text-purple-600 hover:text-purple-800 text-sm">Fee Statements</a>
                                <a href="#" class="block text-purple-600 hover:text-purple-800 text-sm">Academic Calendar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
