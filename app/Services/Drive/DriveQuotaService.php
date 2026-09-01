<?php

declare(strict_types=1);

namespace App\Services\Drive;

use App\Models\Drive\DriveFile;
use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Validation\ValidationException;

class DriveQuotaService
{
    public function __construct(
        private readonly AppSettingsService $settings,
        private readonly DriveAccessService $access,
    ) {}

    public function quotaBytes(): int
    {
        $value = $this->settings->get('drive.quota_bytes', 53_687_091_200);

        return max(0, (int) $value);
    }

    public function usedBytes(): int
    {
        return (int) DriveFile::query()->sum('size');
    }

    public function usedBytesForUser(User $user): int
    {
        return (int) DriveFile::query()
            ->where('owner_id', $user->id)
            ->sum('size');
    }

    /**
     * @return array{used_bytes: int, quota_bytes: int, remaining_bytes: int, used_percent: float, scope: string}
     */
    public function usage(): array
    {
        return $this->buildUsage($this->usedBytes(), 'department');
    }

    /**
     * Department-wide for Drive admins; personal consumption for everyone else.
     *
     * @return array{used_bytes: int, quota_bytes: int, remaining_bytes: int, used_percent: float, scope: string}
     */
    public function usageFor(User $user): array
    {
        if ($this->access->canManageAll($user)) {
            return $this->usage();
        }

        return $this->buildUsage($this->usedBytesForUser($user), 'personal');
    }

    /**
     * @return array{used_bytes: int, quota_bytes: int, remaining_bytes: int, used_percent: float, scope: string}
     */
    private function buildUsage(int $used, string $scope): array
    {
        $quota = $this->quotaBytes();
        $remaining = max(0, $quota - $used);
        $percent = $quota > 0 ? round(($used / $quota) * 100, 1) : 0.0;

        return [
            'used_bytes' => $used,
            'quota_bytes' => $quota,
            'remaining_bytes' => $remaining,
            'used_percent' => $percent,
            'scope' => $scope,
        ];
    }

    public function assertCanStore(int $additionalBytes): void
    {
        if ($additionalBytes <= 0) {
            return;
        }

        $usage = $this->usage();

        if (($usage['used_bytes'] + $additionalBytes) > $usage['quota_bytes']) {
            throw ValidationException::withMessages([
                'file' => __('drive.errors.quota_exceeded'),
            ]);
        }
    }

    public function setQuotaBytes(int $bytes): void
    {
        $this->settings->set('drive.quota_bytes', max(0, $bytes));
    }
}
