    <div x-data="timetablePreparation({{ ($embedded ?? false) ? 'preparationPayload' : Illuminate\Support\Js::from($preparation) }}, @js(route('admin.timetable.prepare.store', $setting)), { embedded: @js($embedded ?? false), classId: {{ ($embedded ?? false) ? 'preparationClassId' : 'null' }}, gridUrl: @js(route('admin.timetable.grid', ['setting' => $setting->id])) })" class="prep mx-auto w-full px-3 py-3" @keydown.escape.stop="joint ? joint = null : (embedded && close())" @keydown.tab="trapFocus($event)" x-cloak>
        <fieldset :disabled="busy" class="min-w-0">
        <div class="prep-panel mb-3 flex flex-wrap items-center gap-2">
            <label class="text-xs font-semibold">Class <select x-model="classId" @change="chooseClass()" class="prep-input"><option value="">Choose class</option><template x-for="klass in classes" :key="klass.id"><option :value="klass.id" x-text="klass.name"></option></template></select></label>
            <label class="min-w-40 flex-1"><span class="sr-only">Find subject or teacher</span><input type="search" x-model="search" placeholder="Find subject or teacher" class="prep-input w-full"></label>
            <button class="prep-secondary" @click="undo()" :disabled="!history.length || busy">Undo</button>
            <button class="prep-secondary" @click="save()" :disabled="busy || !classId" x-text="busy ? 'Saving…' : 'Save'"></button>
            <button class="prep-primary" @click="save(true)" :disabled="busy || !classId">Save &amp; timetable</button>
            <button x-show="embedded" class="prep-secondary" @click="close()">Close</button>
            <div class="flex w-full flex-wrap justify-between gap-2 text-xs text-slate-500 dark:text-brand-300"><span x-text="totals()"></span><span>Totals per {{ $setting->cycle_length }}-day cycle include placed cards. Increase a total to add cards to the tray.</span><span x-show="dirty" class="font-semibold text-amber-700 dark:text-amber-300">Unsaved changes</span></div>
        </div>
        <p role="alert" x-show="error" x-text="error" class="mb-4 rounded-lg border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900"></p>
        <p role="status" x-show="message" x-text="message" class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900"></p>
        <section x-show="staleUnits().length" class="prep-panel mb-4"><h2 class="font-bold">Assignments needing attention</h2><p class="mt-2 text-sm">These saved occurrences refer to removed or inactive teaching assignments. Restore the assignment or remove the affected occurrences.</p><template x-for="unit in staleUnits()" :key="unit.key"><div class="mt-3 flex items-center justify-between gap-3"><span class="text-sm" x-text="attendance(unit) + ' · ' + (unit.span === 2 ? 'Double' : 'Single') + (unit.placed ? ' · On timetable' : '')"></span><button class="prep-secondary" @click="removeStale(unit)">Remove occurrence</button></div></template></section>
        <div x-show="!classId" class="prep-panel py-16 text-center"><h2 class="text-xl font-semibold">Start with the teaching assignments you already have</h2><p class="mt-3 text-sm text-slate-500 dark:text-brand-300">Choose a class, set its singles and doubles, then select joint classes directly on its lesson cards.</p></div>
        <div x-show="classId" class="grid grid-cols-1 gap-3">
            <section class="prep-panel min-w-0">
                <div class="mb-2 flex items-center justify-between gap-2"><h2 class="text-sm font-bold">Lessons from teacher assignments</h2><span class="text-xs text-slate-500" x-text="visibleAssignments().length + ' assignments'"></span></div>
                <p x-show="!visibleAssignments().length" class="py-6 text-center text-sm">No matching assignments. Check this class’s subject and teacher assignments.</p>
                <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-300 text-slate-500 dark:text-brand-300"><tr><th class="p-2">Subject / teacher</th><th class="p-2">Singles</th><th class="p-2">Doubles</th><th class="p-2">Progress</th><th class="p-2"><span class="sr-only">Actions</span></th></tr></thead>
                    <template x-for="assignment in visibleAssignments()" :key="assignment.key">
                        <tbody class="border-b border-slate-200 dark:border-brand-600" :class="selected?.key === assignment.key ? 'bg-teal-50 dark:bg-brand-700' : ''">
                            <tr>
                                <td class="p-2"><strong class="block text-sm" x-text="assignment.subject"></strong><span class="text-slate-500 dark:text-brand-300" x-text="assignment.teacher"></span><span class="block text-[10px] text-indigo-700 dark:text-indigo-300" x-show="joinedClasses(assignment.key)" x-text="'Joint: ' + joinedClasses(assignment.key)"></span></td>
                                <td class="p-2"><input type="number" :aria-label="assignment.subject + ' singles'" min="0" max="2000" step="1" x-model.number="pattern(assignment.key).singles" @input="dirty = true" class="prep-input w-16"></td>
                                <td class="p-2"><input type="number" :aria-label="assignment.subject + ' doubles'" min="0" max="2000" step="1" x-model.number="pattern(assignment.key).doubles" @input="dirty = true" class="prep-input w-16"></td>
                                <td class="p-2"><span class="whitespace-nowrap" x-text="summary(assignment.key)"></span><span x-show="manual(assignment.key).cards" class="block text-[10px] text-slate-500 dark:text-brand-300" x-text="manual(assignment.key).cards + ' existing individual cards included'"></span></td>
                                <td class="p-2 text-right"><button class="prep-secondary" @click="expanded[assignment.key] = !expanded[assignment.key]" :aria-expanded="!!expanded[assignment.key]" x-text="expanded[assignment.key] ? 'Hide cards' : 'Cards'"></button></td>
                            </tr>
                            <tr x-show="expanded[assignment.key]"><td colspan="5" class="bg-slate-50 p-3 dark:bg-brand-900">
                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                    <button class="prep-secondary" @click="applyPattern(assignment.key)">Apply counts</button>
                                    <button class="prep-secondary" @click="applyToClass(assignment.key)">Use counts for class</button>
                                    <button class="prep-secondary" @click="openJoint({ kind: 'assignment', key: assignment.key })">Select joint classes for all cards</button>
                                    <label class="text-xs">Attendance — all cards <select class="prep-input" :value="groupValue(assignment.key)" @change="changeGroup(assignment.key, $event.target.value)"><option value="mixed" disabled>Mixed attendance — choose to apply to all</option><option value="">Whole class</option><template x-for="group in groupsFor(assignment.key)" :key="group.id"><option :value="group.id" x-text="group.name"></option></template></select></label>
                                </div>
                                <p x-show="assignment.existing" class="mb-2 text-xs text-slate-500 dark:text-brand-300">Existing individual cards are included in the totals and kept as they are. Use their timetable card menu to edit attendance or reduce them.</p>
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3"><template x-for="unit in occurrences(assignment.key)" :key="unit.key">@include('admin.timetable.partials.preparation-occurrence')</template></div>
                            </td></tr>
                        </tbody>
                    </template>
                </table>
                </div>
            </section>
        </div>
        <div x-show="joint" @keydown.escape.window="joint = null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="joint-title">
            <section class="prep-panel w-full max-w-lg" @click.outside="joint = null"><h2 id="joint-title" class="text-xl font-bold">Select joint classes</h2><p class="mt-2 text-sm text-slate-500 dark:text-brand-300">Choose another class with the same subject and teacher. Its matching card is combined with this lesson and will appear under both classes. You can add more classes in the same way.</p>
                <label class="mt-4 block text-sm font-semibold">Class and assignment<select class="prep-input mt-2 w-full" :value="joint?.target || ''" @change="joint.target = $event.target.value"><option value="">Choose a class</option><template x-for="option in jointOptions()" :key="option.key"><option :value="option.key" :disabled="jointOptionStatus(option).disabled" x-text="jointOptionLabel(option)"></option></template></select></label>
                <div class="mt-2 space-y-1 text-xs text-slate-500 dark:text-brand-300"><template x-for="option in jointOptions().filter(option => jointOptionStatus(option).label)" :key="option.key"><p x-text="option.class_name + ' — ' + jointOptionStatus(option).label"></p></template></div>
                <p x-show="!jointOptions().length" class="mt-3 text-sm text-amber-700 dark:text-amber-300">No matching assignments in another class. Assign the same subject and teacher to that class first.</p>
                <p x-show="error" x-text="error" class="mt-3 text-sm text-rose-700 dark:text-rose-300" role="alert"></p>
                <div class="mt-5 flex justify-end gap-3"><button class="prep-secondary" @click="joint = null">Cancel</button><button class="prep-primary" @click="join()">Join selected class</button></div>
            </section>
        </div>
        <style>
            .prep-panel{border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:10px}.prep-input{border:1px solid #94a3b8;border-radius:8px;background:transparent;min-height:32px;padding:5px;font-size:13px}.prep-primary,.prep-secondary{border-radius:8px;min-height:32px;padding:5px 12px;font-size:12px;font-weight:600}.prep-primary{background:#124e66;color:white}.prep-secondary{border:1px solid #94a3b8}.prep button:disabled{opacity:.45;cursor:not-allowed}.prep button:focus-visible,.prep select:focus-visible,.prep input:focus-visible{outline:2px solid #14b8a6;outline-offset:3px}.dark .prep-panel{background:#212a31;border-color:#52616c}.dark .prep-input option{background:#212a31;color:#fff}
        </style>
        </fieldset>
    </div>
