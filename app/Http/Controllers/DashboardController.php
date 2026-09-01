<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardMetricsService $metrics,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $activityPage = max(1, (int) $request->integer('activity_page', 1));

        return Inertia::render('Dashboard', [
            'dashboard' => $this->metrics->forUser($user, $activityPage),
        ]);
    }
}
