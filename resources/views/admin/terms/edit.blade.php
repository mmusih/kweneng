<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Edit Term
                </h2>
                <a href="{{ route('admin.terms.index') }}"
                    class="text-white hover:text-blue-100 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Terms
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.terms.update', $term) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="">Select Academic Year</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ old('academic_year_id', $term->academic_year_id) == $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('academic_year_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="name" :value="__('Term Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name', $term->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="start_date" :value="__('Start Date')" />
                                <x-text-input id="start_date" class="block mt-1 w-full" type="date" name="start_date"
                                    :value="old('start_date', $term->start_date->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="end_date" :value="__('End Date')" />
                                <x-text-input id="end_date" class="block mt-1 w-full" type="date" name="end_date"
                                    :value="old('end_date', $term->end_date->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required>
                                    <option value="active"
                                        {{ old('status', $term->status) == 'active' ? 'selected' : '' }}>
                                        Active
                                    </option>
                                    <option value="finalized"
                                        {{ old('status', $term->status) == 'finalized' ? 'selected' : '' }}>
                                        Finalized
                                    </option>
                                    <option value="locked"
                                        {{ old('status', $term->status) == 'locked' ? 'selected' : '' }}>
                                        Locked
                                    </option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-10 border-t pt-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Report Card Text Settings</h3>
                            <p class="text-sm text-gray-500 mb-6">
                                These notes will appear on report cards for this term and can be changed whenever
                                needed.
                            </p>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <x-input-label for="report_title" :value="__('Report Title')" />
                                    <x-text-input id="report_title" class="block mt-1 w-full" type="text"
                                        name="report_title" :value="old('report_title', $term->report_title)" />
                                    <x-input-error :messages="$errors->get('report_title')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="report_footer_note" :value="__('Report Footer Note')" />
                                    <textarea id="report_footer_note" name="report_footer_note" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('report_footer_note', $term->report_footer_note) }}</textarea>
                                    <x-input-error :messages="$errors->get('report_footer_note')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="report_office_note" :value="__('Office Note')" />
                                    <textarea id="report_office_note" name="report_office_note" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('report_office_note', $term->report_office_note) }}</textarea>
                                    <x-input-error :messages="$errors->get('report_office_note')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="report_extra_note" :value="__('Extra Note')" />
                                    <textarea id="report_extra_note" name="report_extra_note" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('report_extra_note', $term->report_extra_note) }}</textarea>
                                    <x-input-error :messages="$errors->get('report_extra_note')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8">
                            <a href="{{ route('admin.terms.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>

                            <x-primary-button>
                                {{ __('Update Term') }}
                            </x-primary-button>
                        </div>
                    </form>

                    {{-- EXAM STAGE LOCKS --}}
                    <div class="mt-10 pt-8 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Exam Stage Locks</h3>
                        <p class="text-sm text-gray-500 mb-6">
                            These locks control whether teachers can edit Midterm or Endterm marks separately.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="border rounded-lg p-5 bg-blue-50">
                                <p class="text-sm text-gray-500">Midterm Lock Status</p>
                                <p
                                    class="text-lg font-bold mt-2 {{ $term->midterm_locked ? 'text-blue-700' : 'text-gray-800' }}">
                                    {{ $term->midterm_locked ? 'Locked' : 'Unlocked' }}
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    Teachers {{ $term->midterm_locked ? 'cannot' : 'can' }} edit midterm scores.
                                </p>

                                <div class="mt-4">
                                    @if (!$term->midterm_locked)
                                        <form action="{{ route('admin.terms.lock-midterm', $term) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"
                                                onclick="return confirm('Lock midterm marks? Teachers will no longer edit midterm scores.')">
                                                Lock Midterm
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.terms.unlock-midterm', $term) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-400 hover:bg-blue-500"
                                                onclick="return confirm('Unlock midterm marks?')">
                                                Unlock Midterm
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="border rounded-lg p-5 bg-green-50">
                                <p class="text-sm text-gray-500">Endterm Lock Status</p>
                                <p
                                    class="text-lg font-bold mt-2 {{ $term->endterm_locked ? 'text-green-700' : 'text-gray-800' }}">
                                    {{ $term->endterm_locked ? 'Locked' : 'Unlocked' }}
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    Teachers {{ $term->endterm_locked ? 'cannot' : 'can' }} edit endterm scores.
                                </p>

                                <div class="mt-4">
                                    @if (!$term->endterm_locked)
                                        <form action="{{ route('admin.terms.lock-endterm', $term) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700"
                                                onclick="return confirm('Lock endterm marks? Teachers will no longer edit endterm scores.')">
                                                Lock Endterm
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.terms.unlock-endterm', $term) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-400 hover:bg-green-500"
                                                onclick="return confirm('Unlock endterm marks?')">
                                                Unlock Endterm
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TERM ACTIONS --}}
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Term Actions</h3>

                        @if ($term->status === 'active')
                            <div class="space-y-3">
                                <form action="{{ route('admin.terms.finalize', $term) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                                        onclick="return confirm('Finalize this term? Students will no longer be able to submit assignments.')">
                                        Finalize Term
                                    </button>
                                </form>

                                <form action="{{ route('admin.terms.lock', $term) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                        onclick="return confirm('Lock this term fully? This action cannot be undone easily and will freeze all term activity.')">
                                        Lock Entire Term
                                    </button>
                                </form>
                            </div>
                        @elseif($term->status === 'finalized')
                            <form action="{{ route('admin.terms.lock', $term) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                    onclick="return confirm('Lock this term fully? This action cannot be undone easily and will freeze all term activity.')">
                                    Lock Entire Term
                                </button>
                            </form>
                        @elseif($term->status === 'locked')
                            <p class="text-sm text-gray-500">This term is locked and cannot be modified.</p>
                        @endif
                    </div>

                    {{-- DANGER ZONE --}}
                    @if ($term->status !== 'locked')
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Danger Zone</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Deleting this term will permanently remove it from the system.
                            </p>

                            <form method="POST" action="{{ route('admin.terms.destroy', $term) }}" class="mt-4">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Are you sure you want to delete this term? This action cannot be undone.')">
                                    {{ __('Delete Term') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Cannot Delete</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                This term is locked and cannot be deleted.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
