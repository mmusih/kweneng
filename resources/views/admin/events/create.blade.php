<x-app-layout>
    @php($eventRoutePrefix = $eventRoutePrefix ?? match (true) { request()->routeIs('headmaster.*') => 'headmaster', request()->routeIs('office.*') => 'office', request()->routeIs('register-officer.*') => 'register-officer', default => 'admin' })

    <x-slot name="header">
        <div class="mt-16 p-5 rounded-2xl kw-page-header flex justify-between items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-white leading-tight">Create Calendar Event</h2>
                <p class="text-sm text-white/80 mt-1">Choose type Holiday when attendance registers must show H.</p>
            </div>
            <a href="{{ route($eventRoutePrefix . '.events.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-slate-800 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-slate-100">
                Back to Events
            </a>
        </div>
    </x-slot>

    <div class="py-12 kw-soft-section min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white kw-panel overflow-hidden">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if ($errors->any())
                        <div class="mb-6 rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route($eventRoutePrefix . '.events.store') }}">
                        @csrf

                        <div class="space-y-6">
                            <div>
                                <x-input-label for="title" :value="__('Event Title')" />
                                <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required autofocus />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="description" :value="__('Description')" />
                                <textarea id="description" name="description" rows="3" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="start_datetime" :value="__('Start Date & Time')" />
                                    <x-text-input id="start_datetime" class="block mt-1 w-full" type="datetime-local" name="start_datetime" :value="old('start_datetime')" required />
                                    <x-input-error :messages="$errors->get('start_datetime')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="end_datetime" :value="__('End Date & Time (Optional)')" />
                                    <x-text-input id="end_datetime" class="block mt-1 w-full" type="datetime-local" name="end_datetime" :value="old('end_datetime')" />
                                    <x-input-error :messages="$errors->get('end_datetime')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="type" :value="__('Event Type')" />
                                    <select id="type" name="type" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="">Select Type</option>
                                        @foreach (['holiday' => 'Holiday', 'exam' => 'Exam', 'meeting' => 'Meeting', 'activity' => 'Activity', 'ceremony' => 'Ceremony', 'other' => 'Other'] as $value => $label)
                                            <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <p id="holiday-help" class="hidden text-sm text-yellow-700 mt-2">Holiday events automatically mark matching attendance register dates as H and teachers cannot save attendance for those days.</p>
                                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="visibility" :value="__('Visibility / Attendance Scope')" />
                                    <select id="visibility" name="visibility" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="">Select Visibility</option>
                                        @foreach (['all' => 'All Users / Whole School', 'parents' => 'Parents Only', 'teachers' => 'Teachers Only', 'students' => 'Students Only', 'specific_class' => 'Specific Class'] as $value => $label)
                                            <option value="{{ $value }}" {{ old('visibility', 'all') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year (Optional)')" />
                                <select id="academic_year_id" name="academic_year_id" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All academic years</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>{{ $year->year_name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Leaving this blank allows the holiday/event to apply across years where relevant.</p>
                                <x-input-error :messages="$errors->get('academic_year_id')" class="mt-2" />
                            </div>

                            <div id="class-field" class="hidden">
                                <x-input-label for="class_id" :value="__('Specific Class')" />
                                <select id="class_id" name="class_id" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }} {{ $class->academicYear?->year_name ? ' - ' . $class->academicYear->year_name : '' }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('class_id')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <label class="flex items-center">
                                    <input id="is_all_day" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_all_day" value="1" {{ old('is_all_day') ? 'checked' : '' }}>
                                    <span class="ml-2 block text-sm text-gray-900">All Day Event</span>
                                </label>
                                <label class="flex items-center">
                                    <input id="is_recurring" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_recurring" value="1" {{ old('is_recurring') ? 'checked' : '' }}>
                                    <span class="ml-2 block text-sm text-gray-900">Recurring Event</span>
                                </label>
                            </div>

                            <div id="recurrence-field" class="hidden">
                                <x-input-label for="recurrence_pattern" :value="__('Recurrence Pattern')" />
                                <select id="recurrence_pattern" name="recurrence_pattern" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Pattern</option>
                                    @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('recurrence_pattern') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('recurrence_pattern')" class="mt-2" />
                            </div>

                            <div class="flex justify-end gap-3">
                                <a href="{{ route($eventRoutePrefix . '.events.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Cancel</a>
                                <x-primary-button>Create Event</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const visibility = document.getElementById('visibility');
            const classField = document.getElementById('class-field');
            const type = document.getElementById('type');
            const isAllDay = document.getElementById('is_all_day');
            const isRecurring = document.getElementById('is_recurring');
            const recurrenceField = document.getElementById('recurrence-field');
            const holidayHelp = document.getElementById('holiday-help');

            function updateClassField() { classField.classList.toggle('hidden', visibility.value !== 'specific_class'); }
            function updateRecurrence() { recurrenceField.classList.toggle('hidden', !isRecurring.checked); }
            function updateHoliday() {
                const holiday = type.value === 'holiday';
                holidayHelp.classList.toggle('hidden', !holiday);
                if (holiday) {
                    isAllDay.checked = true;
                    isRecurring.checked = false;
                    updateRecurrence();
                }
            }

            visibility.addEventListener('change', updateClassField);
            type.addEventListener('change', updateHoliday);
            isRecurring.addEventListener('change', updateRecurrence);
            updateClassField();
            updateHoliday();
            updateRecurrence();
        });
    </script>
</x-app-layout>
