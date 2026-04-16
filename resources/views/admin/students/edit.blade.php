<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Edit Student
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
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

                    <form method="POST" action="{{ route('admin.students.update', $student) }}"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Full Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name', $student->user->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('Email Address')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                                    :value="old('email', $student->user->email)" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">
                                    Use the reset password action if the student needs a new temporary password. The
                                    student will be required to change it on first login.
                                </p>
                            </div>

                            <div>
                                <x-input-label for="admission_no" :value="__('Admission Number')" />
                                <x-text-input id="admission_no" class="block mt-1 w-full" type="text"
                                    name="admission_no" :value="old('admission_no', $student->admission_no)" required />
                                <x-input-error :messages="$errors->get('admission_no')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="gender" :value="__('Gender')" />
                                <select id="gender" name="gender"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Gender</option>
                                    <option value="male"
                                        {{ old('gender', $student->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female"
                                        {{ old('gender', $student->gender) == 'female' ? 'selected' : '' }}>Female
                                    </option>
                                </select>
                                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                                <x-text-input id="date_of_birth" class="block mt-1 w-full" type="date"
                                    name="date_of_birth" :value="old('date_of_birth', $student->date_of_birth->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="current_class_id" :value="__('Current Class')" />
                                <select id="current_class_id" name="current_class_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ old('current_class_id', $student->current_class_id) == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('current_class_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="photo" :value="__('Passport Photo')" />
                                <x-text-input id="photo" class="block mt-1 w-full" type="file" name="photo"
                                    accept="image/*" />
                                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">JPEG, PNG or WebP. Max 2MB.</p>

                                @if ($student->photo)
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">Current photo:</p>
                                        <div class="mt-1">
                                            <img src="{{ Storage::url($student->photo) }}" alt="Current photo"
                                                class="h-24 w-24 object-cover rounded-full border-2 border-gray-300">
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">Leave blank to keep current photo.</p>
                                    </div>
                                @endif
                            </div>

                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input id="results_access" type="checkbox" name="results_access" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        {{ old('results_access', $student->results_access) ? 'checked' : '' }}>
                                    <x-input-label for="results_access" :value="__('Allow Access to Results')" class="ml-2" />
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input id="fees_blocked" type="checkbox" name="fees_blocked" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        {{ old('fees_blocked', $student->fees_blocked) ? 'checked' : '' }}>
                                    <x-input-label for="fees_blocked" :value="__('Block Fees Access')" class="ml-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-3">
                            <form action="{{ route('admin.students.reset-password', $student) }}" method="POST"
                                onsubmit="return confirm('Reset password for this student?');">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-md shadow-sm transition">
                                    Reset Password
                                </button>
                            </form>

                            <a href="{{ route('admin.students.index') }}" class="text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>

                            <x-primary-button>
                                {{ __('Update Student') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
