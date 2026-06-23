<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function read(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        Gate::authorize('update', $record);
        $record = $this->notifications->markRead($request->user(), $notification);

        return redirect()->to($record->data['url'] ?? route('dashboard'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        Gate::authorize('viewAny', DatabaseNotification::class);
        $this->notifications->markAllRead($request->user());

        return back()->with('status', 'All notifications marked as read.');
    }
}
