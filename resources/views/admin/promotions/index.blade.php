<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Student Promotions
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Manage Student Promotions</h3>
                        <p class="text-gray-600">Promote students between classes with full audit trail.</p>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Individual Promotion Form -->
                    <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                        <h4 class="text-lg font-semibold mb-4">Promote Individual Student</h4>
                        <form method="POST" action="{{ route('admin.promotions.promote-student') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @csrf
                            
                            <div>
                                <x-input-label for="student_id" :value="__('Select Student')" />
                                <select id="student_id" name="student_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Choose a student</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">
                                            {{ $student->user->name }} ({{ $student->admission_no }}) - 
                                            {{ $student->currentClass->name ?? 'No Class' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <x-input-label for="new_class_id" :value="__('Target Class')" />
                                <select id="new_class_id" name="new_class_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Choose target class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">
                                            {{ $class->name }} ({{ $class->academicYear->year_name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Choose academic year</option>
                                    @foreach(App\Models\AcademicYear::all() as $year)
                                        <option value="{{ $year->id }}" {{ $year->active ? 'selected' : '' }}>
                                            {{ $year->year_name }} {{ $year->active ? '(Active)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <x-input-label for="promotion_type" :value="__('Promotion Type')" />
                                <select id="promotion_type" name="promotion_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="promoted">Promoted</option>
                                    <option value="repeated">Repeated</option>
                                    <option value="transferred">Transferred</option>
                                    <option value="graduated">Graduated</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <x-input-label for="remarks" :value="__('Remarks (Optional)')" />
                                <textarea id="remarks" name="remarks" rows="2" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                            
                            <div class="md:col-span-2">
                                <x-primary-button>
                                    {{ __('Promote Student') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <!-- Bulk Promotion Form -->
                    <div class="p-6 bg-blue-50 rounded-lg">
                        <h4 class="text-lg font-semibold mb-4">Bulk Promote Entire Class</h4>
                        <form method="POST" action="{{ route('admin.promotions.bulk-promote') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @csrf
                            
                            <div>
                                <x-input-label for="from_class_id_bulk" :value="__('From Class')" />
                                <select id="from_class_id_bulk" name="from_class_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Choose source class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <x-input-label for="to_class_id_bulk" :value="__('To Class')" />
                                <select id="to_class_id_bulk" name="to_class_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Choose target class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <x-input-label for="academic_year_id_bulk" :value="__('Academic Year')" />
                                <select id="academic_year_id_bulk" name="academic_year_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Choose academic year</option>
                                    @foreach(App\Models\AcademicYear::all() as $year)
                                        <option value="{{ $year->id }}" {{ $year->active ? 'selected' : '' }}>
                                            {{ $year->year_name }} {{ $year->active ? '(Active)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <x-input-label for="promotion_type_bulk" :value="__('Promotion Type')" />
                                <select id="promotion_type_bulk" name="promotion_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="promoted">Promoted</option>
                                    <option value="repeated">Repeated</option>
                                    <option value="transferred">Transferred</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <x-primary-button class="bg-blue-600 hover:bg-blue-700">
                                    {{ __('Bulk Promote Class') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <!-- Add this section after the bulk promotion form -->
<div class="mt-8 p-6 bg-yellow-50 rounded-lg">
    <h4 class="text-lg font-semibold mb-4">Reverse Student Promotion</h4>
    <form method="POST" action="{{ route('admin.promotions.reverse-promotion') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        
        <div>
            <x-input-label for="reverse_student_id" :value="__('Select Student')" />
            <select id="reverse_student_id" name="student_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="">Choose a student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">
                        {{ $student->user->name }} ({{ $student->admission_no }}) - 
                        {{ $student->currentClass->name ?? 'No Class' }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <div>
            <x-input-label for="reverse_remarks" :value="__('Remarks (Optional)')" />
            <textarea id="reverse_remarks" name="remarks" rows="2" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
        </div>
        
        <div class="md:col-span-2">
            <x-primary-button class="bg-yellow-600 hover:bg-yellow-700">
                {{ __('Reverse Promotion') }}
            </x-primary-button>
        </div>
    </form>
</div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
