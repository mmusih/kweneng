<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Alumni Details
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(isset($alumni))
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-semibold">{{ $alumni->name }}</h3>
                            <div>
                                <a href="{{ route('admin.alumni.edit', $alumni) }}" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                                    Edit
                                </a>
                                <a href="{{ route('admin.alumni.index') }}" 
                                   class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Back to List
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-lg font-semibold mb-3">Personal Information</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-sm text-gray-500">Email</label>
                                        <p class="font-medium">{{ $alumni->email }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-500">Phone</label>
                                        <p class="font-medium">{{ $alumni->phone ?? 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-500">Graduation Year</label>
                                        <p class="font-medium">{{ $alumni->graduation_year }}</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-lg font-semibold mb-3">Professional Information</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-sm text-gray-500">Current Occupation</label>
                                        <p class="font-medium">{{ $alumni->current_occupation ?? 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-500">Company</label>
                                        <p class="font-medium">{{ $alumni->company ?? 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-500">Location</label>
                                        <p class="font-medium">{{ $alumni->location ?? 'Not provided' }}</p>
                                    </div>
                                    @if($alumni->linkedin_url)
                                    <div>
                                        <label class="text-sm text-gray-500">LinkedIn</label>
                                        <p class="font-medium">
                                            <a href="{{ $alumni->linkedin_url }}" target="_blank" class="text-blue-600 hover:underline">
                                                View Profile
                                            </a>
                                        </p>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <h4 class="text-lg font-semibold mb-3">Biography</h4>
                                <p class="text-gray-700">{{ $alumni->bio ?? 'No biography provided.' }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <h4 class="text-lg font-semibold mb-3">Status</h4>
                                <p>
                                    @if($alumni->is_published)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                            Published
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                            Draft
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <h3 class="text-xl font-semibold text-gray-700">Alumni not found</h3>
                            <a href="{{ route('admin.alumni.index') }}" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Back to Alumni List
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
