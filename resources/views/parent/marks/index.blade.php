<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-white rounded-lg shadow-md border border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Children's Marks
                </h2>
                <a href="{{ route('parent.dashboard') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">View Your Children's Marks</h3>
                        <p class="text-gray-600">Browse academic performance for all your children.</p>
                    </div>

                    @if(isset($children) && $children->count() > 0)
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Your Children</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                                @foreach($children as $child)
                                    <div class="border rounded-lg p-4 bg-gray-50">
                                        <div class="flex items-center">
                                            @if($child->photo)
                                                <img class="h-10 w-10 rounded-full" src="{{ Storage::url($child->photo) }}" alt="{{ $child->user->name }}">
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span class="text-gray-500">{{ substr($child->user->name ?? 'N', 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <div class="ml-3">
                                                <h5 class="font-medium text-gray-800">{{ $child->user->name ?? 'Unknown Student' }}</h5>
                                                <p class="text-sm text-gray-600">{{ $child->admission_no ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if(isset($academicYears) && $academicYears->count() > 0)
                            @foreach($academicYears as $academicYear)
                                <div class="mb-8">
                                    <h4 class="text-xl font-semibold text-gray-800 mb-4">
                                        {{ $academicYear->year_name }}
                                    </h4>
                                    
                                    @if($academicYear->terms->count() > 0)
                                        <div class="space-y-4">
                                            @foreach($academicYear->terms as $term)
                                                <div class="border rounded-lg p-4">
                                                    <div class="flex justify-between items-center mb-3">
                                                        <h5 class="font-medium text-gray-800">{{ $term->name }}</h5>
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
                                                    
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        @foreach($children as $child)
                                                            @php
                                                                $hasMarks = \App\Models\Mark::where('student_id', $child->id)
                                                                    ->where('term_id', $term->id)
                                                                    ->exists();
                                                                
                                                                $marksCount = \App\Models\Mark::where('student_id', $child->id)
                                                                    ->where('term_id', $term->id)
                                                                    ->count();
                                                            @endphp
                                                            
                                                            <div class="border rounded p-3">
                                                                <div class="flex justify-between items-center">
                                                                    <div>
                                                                        <h6 class="font-medium text-gray-700">{{ $child->user->name ?? 'Unknown' }}</h6>
                                                                        <p class="text-xs text-gray-500">{{ $child->admission_no ?? 'N/A' }}</p>
                                                                    </div>
                                                                    <span class="text-xs text-gray-500">{{ $marksCount }} subjects</span>
                                                                </div>
                                                                
                                                                @if($hasMarks)
                                                                    <a href="{{ route('parent.children.marks.show', [$child->id, $academicYear->id, $term->id]) }}" 
                                                                       class="inline-block mt-2 text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                                        View Detailed Marks →
                                                                    </a>
                                                                @else
                                                                    <span class="text-sm text-gray-500">No marks available</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
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
                                <p class="mt-1 text-sm text-gray-500">Your children don't have any marks recorded yet.</p>
                            </div>
                        @endif
                    @else
                        <div class="bg-yellow-50 rounded-lg p-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">No Children Linked</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>You don't have any children linked to your account yet. Please contact the school administration.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
