<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
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
                        <p class="text-gray-600">Monitor your children's academic progress and school information.</p>
                    </div>

                    <!-- Children Overview -->
                    <div class="mb-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Your Children</h4>
                        @if($children && $children->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($children as $child)
                                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-center">
                                            @if($child->photo)
                                                <img class="h-12 w-12 rounded-full" src="{{ Storage::url($child->photo) }}" alt="{{ $child->user->name }}">
                                            @else
                                                <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span class="text-gray-500">{{ substr($child->user->name ?? 'N', 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <div class="ml-4">
                                                <h5 class="font-medium text-gray-800">{{ $child->user->name ?? 'Unknown Student' }}</h5>
                                                <p class="text-sm text-gray-600">{{ $child->admission_no ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                        
                                        @if($child->currentClass)
                                            <div class="mt-3 text-sm">
                                                <span class="text-gray-600">Class:</span>
                                                <span class="font-medium">{{ $child->currentClass->name ?? 'N/A' }}</span>
                                            </div>
                                        @endif
                                        
                                        <div class="mt-2 flex items-center">
                                            <div class="w-2 h-2 rounded-full {{ $child->user->status === 'active' ? 'bg-green-500' : 'bg-red-500' }} mr-2"></div>
                                            <span class="text-xs text-gray-600">
                                                {{ $child->user->status === 'active' ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-8 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No children linked</h3>
                                <p class="mt-1 text-sm text-gray-500">You don't have any children linked to your account yet.</p>
                                <div class="mt-4">
                                    <p class="text-xs text-gray-500">Please contact the school administration to link your children.</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- CHILDREN'S MARKS OVERVIEW SECTION -->
                    @if($children && $children->count() > 0)
                        <div class="mt-8" id="marks-overview">
                            <div class="mb-4">
                                <h4 class="text-lg font-semibold text-gray-800">Children's Marks Overview</h4>
                                <p class="text-sm text-gray-600 mt-1">Current term performance summary</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($children as $child)
                                    <div class="border rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h5 class="font-medium text-gray-800">{{ $child->user->name ?? 'Unknown Student' }}</h5>
                                            <span class="text-sm text-gray-600">{{ $child->admission_no ?? 'N/A' }}</span>
                                        </div>
                                        
                                        @php
                                            $currentTerm = \App\Models\Term::where('status', 'active')->first();
                                            $marks = collect();
                                            $averages = ['midterm' => null, 'endterm' => null];
                                            
                                            if ($currentTerm && $child) {
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
                                                @if($child->currentClass)
                                                    | {{ $child->currentClass->name ?? 'N/A' }}
                                                @endif
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500">No active term currently</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Quick Access Section -->
                    <div class="mt-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Quick Access</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <a href="{{ route('parent.children.marks.index') }}" class="border rounded-lg p-4 hover:shadow-md transition-shadow block">
                                <div class="flex items-center">
                                    <div class="rounded-full bg-blue-100 p-2">
                                        <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h5 class="font-medium text-gray-800">View All Marks</h5>
                                        <p class="text-sm text-gray-600">See detailed performance</p>
                                    </div>
                                </div>
                            </a>
                            
                            <div class="border rounded-lg p-4 opacity-50">
                                <div class="flex items-center">
                                    <div class="rounded-full bg-green-100 p-2">
                                        <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h5 class="font-medium text-gray-800">Attendance</h5>
                                        <p class="text-sm text-gray-600">Coming soon</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border rounded-lg p-4 opacity-50">
                                <div class="flex items-center">
                                    <div class="rounded-full bg-purple-100 p-2">
                                        <svg class="h-6 w-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h5 class="font-medium text-gray-800">Events</h5>
                                        <p class="text-sm text-gray-600">Coming soon</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="mt-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Account Information</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Email</p>
                                    <p class="font-medium">{{ auth()->user()->email }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Account Status</p>
                                    <p class="font-medium">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
