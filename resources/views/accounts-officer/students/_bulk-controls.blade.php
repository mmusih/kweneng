<section class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 dark:bg-brand-800 dark:border-brand-600">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 mb-5">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bulk Results Access</h3>
            <p class="text-sm text-gray-500 mt-1 dark:text-brand-300">
                Block students by their latest imported closing balance, or update everyone in the selected scope.
            </p>
        </div>
        <span
            class="inline-flex w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-200">
            Applies beyond the current page
        </span>
    </div>

    <form method="POST" action="{{ route('accounts-officer.students.bulk-fees-block') }}"
        onsubmit="return window.confirm(event.submitter?.dataset.confirm || 'Apply this bulk results-access update?')">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-end">
            <div class="xl:col-span-2">
                <label for="{{ $bulkControlId ?? 'bulk' }}-class-id"
                    class="block text-sm font-medium text-gray-700 dark:text-brand-200">
                    Student scope
                </label>
                <select id="{{ $bulkControlId ?? 'bulk' }}-class-id" name="class_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-brand-600 dark:bg-brand-900 dark:text-white">
                    <option value="">All students</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}"
                            @selected((string) old('class_id', request('class_id')) === (string) $class->id)>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $bulkControlId ?? 'bulk' }}-threshold"
                    class="block text-sm font-medium text-gray-700 dark:text-brand-200">
                    Owes more than (P)
                </label>
                <input id="{{ $bulkControlId ?? 'bulk' }}-threshold" type="number" name="threshold"
                    value="{{ old('threshold') }}" min="0" max="9999999999.99" step="0.01"
                    placeholder="e.g. 1000.00"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-brand-600 dark:bg-brand-900 dark:text-white">
            </div>

            <button type="submit" name="action" value="block_above_threshold"
                data-confirm="Block every student in the selected scope whose latest balance is above the entered amount?"
                class="inline-flex min-h-10 items-center justify-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-brand-800">
                Block above amount
            </button>

            <div class="grid grid-cols-2 gap-3">
                <button type="submit" name="action" value="block_all"
                    data-confirm="Block results access for every student in the selected scope?"
                    class="inline-flex min-h-10 items-center justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-brand-800">
                    Block all
                </button>
                <button type="submit" name="action" value="unblock_all"
                    data-confirm="Restore results access for every blocked student in the selected scope?"
                    class="inline-flex min-h-10 items-center justify-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-brand-800">
                    Unblock all
                </button>
            </div>
        </div>
    </form>

    <p class="mt-4 text-xs text-gray-500 dark:text-brand-400">
        “Block above amount” uses each student’s latest imported fee balance. Students without an imported balance are
        not included in threshold-based blocking.
    </p>
</section>
