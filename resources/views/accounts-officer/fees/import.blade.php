<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Import Fee Balances</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-accounts-officer-dashboard-link />
                <a href="{{ route('accounts-officer.fees.index') }}"
                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Back to Fees</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-md px-4 py-3">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('accounts-officer.fees.import.store') }}"
                    enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <select name="academic_year_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Select academic year</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>{{ $year->year_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Term</label>
                        <select name="term_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Select term</option>
                            @foreach ($terms as $term)
                                <option value="{{ $term->id }}" @selected(old('term_id') == $term->id)>{{ $term->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Excel File (.xlsx)</label>
                        <input type="file" name="fee_file" accept=".xlsx" required
                            class="mt-1 block w-full text-sm text-gray-700">
                        <p class="mt-2 text-xs text-gray-500">
                            The importer reads Surname from column B, Student Names from column C, and Closing Balance
                            from column L.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('accounts-officer.fees.index') }}"
                            class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700">
                            Preview Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
