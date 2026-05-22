<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ImpersonationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->whereKeyNot($request->user()->id)
            ->orderBy('name')
            ->paginate(20)
            ->through(fn (User $u): array => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
        ]);
    }

    public function start(Request $request, User $user): RedirectResponse
    {
        $this->authorize('impersonate', $user);

        if ($request->session()->has('impersonate.original_user_id')) {
            return back()->withErrors(['user' => 'You are already impersonating another user.']);
        }

        $request->session()->put('impersonate.original_user_id', $request->user()->id);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'You are now signed in as '.$user->name.'.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $originalId = $request->session()->pull('impersonate.original_user_id');

        if ($originalId === null) {
            return redirect()->route('dashboard');
        }

        $original = User::query()->findOrFail($originalId);

        Auth::login($original);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Returned to your account.');
    }
}
