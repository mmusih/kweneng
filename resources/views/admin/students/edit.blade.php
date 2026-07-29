<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">Edit Student</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 text-sm">
                        Legacy admission reference: <strong>{{ $student->admission_no }}</strong>. This is kept only for older records and login-slip compatibility.
                    </div>

                    <form method="POST" action="{{ route('admin.students.update', $student) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="space-y-8">
                            <section>
                                <div class="mb-4 border-b pb-3">
                                    <h3 class="text-lg font-semibold text-gray-900">Student Details</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="name" :value="__('Full Name')" />
                                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $student->user->name)" required autofocus />
                                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="email" :value="__('Email Address')" />
                                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $student->user->email)" required />
                                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="gender" :value="__('Gender')" />
                                        <select id="gender" name="gender" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" {{ old('gender', $student->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender', $student->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                                        <x-text-input id="date_of_birth" class="block mt-1 w-full" type="date" name="date_of_birth" :value="old('date_of_birth', optional($student->date_of_birth)->format('Y-m-d'))" required />
                                        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="current_class_id" :value="__('Current Class')" />
                                        <select id="current_class_id" name="current_class_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                            <option value="">Select Class</option>
                                            @foreach ($classes as $class)
                                                <option value="{{ $class->id }}" {{ old('current_class_id', $student->current_class_id) == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('current_class_id')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="photo" :value="__('Passport Photo')" />
                                        <x-text-input id="photo" class="block mt-1 w-full" type="file" name="photo" accept="image/*" />
                                        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                                        <p class="text-sm text-gray-500 mt-1">JPEG, PNG or WebP. Max 2MB.</p>

                                        @if ($student->photo)
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500">Current photo:</p>
                                                <img src="{{ Storage::url($student->photo) }}" alt="Current photo" class="mt-1 h-24 w-24 object-cover rounded-full border-2 border-gray-300">
                                                <p class="text-sm text-gray-500 mt-1">Leave blank to keep current photo.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </section>

                            <section>
                                <div class="mb-4 border-b pb-3">
                                    <h3 class="text-lg font-semibold text-gray-900">Identity Information</h3>
                                    <p class="text-sm text-gray-500 mt-1">Parents can update missing identity information from their dashboard.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <x-input-label for="nationality" :value="__('Nationality')" />
                                        <x-text-input id="nationality" class="block mt-1 w-full" type="text" name="nationality" :value="old('nationality', $student->nationality)" required />
                                        <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="identity_document_type" :value="__('Document Type')" />
                                        <select id="identity_document_type" name="identity_document_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                            <option value="">Select document type</option>
                                            @foreach ($identityDocumentTypes as $value => $label)
                                                <option value="{{ $value }}" {{ old('identity_document_type', $student->identity_document_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('identity_document_type')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="identity_document_number" :value="__('Document Number')" />
                                        <x-text-input id="identity_document_number" class="block mt-1 w-full uppercase" type="text" name="identity_document_number" :value="old('identity_document_number', $student->identity_document_number)" required />
                                        <x-input-error :messages="$errors->get('identity_document_number')" class="mt-2" />
                                    </div>
                                </div>
                            </section>

                            <section>
                                <div class="mb-4 border-b pb-3">
                                    <h3 class="text-lg font-semibold text-gray-900">Emergency Contact</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="emergency_contact_name" :value="__('Contact Name')" />
                                        <x-text-input id="emergency_contact_name" class="block mt-1 w-full" type="text" name="emergency_contact_name" :value="old('emergency_contact_name', $student->emergency_contact_name)" />
                                        <x-input-error :messages="$errors->get('emergency_contact_name')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="emergency_contact_relationship" :value="__('Relationship')" />
                                        <x-text-input id="emergency_contact_relationship" class="block mt-1 w-full" type="text" name="emergency_contact_relationship" :value="old('emergency_contact_relationship', $student->emergency_contact_relationship)" />
                                        <x-input-error :messages="$errors->get('emergency_contact_relationship')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="emergency_contact_phone" :value="__('Primary Phone')" />
                                        <x-text-input id="emergency_contact_phone" class="block mt-1 w-full" type="text" name="emergency_contact_phone" :value="old('emergency_contact_phone', $student->emergency_contact_phone)" />
                                        <x-input-error :messages="$errors->get('emergency_contact_phone')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="emergency_contact_alt_phone" :value="__('Alternative Phone')" />
                                        <x-text-input id="emergency_contact_alt_phone" class="block mt-1 w-full" type="text" name="emergency_contact_alt_phone" :value="old('emergency_contact_alt_phone', $student->emergency_contact_alt_phone)" />
                                        <x-input-error :messages="$errors->get('emergency_contact_alt_phone')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="emergency_contact_address" :value="__('Emergency Address')" />
                                        <textarea id="emergency_contact_address" name="emergency_contact_address" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('emergency_contact_address', $student->emergency_contact_address) }}</textarea>
                                        <x-input-error :messages="$errors->get('emergency_contact_address')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="medical_notes" :value="__('Medical Notes / Allergies')" />
                                        <textarea id="medical_notes" name="medical_notes" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('medical_notes', $student->medical_notes) }}</textarea>
                                        <x-input-error :messages="$errors->get('medical_notes')" class="mt-2" />
                                    </div>
                                </div>
                            </section>

                            <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input id="results_access" type="checkbox" name="results_access" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('results_access', $student->results_access) ? 'checked' : '' }}>
                                    <x-input-label for="results_access" :value="__('Allow Access to Results')" class="ml-2" />
                                </div>

                                <div class="flex items-center">
                                    <input id="fees_blocked" type="checkbox" name="fees_blocked" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('fees_blocked', $student->fees_blocked) ? 'checked' : '' }}>
                                    <x-input-label for="fees_blocked" :value="__('Block Fees Access')" class="ml-2" />
                                </div>
                            </section>
                        </div>

                        <div class="flex items-center justify-end mt-8 gap-3">
                            <a href="{{ route('admin.students.index') }}" class="text-gray-600 hover:text-gray-800">Cancel</a>
                            <x-primary-button>{{ __('Update Student') }}</x-primary-button>
                        </div>
                    </form>

                    <form action="{{ route('admin.students.reset-password', $student) }}" method="POST" class="mt-4 text-right" onsubmit="return confirm('Reset password for this student?');">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-md shadow-sm transition">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
