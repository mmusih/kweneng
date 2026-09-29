<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-3 bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] text-white rounded-lg shadow-lg flex items-center justify-center">
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

                    <form id="student-photo-form" method="POST" action="{{ route('admin.students.photo.update', array_merge(['student' => $student], request()->only(['search', 'class_id', 'page', 'term_id']))) }}" enctype="multipart/form-data" class="profile-section mb-8 rounded-xl border border-gray-200 bg-gray-50 p-5" x-data="{ preview: null }">
                        @csrf
                        @method('PUT')
                        <h3 class="text-lg font-semibold text-gray-900">Profile photo</h3>
                        <p id="photo-help" class="mt-1 text-sm text-gray-600">Upload or replace the photo independently. Nationality, identity details and other student information can be completed later.</p>
                        <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div x-show="!preview"><x-student-photo :student="$student" :portrait="true" /></div>
                            <img x-cloak x-show="preview" x-bind:src="preview" alt="Selected student photo preview" class="student-portrait object-contain">
                            <div class="min-w-0 flex-1">
                                <x-input-label for="photo" :value="__('Choose profile photo')" />
                                <x-text-input id="photo" class="block mt-1 w-full p-2" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required aria-describedby="photo-help photo-format" x-on:change="if (preview) URL.revokeObjectURL(preview); preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" />
                                <p id="photo-format" class="mt-2 text-sm text-gray-600">JPEG, PNG or WebP. Maximum 2 MB.</p>
                                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                            </div>
                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Save Photo</button>
                        </div>
                    </form>

                    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 text-sm">
                        Legacy admission reference: <strong>{{ $student->admission_no }}</strong>. This is kept only for older records and login-slip compatibility.
                    </div>

                    <form method="POST" action="{{ route('admin.students.update', array_merge(['student' => $student], request()->only(['search', 'class_id', 'page', 'term_id']))) }}" enctype="multipart/form-data">
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
                            <a href="{{ route('admin.students.show', array_merge(['student' => $student], request()->only(['search', 'class_id', 'page', 'term_id']))) }}" class="text-gray-600 hover:text-gray-800">Cancel</a>
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
