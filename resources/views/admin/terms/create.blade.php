<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Create Term
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.terms.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
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

                            <div>
                                <x-input-label for="name" :value="__('Term Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Example: Term 1, Semester 1, Quarter 1</p>
                            </div>

                            <div>
                                <x-input-label for="start_date" :value="__('Start Date')" />
                                <x-text-input id="start_date" class="block mt-1 w-full" type="date" name="start_date"
                                    :value="old('start_date')" required />
                                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="end_date" :value="__('End Date')" />
                                <x-text-input id="end_date" class="block mt-1 w-full" type="date" name="end_date"
                                    :value="old('end_date')" required />
                                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-10 border-t pt-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Report Card Text Settings</h3>
                            <p class="text-sm text-gray-500 mb-6">
                                These notes will appear on report cards for this term and can be customized per term.
                            </p>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <x-input-label for="report_title" :value="__('Report Title')" />
                                    <x-text-input id="report_title" class="block mt-1 w-full" type="text"
                                        name="report_title" :value="old('report_title', 'End of Term Report')" />
                                    <x-input-error :messages="$errors->get('report_title')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="report_footer_note" :value="__('Report Footer Note')" />
                                    <textarea id="report_footer_note" name="report_footer_note" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('report_footer_note') }}</textarea>
                                    <x-input-error :messages="$errors->get('report_footer_note')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="report_office_note" :value="__('Office Note')" />
                                    <textarea id="report_office_note" name="report_office_note" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('report_office_note') }}</textarea>
                                    <x-input-error :messages="$errors->get('report_office_note')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="report_extra_note" :value="__('Extra Note')" />
                                    <textarea id="report_extra_note" name="report_extra_note" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('report_extra_note') }}</textarea>
                                    <x-input-error :messages="$errors->get('report_extra_note')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8">
                            <a href="{{ route('admin.terms.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>

                            <x-primary-button>
                                {{ __('Create Term') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
