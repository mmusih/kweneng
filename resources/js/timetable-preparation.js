export function timetablePreparation(initial, saveUrl, options = {}) {
    return {
        ...initial, saveUrl, classId: '', selected: null, dragging: null, history: [], error: '', message: '', busy: false,
        patterns: {}, expanded: {}, joint: null, dirty: false, search: '', ...options,
        init() {
            this.units = JSON.parse(JSON.stringify(initial.units));
            this.assignments = [...new Map(this.assignments.map(a => [a.key, a])).values()];
            const classId = Number(options.classId || new URLSearchParams(window.location.search).get('class_id') || this.classes[0]?.id);
            if (this.classes.some(c => c.id === classId)) {
                this.$nextTick(() => { this.classId = String(classId); this.chooseClass(); });
            }
            if (this.embedded) this.$nextTick(() => this.$root.querySelector('select')?.focus());
            this.unloadHandler = event => {
                if (this.dirty) { event.preventDefault(); event.returnValue = ''; }
            };
            window.addEventListener('beforeunload', this.unloadHandler);
        },
        destroy() { window.removeEventListener('beforeunload', this.unloadHandler); },
        id() { return crypto.randomUUID(); },
        lookupAssignment(key) { return this.assignments.find(a => a.key === key); },
        classAssignments() { return [...new Map(this.assignments.filter(a => a.class_id === Number(this.classId)).map(a => [a.key, a])).values()]; },
        visibleAssignments() { const q = this.search.trim().toLowerCase(); return this.classAssignments().filter(a => !q || `${a.subject} ${a.teacher}`.toLowerCase().includes(q)); },
        manual(key) { return this.lookupAssignment(key)?.manual || { singles: 0, doubles: 0, cards: 0, periods: 0, placed: 0 }; },
        summary(key) {
            const units = this.occurrences(key), manual = this.manual(key);
            const placed = manual.placed + units.filter(u => u.placed).length;
            const total = manual.cards + units.length;
            const draft = units.filter(u => !u.saved && !u.placed).length;
            const pattern = this.patterns[key];
            const pending = pattern ? Number(pattern.singles) + Number(pattern.doubles) - manual.singles - manual.doubles - units.length : 0;
            const changes = draft + pending;
            return `${total} ${total === 1 ? 'card' : 'cards'} · ${placed} placed · ${total - placed - draft} in tray`
                + (changes ? ` · ${changes > 0 ? '+' : ''}${changes} on save` : '');
        },
        occurrences(key) { return this.units.filter(u => u.sources.some(s => s.key === key)); },
        belongs(unit) { return unit.sources.some(s => this.lookupAssignment(s.key)?.class_id === Number(this.classId)); },
        title(unit) { return this.lookupAssignment(unit.sources[0].key)?.subject || 'Removed assignment'; },
        teacher(unit) { return this.lookupAssignment(unit.sources[0].key)?.teacher || 'Assignment unavailable'; },
        attendance(unit) { return unit.sources.map(s => this.lookupAssignment(s.key)?.class_name || s.key).join(' + '); },
        joinedClasses(key) {
            return [...new Set(this.occurrences(key).filter(u => u.sources.length > 1)
                .flatMap(u => u.sources.filter(s => s.key !== key).map(s => this.lookupAssignment(s.key)?.class_name))
                .filter(Boolean))].join(', ');
        },
        groupsFor(key) { return this.classes.find(c => c.id === this.lookupAssignment(key)?.class_id)?.groups || []; },
        pattern(key) {
            if (!this.patterns[key]) {
                const units = this.occurrences(key);
                const manual = this.manual(key);
                this.patterns[key] = { singles: manual.singles + units.filter(u => u.span === 1).length, doubles: manual.doubles + units.filter(u => u.span === 2).length };
            }
            return this.patterns[key];
        },
        checkpoint() {
            this.history.push(JSON.stringify(this.units));
            if (this.history.length > 30) this.history.shift();
            this.error = ''; this.message = ''; this.dirty = true;
        },
        undo() {
            if (!this.history.length) return;
            this.units = JSON.parse(this.history.pop()); this.patterns = {}; this.error = ''; this.dirty = true;
        },
        chooseClass() {
            this.search = '';
            if (this.version > 0) return;
            const missing = this.classAssignments().filter(a => !a.existing && !this.occurrences(a.key).length && !this.initialized?.[a.key]);
            if (missing.length) {
                this.checkpoint();
                missing.forEach(a => this.units.push(this.newUnit(a.key, 1)));
                missing.forEach(a => { (this.initialized ||= {})[a.key] = true; });
            }
        },
        newUnit(key, span) { return { key: this.id(), span, sources: [{ key, group_id: null }], split_key: null, room_id: null, placed: false }; },
        applyPattern(key, record = true) {
            const p = this.pattern(key), counts = [Number(p.singles), Number(p.doubles)];
            const manual = this.manual(key);
            if (counts.some(n => !Number.isInteger(n) || n < 0 || n > 2000)) {
                this.error = 'Use whole numbers between 0 and 2000.'; return false;
            }
            counts[0] -= manual.singles; counts[1] -= manual.doubles;
            if (counts.some(n => n < 0)) {
                this.error = 'These totals include existing individual lessons. Edit those lessons on the timetable to reduce them.'; return false;
            }
            const removed = [];
            for (const span of [1, 2]) {
                const units = this.occurrences(key).filter(u => u.span === span);
                const excess = Math.max(0, units.length - counts[span - 1]);
                const free = units.filter(u => !u.placed && !u.split_key && u.sources.length === 1);
                if (free.length < excess) {
                    this.error = 'Keep the placed, split and joint cards in the total. Return placed cards to the tray or unlink them before reducing further.'; return false;
                }
                if (excess) removed.push(...free.slice(-excess));
            }
            if (!removed.length && [1, 2].every(span => this.occurrences(key).filter(u => u.span === span).length === counts[span - 1])) { delete this.patterns[key]; return true; }
            if (record) this.checkpoint();
            this.units = this.units.filter(u => !removed.includes(u));
            [1, 2].forEach(span => {
                let count = this.occurrences(key).filter(u => u.span === span).length;
                const group = this.occurrences(key).flatMap(u => u.sources).find(s => s.key === key)?.group_id || null;
                while (count++ < counts[span - 1]) {
                    const unit = this.newUnit(key, span); unit.sources[0].group_id = group; this.units.push(unit);
                }
            });
            delete this.patterns[key];
            return true;
        },
        applyToClass(key) {
            const pattern = { ...this.pattern(key) };
            const before = JSON.stringify(this.units);
            this.checkpoint();
            for (const a of this.classAssignments()) {
                this.patterns[a.key] = { ...pattern };
                if (!this.applyPattern(a.key, false)) {
                    this.units = JSON.parse(before); this.history.pop(); this.patterns = {}; return;
                }
            }
        },
        select(kind, key) { this.selected = { kind, key }; },
        start(event, kind, key) {
            if (this.busy) { event.preventDefault(); return; }
            this.dragging = { kind, key }; this.selected = this.dragging;
            event.dataTransfer.setData('text/plain', JSON.stringify(this.dragging)); event.dataTransfer.effectAllowed = 'move';
        },
        picked(payload = this.selected) {
            if (!payload) return [];
            return payload.kind === 'assignment' ? this.occurrences(payload.key) : this.units.filter(u => u.key === payload.key);
        },
        dropSplit(event, row = null) { event.preventDefault(); this.addSplit(row, this.dragging); this.dragging = null; },
        addSplit(row = null, payload = this.selected) {
            const picked = this.picked(payload);
            if (!picked.length) { this.error = 'Select a lesson tile or occurrence first.'; return; }
            let anchors = row ? [this.units.find(u => u.split_key === row)] : [];
            if (row && payload.kind === 'assignment') anchors = this.occurrences(anchors[0].sources[0].key);
            const used = new Set(); const pairs = [];
            for (const unit of picked) {
                const anchor = row ? anchors.find(a => a.span === unit.span && !used.has(a.key)) : null;
                if (row && !anchor) { this.error = 'Patterns differ. Expand the tiles and link matching single or double occurrences individually.'; return; }
                if (anchor && anchor.key === unit.key) { this.error = 'Choose a different lesson for this split.'; return; }
                if (unit.placed || anchor?.placed) { this.error = 'Return these cards to the tray before changing their split.'; return; }
                if (anchor) used.add(anchor.key);
                pairs.push([unit, anchor]);
            }
            this.checkpoint();
            pairs.forEach(([unit, anchor]) => {
                const split = anchor?.split_key || this.id();
                if (anchor) anchor.split_key = split;
                unit.split_key = split;
            });
            this.selected = null;
        },
        splitRows() {
            const keys = [...new Set(this.units.filter(u => u.split_key && this.belongs(u)).map(u => u.split_key))];
            return keys.map(key => ({ key, units: this.units.filter(u => u.split_key === key) }));
        },
        unlinkSplit(unit) {
            if (unit.placed) { this.error = 'Return the linked cards to the tray first.'; return; }
            this.checkpoint(); const old = unit.split_key; unit.split_key = null;
            const remaining = this.units.filter(u => u.split_key === old);
            if (remaining.length === 1) remaining[0].split_key = null;
        },
        returnToLessons(event) {
            event.preventDefault();
            const picked = this.picked(this.dragging); this.dragging = null;
            if (picked.some(u => u.placed)) { this.error = 'Return placed cards to the tray first.'; return; }
            if (!picked.some(u => u.split_key)) return;
            this.checkpoint();
            const keys = new Set(picked.map(u => u.split_key).filter(Boolean));
            picked.forEach(u => u.split_key = null);
            keys.forEach(key => { const remaining = this.units.filter(u => u.split_key === key); if (remaining.length === 1) remaining[0].split_key = null; });
        },
        openJoint(payload = this.selected) {
            if (!this.applyPendingPatterns()) return;
            const units = this.picked(payload);
            if (!units.length) { this.error = 'Select a lesson tile or occurrence first.'; return; }
            if (units.some(u => u.placed)) { this.error = 'Return these cards to the tray before joining them.'; return; }
            this.joint = { payload, target: '' }; this.error = '';
        },
        dropJoint(event) { event.preventDefault(); this.openJoint(this.dragging); this.dragging = null; },
        jointOptions() {
            if (!this.joint) return [];
            const units = this.picked(this.joint.payload), a = this.lookupAssignment(units[0]?.sources[0]?.key);
            const origin = this.joint.payload.kind === 'assignment'
                ? this.lookupAssignment(this.joint.payload.key)?.class_id : Number(this.classId);
            return this.assignments.filter(b => a && b.subject_id === a.subject_id && b.teacher_id === a.teacher_id && b.class_id !== (origin || a.class_id))
                .sort((a, b) => a.class_name.localeCompare(b.class_name, undefined, { numeric: true }));
        },
        jointOptionStatus(assignment) {
            const units = this.joint ? this.picked(this.joint.payload) : [];
            const needed = units.filter(u => !u.sources.some(s => this.lookupAssignment(s.key)?.class_id === assignment.class_id));
            if (!needed.length) return { disabled: true, label: 'Already joined' };
            const targets = this.occurrences(assignment.key).filter(u => !units.includes(u));
            if (!targets.length && !assignment.existing) return { disabled: false, label: '' };
            const free = [...targets.filter(u => !u.placed)];
            for (const unit of needed) {
                const index = free.findIndex(t => t.span === unit.span);
                if (index < 0) return { disabled: true, label: assignment.existing && !targets.length
                    ? 'Existing individual lessons — use their card menu'
                    : 'Needs matching unplaced cards' };
                free.splice(index, 1);
            }
            return { disabled: false, label: '' };
        },
        jointOptionLabel(assignment) {
            const status = this.jointOptionStatus(assignment);
            return assignment.class_name + ' · ' + assignment.subject + ' · ' + assignment.teacher
                + (status.label ? ' — ' + status.label : '');
        },
        join() {
            const key = this.joint?.target;
            const assignment = this.jointOptions().find(a => a.key === key);
            if (!assignment) { this.error = 'Choose a matching assignment from another class.'; return; }
            const status = this.jointOptionStatus(assignment);
            if (status.disabled) { this.error = status.label; return; }
            const all = this.picked(this.joint.payload);
            const picked = all.filter(u => !u.sources.some(s => this.lookupAssignment(s.key)?.class_id === assignment.class_id));
            const targets = this.occurrences(key).filter(u => !all.includes(u)), used = new Set(), pairs = [];
            for (const unit of picked) {
                const target = targets.find(t => t.span === unit.span && !t.placed && !used.has(t.key));
                if (targets.length && !target) { this.error = 'The other class has no matching free occurrences. Adjust its pattern, or join individual occurrences.'; return; }
                if (target?.split_key && unit.split_key && target.split_key !== unit.split_key) {
                    this.error = 'These occurrences belong to different split rows. Unlink one row first.'; return;
                }
                if (target) used.add(target.key);
                const origin = unit.sources.find(source => source.group_id);
                const originGroup = origin && this.groupsFor(origin.key).find(group => group.id === origin.group_id);
                const match = originGroup && this.groupsFor(key).find(group =>
                    (originGroup.shared_key && group.shared_key === originGroup.shared_key) || group.name === originGroup.name);
                const additions = (target?.sources || [{ key, group_id: null }]).map(source =>
                    source.key === key && !source.group_id && match ? { ...source, group_id: match.id } : source);
                const sources = [...unit.sources, ...additions];
                const classes = sources.map(s => this.lookupAssignment(s.key)?.class_id);
                if (new Set(classes).size !== classes.length) { this.error = 'This would include the same class twice.'; return; }
                pairs.push([unit, target, sources]);
            }
            this.checkpoint();
            pairs.forEach(([unit, target, sources]) => { unit.sources = sources; unit.split_key ||= target?.split_key || null; });
            this.units = this.units.filter(u => !used.has(u.key));
            this.patterns = {}; this.joint = null; this.selected = null;
        },
        unjoin(unit) {
            if (unit.placed) { this.error = 'Return the card to the tray first.'; return; }
            if (!this.applyPendingPatterns()) return;
            this.checkpoint();
            const others = unit.sources.splice(1);
            others.forEach(source => this.units.push({ ...this.newUnit(source.key, unit.span), sources: [source], room_id: unit.room_id }));
            this.patterns = {};
        },
        changeGroup(key, groupId) {
            if (this.occurrences(key).some(u => u.placed)) { this.error = 'Return this assignment’s cards to the tray before changing attendance.'; return; }
            this.checkpoint();
            this.occurrences(key).forEach(u => u.sources.filter(s => s.key === key).forEach(s => s.group_id = Number(groupId) || null));
        },
        changeCardGroup(unit, source, value) {
            if (unit.placed) { this.error = 'Use this card’s attendance menu on the grid, or return it to the tray first.'; return; }
            this.checkpoint(); source.group_id = Number(value) || null;
        },
        groupValue(key) {
            const values = [...new Set(this.occurrences(key).flatMap(u => u.sources).filter(s => s.key === key).map(s => s.group_id || ''))];
            return values.length > 1 ? 'mixed' : (values[0] || '');
        },
        setRoom(unit, value) {
            if (unit.placed) { this.error = 'Return this card to the tray before changing its room.'; return; }
            this.checkpoint(); unit.room_id = Number(value) || null;
        },
        jointUnits() { return this.units.filter(u => u.sources.length > 1 && !u.split_key && this.belongs(u)); },
        staleUnits() { return this.units.filter(u => u.sources.some(s => !this.lookupAssignment(s.key))); },
        removeStale(unit) {
            if (unit.placed) { this.error = 'Return the affected cards to the tray before removing an unavailable assignment.'; return; }
            this.checkpoint(); this.units = this.units.filter(u => u.key !== unit.key);
            if (unit.split_key) {
                const remaining = this.units.filter(u => u.split_key === unit.split_key);
                if (remaining.length === 1) remaining[0].split_key = null;
            }
        },
        capacityUsed() {
            let whole = 0;
            const divisions = {};
            const add = (periods, groups) => {
                if (!groups.length || groups.some(g => g.entire_class)) { whole += periods; return; }
                groups.forEach(g => {
                    const division = g.division_id ?? 'group-' + g.id;
                    divisions[division] ||= {};
                    divisions[division][g.id] = (divisions[division][g.id] || 0) + periods;
                });
            };
            for (const lesson of this.manual_lessons || []) {
                if (lesson.class_ids.includes(Number(this.classId))) add(lesson.cards * lesson.span,
                    lesson.groups.filter(g => Number(g.class_id) === Number(this.classId)));
            }
            const rows = {};
            this.units.filter(u => u.split_key).forEach(u => (rows[u.split_key] ||= []).push(u));
            this.units.filter(u => this.belongs(u)).forEach(u => {
                const source = u.sources.find(s => this.lookupAssignment(s.key)?.class_id === Number(this.classId));
                let group = this.groupsFor(source.key).find(g => Number(g.id) === Number(source.group_id));
                if (!group && u.split_key) {
                    const sources = rows[u.split_key].flatMap(member => member.sources);
                    const explicit = sources.filter(s => this.lookupAssignment(s.key)?.class_id === Number(this.classId))
                        .map(s => this.groupsFor(s.key).find(g => Number(g.id) === Number(s.group_id))).find(Boolean);
                    group = { id: source.key, division_id: explicit?.division_id ?? 'draft-' + [...new Set(sources.map(s => s.key))].sort().join('|') };
                }
                add(u.span, group ? [group] : []);
            });
            return whole + Object.values(divisions).reduce((sum, groups) => sum + Math.max(...Object.values(groups)), 0);
        },
        totals() {
            const cards = this.units.filter(u => this.belongs(u)).length + (this.manual_lessons || [])
                .filter(l => l.class_ids.includes(Number(this.classId))).reduce((sum, l) => sum + l.cards, 0);
            return cards + ' cards · ' + this.capacityUsed() + ' / ' + (this.capacity ?? 40) + ' grid periods · parallel options count together' + (this.capacityUsed() > this.capacity ? ' · extra cards can stay in the tray' : '');
        },
        close() {
            if (this.busy || (this.dirty && !window.confirm('Discard unsaved lesson changes?'))) return;
            this.$dispatch('preparation-close');
        },
        trapFocus(event) {
            if (!this.embedded) return;
            const controls = [...this.$root.querySelectorAll('button, input, select, a[href]')].filter(el => !el.disabled && el.getClientRects().length);
            const first = controls[0], last = controls[controls.length - 1];
            if (event.shiftKey && event.target === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && event.target === last) { event.preventDefault(); first?.focus(); }
        },
        applyPendingPatterns() {
            const before = JSON.stringify(this.units), pending = JSON.parse(JSON.stringify(this.patterns));
            for (const key of Object.keys(pending)) {
                if (!this.applyPattern(key, false)) { this.units = JSON.parse(before); this.patterns = pending; return false; }
            }
            if (JSON.stringify(this.units) !== before) { this.history.push(before); this.dirty = true; }
            return true;
        },
        async save(returnToGrid = false) {
            if (this.busy) return;
            this.error = ''; this.message = '';
            if (!this.applyPendingPatterns()) return;
            const rows = [...new Set(this.units.map(u => u.split_key).filter(Boolean))];
            if (rows.some(key => this.units.filter(u => u.split_key === key).length < 2)) {
                this.error = 'An existing split is incomplete. Correct it in the timetable split controls before saving.'; return;
            }
            this.busy = true;
            try {
                const response = await fetch(this.saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ version: this.version, units: this.units, manual_baselines: Object.fromEntries(this.assignments.filter(a => a.manual?.signature).map(a => [a.key, a.manual.signature])) }) });
                const result = await response.json();
                if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Could not save cards.');
                this.manual_lessons = result.manual_lessons || []; this.units = result.units; this.assignments = result.assignments; this.classes = result.classes; this.version = result.version;
                this.patterns = {}; this.history = []; this.dirty = false; this.message = result.message;
                if (this.embedded) { this.$dispatch('timetable-prepared', { grid: result.grid }); if (returnToGrid) this.$dispatch('preparation-close'); }
                else if (returnToGrid && this.gridUrl) window.location.assign(this.gridUrl);
            } catch (error) { this.error = error.message || 'Could not save. Your draft is still here.'; }
            finally { this.busy = false; }
        },
    };
}
