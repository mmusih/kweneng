<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">Update Student Information</h2>
                <p class="text-blue-100 text-sm mt-1">{{ $student->user->name ?? 'Student' }} · {{ $student->currentClass->name ?? 'No class assigned' }}</p>
            </div>
            <a href="{{ route('parent.dashboard') }}" class="text-white hover:text-blue-100 text-sm font-medium">Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('parent.children.profile.update', $student) }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <section>
                        <div class="mb-4 border-b border-gray-200 pb-3">
                            <h3 class="text-lg font-semibold text-gray-900">Identity Information</h3>
                            <p class="text-sm text-gray-500 mt-1">This replaces the old admission number requirement.</p>
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
                        <div class="mb-4 border-b border-gray-200 pb-3">
                            <h3 class="text-lg font-semibold text-gray-900">Emergency Contact</h3>
                            <p class="text-sm text-gray-500 mt-1">This helps the school contact the right person quickly in an emergency.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="emergency_contact_name" :value="__('Contact Name')" />
                                <x-text-input id="emergency_contact_name" class="block mt-1 w-full" type="text" name="emergency_contact_name" :value="old('emergency_contact_name', $student->emergency_contact_name)" required />
                                <x-input-error :messages="$errors->get('emergency_contact_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="emergency_contact_relationship" :value="__('Relationship')" />
                                <x-text-input id="emergency_contact_relationship" class="block mt-1 w-full" type="text" name="emergency_contact_relationship" :value="old('emergency_contact_relationship', $student->emergency_contact_relationship)" required />
                                <x-input-error :messages="$errors->get('emergency_contact_relationship')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="emergency_contact_phone" :value="__('Primary Phone')" />
                                <x-text-input id="emergency_contact_phone" class="block mt-1 w-full" type="text" name="emergency_contact_phone" :value="old('emergency_contact_phone', $student->emergency_contact_phone)" required />
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

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('parent.dashboard') }}" class="text-gray-600 hover:text-gray-800">Cancel</a>
                        <x-primary-button>{{ __('Save Information') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
