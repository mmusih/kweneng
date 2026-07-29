<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Create Class
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($activeAcademicYear)
                        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-900">
                            <div class="font-semibold">Current academic year</div>
                            <div class="text-sm mt-1">{{ $activeAcademicYear->year_name }} is selected automatically for new classes.</div>
                        </div>
                    @else
                        <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-900">
                            <div class="font-semibold">No active academic year</div>
                            <div class="text-sm mt-1">Activate the current academic year before creating operational classes.</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.classes.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Class Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="level" :value="__('Level')" />
                                <x-text-input id="level" class="block mt-1 w-full" type="number" name="level"
                                    :value="old('level')" required min="1" max="12" />
                                <x-input-error :messages="$errors->get('level')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Select academic year</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ (string) old('academic_year_id', $activeAcademicYear?->id) === (string) $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                            @if ($year->active)
                                                (Active)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('academic_year_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="class_teacher_id" :value="__('Class Teacher')" />
                                <select id="class_teacher_id" name="class_teacher_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">No class teacher assigned</option>
                                    @foreach ($classTeachers as $teacher)
                                        <option value="{{ $teacher->id }}"
                                            {{ old('class_teacher_id') == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->user->name }}
                                            @if ($teacher->user->role === 'headmaster')
                                                - Headmaster
                                            @else
                                                - Teacher
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('class_teacher_id')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">
                                    Only the assigned class teacher will manage attendance, behaviour, and term summary
                                    for this class.
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Optional Class List Import</h3>
                            <p class="text-sm text-gray-500 mb-4">
                                You may upload a CSV file to create student accounts while creating the class.
                                The file must include the required student fields for your current database structure.
                            </p>

                            <div>
                                <x-input-label for="class_list_file" :value="__('Class List CSV File')" />
                                <input id="class_list_file" name="class_list_file" type="file" accept=".csv,text/csv"
                                    class="block mt-1 w-full rounded-md border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white p-2" />
                                <x-input-error :messages="$errors->get('class_list_file')" class="mt-2" />

                                <div
                                    class="mt-3 rounded-md bg-gray-50 border border-gray-200 p-4 text-sm text-gray-600">
                                    <p class="font-medium text-gray-700 mb-2">Required CSV columns</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li><span class="font-medium">surname</span></li>
                                        <li><span class="font-medium">name</span></li>
                                        <li><span class="font-medium">gender</span> (male or female)</li>
                                        <li><span class="font-medium">date_of_birth</span> (format: YYYY-MM-DD)</li>
                                    </ul>

                                    <p class="font-medium text-gray-700 mt-4 mb-2">Optional CSV columns</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li><span class="font-medium">nationality</span></li>
                                        <li><span class="font-medium">identity_document_type</span> (birth_certificate, national_id, or passport)</li>
                                        <li><span class="font-medium">identity_document_number</span></li>
                                        <li><span class="font-medium">emergency_contact_name</span></li>
                                        <li><span class="font-medium">emergency_contact_phone</span></li>
                                    </ul>

                                    <p class="font-medium text-gray-700 mt-4 mb-2">Example CSV format</p>
                                    <pre class="whitespace-pre-wrap text-xs text-gray-600">surname,name,gender,date_of_birth,nationality,identity_document_type,identity_document_number,emergency_contact_name,emergency_contact_phone
Sechele,Leatile,female,2010-05-12,Botswana,birth_certificate,BC1234567,Mpho Sechele,71234567
Doe,John,male,2011-08-03,Zimbabwe,passport,PN123456,Jane Doe,72345678</pre>

                                    <p class="mt-3 text-xs text-gray-500">
                                        Missing identity and emergency contact details can be completed later by admin or by linked parents from their dashboard.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.classes.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>

                            <x-primary-button>
                                {{ __('Create Class') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
