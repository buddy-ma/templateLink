<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $model = $user->notifications()->where('id', $notification)->firstOrFail();
        $model->markAsRead();

        $url = data_get($model->data, 'url');

        if (is_string($url) && $url !== '') {
            return redirect()->to($url);
        }

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $user->unreadNotifications->markAsRead();

        return back();
    }
}
