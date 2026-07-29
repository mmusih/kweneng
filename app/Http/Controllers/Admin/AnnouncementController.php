<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassModel;
use App\Models\ParentDeviceToken;
use App\Models\ParentModel;
use App\Services\AudienceResolverService;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

use App\Models\AnnouncementActivity;
class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['author', 'classModel', 'subject', 'targets'])
            ->latest()
            ->paginate(20);

        return view('admin.announcements.index', compact('announcements'))->with('announcementRoutePrefix', $this->routePrefix());
    }

    public function create()
    {
        return view('admin.announcements.create', $this->formData())->with('announcementRoutePrefix', $this->routePrefix());
    }

    public function store(
        Request $request,
        FirebaseNotificationService $firebase,
        AudienceResolverService $audienceResolver
    ) {
        $validated = $this->validateAnnouncement($request);

        $announcement = DB::transaction(function () use ($request, $validated) {
            $announcement = Announcement::create([
                'title'        => $validated['title'],
                'message'      => $validated['message'],
                'type'         => $validated['type'],
                'audience'     => $validated['audience'],
                'class_id'     => null,
                'subject_id'   => null,
                'is_published' => $request->boolean('is_published'),
                'publish_at'   => $validated['publish_at'] ?? null,
                'expires_at'   => $validated['expires_at'] ?? null,
                'author_id'    => Auth::id(),
                'author_role'  => Auth::user()?->role ?? 'admin',
            ]);

        $this->logAnnouncementActivity($announcement, 'Announcement Created', 'Announcement was created.');

            $this->syncTargets($announcement, $validated);

            return $announcement->fresh(['targets']);
        });

        $this->sendPushIfDue($announcement, $firebase, $audienceResolver);

        return redirect()->route($this->routePrefix() . '.announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    public function show(Announcement $announcement)
    {
        $announcement->load(['author', 'classModel', 'subject', 'targets']);

        return view('admin.announcements.show', compact('announcement'))->with('announcementRoutePrefix', $this->routePrefix());
    }

    public function edit(Announcement $announcement)
    {
        $announcement->load('targets');

        return view('admin.announcements.edit', array_merge(
            ['announcement' => $announcement],
            $this->formData(),
            $this->selectedTargetData($announcement)
        ))->with('announcementRoutePrefix', $this->routePrefix());
    }

    public function update(
        Request $request,
        Announcement $announcement,
        FirebaseNotificationService $firebase,
        AudienceResolverService $audienceResolver
    ) {
        $validated = $this->validateAnnouncement($request);

        DB::transaction(function () use ($request, $announcement, $validated) {
            $announcement->update([
                'title'        => $validated['title'],
                'message'      => $validated['message'],
                'type'         => $validated['type'],
                'audience'     => $validated['audience'],
                'class_id'     => null,
                'subject_id'   => null,
                'is_published' => $request->boolean('is_published'),
                'publish_at'   => $validated['publish_at'] ?? null,
                'expires_at'   => $validated['expires_at'] ?? null,
            ]);

        $this->logAnnouncementActivity($announcement, 'Announcement Updated', 'Announcement details or targets were updated.');

            $this->syncTargets($announcement, $validated);
        });

        $announcement->refresh()->load('targets');
        $this->sendPushIfDue($announcement, $firebase, $audienceResolver);

        return redirect()->route($this->routePrefix() . '.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route($this->routePrefix() . '.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    public function tracking(
        Request $request,
        Announcement $announcement,
        AudienceResolverService $audienceResolver
    ) {
        $announcement->load(['author', 'targets']);

        $targetParentIds = $audienceResolver->parentIdsForAnnouncement($announcement)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $reads = DB::table('announcement_reads')
            ->where('announcement_id', $announcement->id)
            ->whereIn('parent_id', $targetParentIds)
            ->get()
            ->keyBy('parent_id');

        $tokenParentIds = ParentDeviceToken::query()
            ->whereIn('parent_id', $targetParentIds)
            ->pluck('parent_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $parents = ParentModel::with(['user', 'students.user', 'students.currentClass'])
            ->whereIn('id', $targetParentIds)
            ->get()
            ->sortBy(fn ($parent) => $parent->user->name ?? 'Unknown parent')
            ->values();

        $rows = $parents->map(function ($parent) use ($reads, $tokenParentIds) {
            $read = $reads->get($parent->id);
            $students = $parent->students->map(function ($student) {
                $studentName = $student->user->name ?? 'Unnamed student';
                $className = $student->currentClass->name ?? 'No class';

                return $studentName . ' (' . $className . ')';
            })->join(', ');

            return [
                'parent_id' => $parent->id,
                'name' => $parent->user->name ?? 'Unknown parent',
                'email' => $parent->user->email ?? '',
                'phone' => $parent->phone ?? '',
                'students' => $students,
                'read' => (bool) $read,
                'read_at' => $read?->read_at,
                'acknowledged' => $read && $read->acknowledged_at !== null,
                'acknowledged_at' => $read?->acknowledged_at,
                'has_token' => $tokenParentIds->contains((int) $parent->id),
            ];
        });

        $stats = [
            'targeted' => $rows->count(),
            'read' => $rows->where('read', true)->count(),
            'unread' => $rows->where('read', false)->count(),
            'acknowledged' => $rows->where('acknowledged', true)->count(),
            'pending_acknowledgement' => $rows->where('acknowledged', false)->count(),
            'with_token' => $rows->where('has_token', true)->count(),
            'without_token' => $rows->where('has_token', false)->count(),
        ];

        $filter = $request->query('filter', 'all');

        $filteredRows = match ($filter) {
            'read' => $rows->where('read', true)->values(),
            'unread' => $rows->where('read', false)->values(),
            'acknowledged' => $rows->where('acknowledged', true)->values(),
            'pending_acknowledgement' => $rows->where('acknowledged', false)->values(),
            'no_token' => $rows->where('has_token', false)->values(),
            default => $rows,
        };

        return view('admin.announcements.tracking', [
            'announcementRoutePrefix' => $this->routePrefix(),
            'announcement' => $announcement,
            'stats' => $stats,
            'rows' => $filteredRows,
            'filter' => $filter,
        ]);
    }

    protected function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'message'    => ['required', 'string'],
            'type'       => ['required', Rule::in(['general', 'academic', 'event', 'urgent'])],
            'audience'   => ['required', Rule::in(['all_parents', 'form_level', 'class_group', 'specific_parent'])],
            'target_form_levels' => ['nullable', 'array'],
            'target_form_levels.*' => ['nullable', 'string', 'max:100'],
            'target_class_ids' => ['nullable', 'array'],
            'target_class_ids.*' => ['nullable', 'integer', 'exists:classes,id'],
            'target_parent_ids' => ['nullable', 'array'],
            'target_parent_ids.*' => ['nullable', 'integer', 'exists:parents,id'],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:publish_at'],
        ]);
    }

    protected function formData(): array
    {
        $classes = ClassModel::orderBy('level')->orderBy('name')->get();

        $formLevels = $classes
            ->pluck('level')
            ->filter()
            ->unique()
            ->values();

        if ($formLevels->isEmpty()) {
            $formLevels = $classes
                ->pluck('name')
                ->map(fn ($name) => preg_match('/^(Form\s+\d+)/i', $name, $m) ? $m[1] : null)
                ->filter()
                ->unique()
                ->values();
        }

        $parents = ParentModel::with('user')
            ->whereNotNull('user_id')
            ->get()
            ->sortBy(fn ($parent) => $parent->user->name ?? 'Unknown parent')
            ->values();

        return compact('classes', 'formLevels', 'parents');
    }

    protected function selectedTargetData(Announcement $announcement): array
    {
        $targets = $announcement->targets;

        return [
            'selectedFormLevels' => $targets
                ->where('target_type', 'form_level')
                ->pluck('target_value')
                ->filter()
                ->values()
                ->all(),

            'selectedClassIds' => $targets
                ->where('target_type', 'class_group')
                ->pluck('target_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),

            'selectedParentIds' => $targets
                ->where('target_type', 'parent')
                ->pluck('target_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
        ];
    }

    protected function syncTargets(Announcement $announcement, array $validated): void
    {
        $announcement->targets()->delete();

        if ($validated['audience'] === 'all_parents') {
            return;
        }

        if ($validated['audience'] === 'form_level') {
            foreach ($validated['target_form_levels'] ?? [] as $level) {
                if (trim((string) $level) === '') {
                    continue;
                }

                $announcement->targets()->create([
                    'target_type' => 'form_level',
                    'target_id' => null,
                    'target_value' => trim((string) $level),
                ]);
            }

            return;
        }

        if ($validated['audience'] === 'class_group') {
            foreach ($validated['target_class_ids'] ?? [] as $classId) {
                $announcement->targets()->create([
                    'target_type' => 'class_group',
                    'target_id' => (int) $classId,
                    'target_value' => null,
                ]);
            }

            return;
        }

        if ($validated['audience'] === 'specific_parent') {
            foreach ($validated['target_parent_ids'] ?? [] as $parentId) {
                $announcement->targets()->create([
                    'target_type' => 'parent',
                    'target_id' => (int) $parentId,
                    'target_value' => null,
                ]);
            }
        }
    }

    protected function sendPushIfDue(
        Announcement $announcement,
        FirebaseNotificationService $firebase,
        AudienceResolverService $audienceResolver
    ): void {
        if (! $announcement->is_published) {
            return;
        }

        if ($announcement->publish_at && $announcement->publish_at->isFuture()) {
            return;
        }

        if (
            Schema::hasColumn('announcements', 'push_sent_at') &&
            $announcement->push_sent_at
        ) {
            return;
        }

        $this->sendAnnouncementPush($firebase, $audienceResolver, $announcement);

        if (Schema::hasColumn('announcements', 'push_sent_at')) {
            $announcement->forceFill(['push_sent_at' => now()])->save();
        }
    }

    protected function sendAnnouncementPush(
        FirebaseNotificationService $firebase,
        AudienceResolverService $audienceResolver,
        Announcement $announcement
    ): void {
        $parentIds = $audienceResolver->parentIdsForAnnouncement($announcement)->all();

        if (empty($parentIds)) {
            return;
        }

        $title = $announcement->type === 'urgent'
            ? 'Urgent School Notice'
            : 'New School Notice';

        $firebase->sendToParents(
            $parentIds,
            $title,
            $announcement->title,
            [
                'type'            => 'announcement',
                'announcement_id' => (string) $announcement->id,
                'screen'          => 'announcements',
            ]
        );
    }
    public function exportTrackingCsv(
        Announcement $announcement,
        AudienceResolverService $audienceResolver
    ) {
        $announcement->load(['author', 'targets']);

        $requiresAcknowledgement = method_exists($announcement, 'requiresAcknowledgement')
            ? $announcement->requiresAcknowledgement()
            : in_array($announcement->type, ['urgent'], true);

        $targetParentIds = $audienceResolver->parentIdsForAnnouncement($announcement)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $reads = DB::table('announcement_reads')
            ->where('announcement_id', $announcement->id)
            ->whereIn('parent_id', $targetParentIds)
            ->get()
            ->keyBy('parent_id');

        $tokenParentIds = ParentDeviceToken::query()
            ->whereIn('parent_id', $targetParentIds)
            ->pluck('parent_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $parents = ParentModel::with(['user', 'students.user', 'students.currentClass'])
            ->whereIn('id', $targetParentIds)
            ->get()
            ->sortBy(fn ($parent) => $parent->user->name ?? 'Unknown parent')
            ->values();

        $fileName = 'announcement-tracking-' . $announcement->id . '-' . now()->format('Y-m-d-His') . '.csv';

        $this->logAnnouncementActivity($announcement, 'CSV Exported', 'Tracking CSV downloaded.');



        return response()->streamDownload(function () use ($parents, $reads, $tokenParentIds, $requiresAcknowledgement) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Parent Name',
                'Email',
                'Phone',
                'Student(s)',
                'Read Status',
                'Read At',
                'Acknowledgement Status',
                'Acknowledged At',
                'App Token Status',
            ]);

            foreach ($parents as $parent) {
                $read = $reads->get($parent->id);

                $students = $parent->students->map(function ($student) {
                    $studentName = $student->user->name ?? 'Unnamed student';
                    $className = $student->currentClass->name ?? 'No class';

                    return $studentName . ' (' . $className . ')';
                })->join(', ');

                $readStatus = $read && $read->read_at ? 'Read' : 'Unread';
                $readAt = $read?->read_at ?? '';

                if (! $requiresAcknowledgement) {
                    $ackStatus = 'Not Required';
                    $ackAt = '';
                } else {
                    $ackStatus = $read && $read->acknowledged_at ? 'Acknowledged' : 'Pending';
                    $ackAt = $read?->acknowledged_at ?? '';
                }

                $appTokenStatus = $tokenParentIds->contains((int) $parent->id)
                    ? 'Available'
                    : 'No App Token';

                fputcsv($handle, [
                    $parent->user->name ?? 'Unknown parent',
                    $parent->user->email ?? '',
                    $parent->phone ?? '',
                    $students,
                    $readStatus,
                    $readAt,
                    $ackStatus,
                    $ackAt,
                    $appTokenStatus,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function sendTrackingReminder(
        Request $request,
        Announcement $announcement,
        AudienceResolverService $audienceResolver,
        FirebaseNotificationService $firebase
    ) {
        $validated = $request->validate([
            'target' => 'required|in:unread,pending_acknowledgement',
        ]);

        $announcement->load(['targets']);

        $requiresAcknowledgement = $announcement->requiresAcknowledgement();

        if ($validated['target'] === 'pending_acknowledgement' && ! $requiresAcknowledgement) {
            return redirect()
                ->route($this->routePrefix() . '.announcements.tracking', $announcement)
                ->with('error', 'This announcement does not require acknowledgement, so there are no pending acknowledgements.');
        }

        $targetParentIds = $audienceResolver->parentIdsForAnnouncement($announcement)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $reads = DB::table('announcement_reads')
            ->where('announcement_id', $announcement->id)
            ->whereIn('parent_id', $targetParentIds)
            ->get()
            ->keyBy('parent_id');

        $selectedParentIds = $targetParentIds->filter(function ($parentId) use ($reads, $validated) {
            $read = $reads->get((int) $parentId);

            if ($validated['target'] === 'unread') {
                return ! ($read && $read->read_at);
            }

            return ! ($read && $read->acknowledged_at);
        })->values();

        $tokenParentIds = ParentDeviceToken::query()
            ->whereIn('parent_id', $selectedParentIds)
            ->pluck('parent_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $sendParentIds = $selectedParentIds
            ->filter(fn ($id) => $tokenParentIds->contains((int) $id))
            ->values();

        $title = 'Reminder: School Notice';
        $body = $requiresAcknowledgement
            ? 'Reminder: Please read and acknowledge this notice from Kweneng International Secondary School.'
            : 'Reminder: Please read this notice from Kweneng International Secondary School.';

        foreach ($sendParentIds as $parentId) {
            $firebase->sendToParent((int) $parentId, $title, $body, [
                'type'            => 'announcement_reminder',
                'announcement_id' => (string) $announcement->id,
                'screen'          => 'announcements',
            ]);
        }

        $withoutToken = $selectedParentIds->count() - $sendParentIds->count();

        $message = 'Reminder sent to ' . $sendParentIds->count() . ' parent(s).';

        if ($withoutToken > 0) {
            $message .= ' ' . $withoutToken . ' selected parent(s) have no app token.';
        }

        $this->logAnnouncementActivity(
            $announcement,
            'Reminder Sent',
            $message,
            Auth::user()?->role ?? 'admin'
        );

        return redirect()
            ->route($this->routePrefix() . '.announcements.tracking', $announcement)
            ->with('success', $message);
    }


    protected function logAnnouncementActivity(Announcement $announcement, string $action, ?string $details = null, ?string $actorType = null): void
    {
        try {
            AnnouncementActivity::create([
                'announcement_id' => $announcement->id,
                'action' => $action,
                'details' => $details,
                'actor_type' => $actorType ?? (Auth::check() ? Auth::user()->role : 'system'),
                'performed_by_id' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }



    protected function routePrefix(): string
    {
        return request()->routeIs('office.*') ? 'office' : 'admin';
    }
}
