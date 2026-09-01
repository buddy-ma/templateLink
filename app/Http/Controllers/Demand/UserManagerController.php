<?php

declare(strict_types=1);

namespace App\Http\Controllers\Demand;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserManagerController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('demands.view_all'), 403);

        $users = User::query()
            ->with(['manager:id,name,email', 'roles'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values()->all(),
                'manager_id' => $user->manager_id,
                'manager' => $user->manager ? [
                    'id' => $user->manager->id,
                    'name' => $user->manager->name,
                ] : null,
            ]);

        $managers = User::role('responsable_marketing')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('demands/team/Index', [
            'users' => $users,
            'managers' => $managers,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->can('demands.view_all'), 403);

        $validated = $request->validate([
            'manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::notIn([$user->id]),
            ],
        ]);

        $managerId = $validated['manager_id'] ?? null;

        if ($managerId !== null) {
            $manager = User::query()->findOrFail($managerId);
            if (! $manager->hasRole('responsable_marketing')) {
                return back()->withErrors([
                    'manager_id' => __('demands.messages.manager_role_required'),
                ]);
            }
        }

        $user->update(['manager_id' => $managerId]);

        return back()->with('success', __('demands.messages.manager_updated'));
    }
}
