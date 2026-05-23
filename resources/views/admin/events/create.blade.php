<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create New Event
            </h2>
            <a href="{{ route('admin.events.index') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Back to Events
            </a>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('admin.events.store') }}">
                        @csrf

                        <div class="space-y-6">
                            <!-- Title -->
                            <div>
                                <x-input-label for="title" :value="__('Event Title')" />
                                <x-text-input id="title" class="block mt-1 w-full" type="text" name="title"
                                    :value="old('title')" required autofocus />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <!-- Description -->
                            <div>
                                <x-input-label for="description" :value="__('Description')" />
                                <textarea id="description" name="description" rows="3"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Start Date & Time -->
                                <div>
                                    <x-input-label for="start_datetime" :value="__('Start Date & Time')" />
                                    <x-text-input id="start_datetime" class="block mt-1 w-full" type="datetime-local"
                                        name="start_datetime" :value="old('start_datetime')" required />
                                    <x-input-error :messages="$errors->get('start_datetime')" class="mt-2" />
                                </div>

                                <!-- End Date & Time -->
                                <div>
                                    <x-input-label for="end_datetime" :value="__('End Date & Time (Optional)')" />
                                    <x-text-input id="end_datetime" class="block mt-1 w-full" type="datetime-local"
                                        name="end_datetime" :value="old('end_datetime')" />
                                    <x-input-error :messages="$errors->get('end_datetime')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Type -->
                                <div>
                                    <x-input-label for="type" :value="__('Event Type')" />
                                    <select id="type" name="type"
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Type</option>
                                        <option value="holiday" {{ old('type') == 'holiday' ? 'selected' : '' }}>Holiday
                                        </option>
                                        <option value="exam" {{ old('type') == 'exam' ? 'selected' : '' }}>Exam
                                        </option>
                                        <option value="meeting" {{ old('type') == 'meeting' ? 'selected' : '' }}>Meeting
                                        </option>
                                        <option value="activity" {{ old('type') == 'activity' ? 'selected' : '' }}>
                                            Activity</option>
                                        <option value="ceremony" {{ old('type') == 'ceremony' ? 'selected' : '' }}>
                                            Ceremony</option>
                                        <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Other
                                        </option>
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                </div>

                                <!-- Visibility -->
                                <div>
                                    <x-input-label for="visibility" :value="__('Visibility')" />
                                    <select id="visibility" name="visibility"
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Visibility</option>
                                        <option value="all" {{ old('visibility') == 'all' ? 'selected' : '' }}>All
                                            Users</option>
                                        <option value="parents" {{ old('visibility') == 'parents' ? 'selected' : '' }}>
                                            Parents Only</option>
                                        <option value="teachers"
                                            {{ old('visibility') == 'teachers' ? 'selected' : '' }}>Teachers Only
                                        </option>
                                        <option value="students"
                                            {{ old('visibility') == 'students' ? 'selected' : '' }}>Students Only
                                        </option>
                                        <option value="specific_class"
                                            {{ old('visibility') == 'specific_class' ? 'selected' : '' }}>Specific
                                            Class</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Academic Year -->
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year (Optional)')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Academic Year</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('academic_year_id')" class="mt-2" />
                            </div>

                            <!-- Class (conditional) -->
                            <div id="class-field" class="hidden">
                                <x-input-label for="class_id" :value="__('Specific Class')" />
                                <select id="class_id" name="class_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('class_id')" class="mt-2" />
                            </div>

                            <!-- Options -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input id="is_all_day" type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        name="is_all_day" {{ old('is_all_day') ? 'checked' : '' }}>
                                    <label for="is_all_day" class="ml-2 block text-sm text-gray-900">
                                        {{ __('All Day Event') }}
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="is_recurring" type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        name="is_recurring" {{ old('is_recurring') ? 'checked' : '' }}>
                                    <label for="is_recurring" class="ml-2 block text-sm text-gray-900">
                                        {{ __('Recurring Event') }}
                                    </label>
                                </div>
                            </div>

                            <!-- Recurrence Pattern -->
                            <div id="recurrence-field" class="hidden">
                                <x-input-label for="recurrence_pattern" :value="__('Recurrence Pattern')" />
                                <x-text-input id="recurrence_pattern" class="block mt-1 w-full" type="text"
                                    name="recurrence_pattern" :value="old('recurrence_pattern')"
                                    placeholder="e.g., daily, weekly, monthly" />
                                <x-input-error :messages="$errors->get('recurrence_pattern')" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <a href="{{ route('admin.events.index') }}"
                                    class="mr-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    {{ __('Cancel') }}
                                </a>

                                <x-primary-button class="ml-4">
                                    {{ __('Create Event') }}
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const visibilitySelect = document.getElementById('visibility');
            const classField = document.getElementById('class-field');
            const recurrenceCheckbox = document.getElementById('is_recurring');
            const recurrenceField = document.getElementById('recurrence-field');

            // Toggle class field based on visibility
            visibilitySelect.addEventListener('change', function() {
                if (this.value === 'specific_class') {
                    classField.classList.remove('hidden');
                } else {
                    classField.classList.add('hidden');
                    document.getElementById('class_id').value = '';
                }
            });

            // Toggle recurrence field
            recurrenceCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    recurrenceField.classList.remove('hidden');
                } else {
                    recurrenceField.classList.add('hidden');
                    document.getElementById('recurrence_pattern').value = '';
                }
            });

            // Initialize on page load
            if (visibilitySelect.value === 'specific_class') {
                classField.classList.remove('hidden');
            }

            if (recurrenceCheckbox.checked) {
                recurrenceField.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>
