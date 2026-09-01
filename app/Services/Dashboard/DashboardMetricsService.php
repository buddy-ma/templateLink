<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\DemandStatus;
use App\Models\Demand;
use App\Models\DemandEvent;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShare;
use App\Models\User;
use App\Services\Drive\DriveAccessService;
use App\Services\Drive\DriveQuotaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    public function __construct(
        private readonly DriveAccessService $driveAccess,
        private readonly DriveQuotaService $driveQuota,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, int $activityPage = 1): array
    {
        $activityPage = max(1, $activityPage);
        $visible = Demand::query()->visibleTo($user);
        $awaitingQuery = $this->actionRequiredQuery($user);
        $awaitingCount = (clone $awaitingQuery)->count();

        $statusCounts = (clone $visible)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $closedThisMonth = (clone $visible)
            ->where('status', DemandStatus::Closed->value)
            ->where('closed_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'welcome' => [
                'name' => $user->name,
                'awaiting_count' => $awaitingCount,
                'can_create' => $user->can('demands.create'),
            ],
            'stats' => [
                'total' => (clone $visible)->count(),
                'pending_validation' => (int) ($statusCounts[DemandStatus::PendingValidation->value] ?? 0),
                'blocked' => (int) ($statusCounts[DemandStatus::Blocked->value] ?? 0),
                'closed_this_month' => $closedThisMonth,
                'awaiting_me' => $awaitingCount,
            ],
            'drive' => $this->driveMetrics($user),
            'urgent' => $this->urgentItems($awaitingQuery),
            'charts' => [
                'status_distribution' => $this->statusDistribution($statusCounts),
                'submissions_last_30_days' => $this->submissionsLast30Days($user),
            ],
            'recent_activity' => $this->recentActivity($user, $activityPage),
        ];
    }

    /**
     * Role-aware Drive KPIs: department-wide for drive.manage, personal otherwise.
     *
     * @return array<string, mixed>|null
     */
    private function driveMetrics(User $user): ?array
    {
        if (! $user->can('drive.access')) {
            return null;
        }

        $isAdmin = $this->driveAccess->canManageAll($user);
        $storage = $this->driveQuota->usageFor($user);

        if ($isAdmin) {
            $files = DriveFile::query()->count();
            $folders = DriveFolder::query()->count();
            $sharedItems = DriveShare::query()->distinct()->count('shareable_id');
            $trash = DriveFile::onlyTrashed()->count() + DriveFolder::onlyTrashed()->count();

            return [
                'enabled' => true,
                'scope' => 'department',
                'files' => $files,
                'folders' => $folders,
                'shared_items' => $sharedItems,
                'trash' => $trash,
                'storage_used_bytes' => $storage['used_bytes'],
                'storage_quota_bytes' => $storage['quota_bytes'],
                'storage_used_percent' => $storage['used_percent'],
                'storage_label' => $this->formatBytes($storage['used_bytes']),
            ];
        }

        $files = DriveFile::query()->where('owner_id', $user->id)->count();
        $folders = DriveFolder::query()->where('owner_id', $user->id)->count();
        $sharedWithMe = DriveShare::query()->where('user_id', $user->id)->count();
        $sharedByMe = DriveShare::query()->where('shared_by', $user->id)->count();

        return [
            'enabled' => true,
            'scope' => 'personal',
            'files' => $files,
            'folders' => $folders,
            'shared_with_me' => $sharedWithMe,
            'shared_by_me' => $sharedByMe,
            'storage_used_bytes' => $storage['used_bytes'],
            'storage_quota_bytes' => $storage['quota_bytes'],
            'storage_used_percent' => $storage['used_percent'],
            'storage_label' => $this->formatBytes($storage['used_bytes']),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;
        $i = 0;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return ($value >= 10 ? (string) (int) round($value) : number_format($value, 1)).' '.$units[$i];
    }

    /**
     * @return Builder<Demand>
     */
    public function actionRequiredQuery(User $user): Builder
    {
        return Demand::query()
            ->visibleTo($user)
            ->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $q) use ($user): void {
                    $q->where('status', DemandStatus::PendingValidation->value)
                        ->whereHas(
                            'validators',
                            function (Builder $vq) use ($user): void {
                                $vq->whereColumn('demand_validators.position', 'demands.current_step')
                                    ->where(function (Builder $inner) use ($user): void {
                                        $inner->where('user_id', $user->id);
                                        $roleNames = $user->getRoleNames()->all();
                                        if ($roleNames !== []) {
                                            $inner->orWhereIn('role_name', $roleNames);
                                        }
                                    });
                            },
                        );
                });

                $query->orWhere(function (Builder $q) use ($user): void {
                    $q->where('status', DemandStatus::PendingManager->value)
                        ->where('manager_id', $user->id);
                });

                if ($user->can('demands.business_validate')) {
                    $query->orWhere('status', DemandStatus::PendingBusinessDev->value);
                }

                if ($user->can('demands.close')) {
                    $query->orWhere('status', DemandStatus::PendingClosure->value);
                }

                $query->orWhere(function (Builder $q) use ($user): void {
                    $q->where('created_by', $user->id)
                        ->whereIn('status', [
                            DemandStatus::Refused->value,
                            DemandStatus::Blocked->value,
                        ]);
                });

                if ($user->can('demands.unblock')) {
                    $query->orWhere(function (Builder $q) use ($user): void {
                        $q->where('status', DemandStatus::Blocked->value)
                            ->where('created_by', '!=', $user->id);
                    });
                }
            })
            ->orderBy('updated_at');
    }

    /**
     * @param  Builder<Demand>  $query
     * @return array<int, array<string, mixed>>
     */
    private function urgentItems(Builder $query): array
    {
        return $query
            ->with(['brand:id,name', 'creator:id,name'])
            ->limit(8)
            ->get()
            ->map(fn (Demand $demand): array => [
                'id' => $demand->id,
                'reference' => $demand->reference,
                'status' => $demand->status->value,
                'brand_name' => $demand->brand?->name,
                'creator_name' => $demand->creator?->name,
                'updated_at' => $demand->updated_at?->toIso8601String(),
                'waiting_days' => $demand->updated_at
                    ? (int) $demand->updated_at->diffInDays(now())
                    : 0,
                'url' => route('demands.show', $demand),
            ])
            ->all();
    }

    /**
     * @param  Collection<string, int|string>  $statusCounts
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function statusDistribution(Collection $statusCounts): array
    {
        $order = [
            DemandStatus::Draft,
            DemandStatus::PendingManager,
            DemandStatus::PendingValidation,
            DemandStatus::Refused,
            DemandStatus::Blocked,
            DemandStatus::PendingBusinessDev,
            DemandStatus::PendingClosure,
            DemandStatus::Closed,
        ];

        $labels = [];
        $values = [];

        foreach ($order as $status) {
            $count = (int) ($statusCounts[$status->value] ?? 0);
            if ($count === 0) {
                continue;
            }
            $labels[] = $status->value;
            $values[] = $count;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function submissionsLast30Days(User $user): array
    {
        $start = now()->subDays(29)->startOfDay();

        $rows = DemandEvent::query()
            ->whereIn('type', ['submitted', 'resubmitted'])
            ->where('created_at', '>=', $start)
            ->whereHas('demand', fn (Builder $q) => $q->visibleTo($user))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('aggregate', 'day');

        $labels = [];
        $values = [];

        for ($i = 0; $i < 30; $i++) {
            /** @var Carbon $day */
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('M j');
            $values[] = (int) ($rows[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int,
     *     from: int|null,
     *     to: int|null
     * }
     */
    private function recentActivity(User $user, int $page): array
    {
        $perPage = 8;

        $paginator = DemandEvent::query()
            ->with(['actor:id,name', 'demand:id,reference,status'])
            ->whereHas('demand', fn (Builder $q) => $q->visibleTo($user))
            ->latest('created_at')
            ->paginate(perPage: $perPage, page: $page);

        $data = $paginator->getCollection()
            ->map(fn (DemandEvent $event): array => [
                'id' => $event->id,
                'type' => $event->type,
                'comment' => $event->comment,
                'created_at' => $event->created_at?->toIso8601String(),
                'actor_name' => $event->actor?->name,
                'demand_id' => $event->demand_id,
                'reference' => $event->demand?->reference,
                'status' => $event->demand?->status?->value,
                'url' => $event->demand ? route('demands.show', $event->demand) : null,
            ])
            ->values()
            ->all();

        return [
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
