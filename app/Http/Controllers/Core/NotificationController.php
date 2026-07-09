<?php

namespace App\Http\Controllers\Core;

use App\Core\Notifications\NotificationService;
use App\Http\Controllers\Controller;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = CoreNotification::query()
            ->with('actor:id,name,email')
            ->where('recipient_id', $request->user()->id)
            ->when($request->boolean('unread'), fn ($query) => $query->unread())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Core/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only('unread'),
            'unreadCount' => CoreNotification::query()->where('recipient_id', $request->user()->id)->unread()->count(),
        ]);
    }

    public function test(Request $request, NotificationService $service): RedirectResponse
    {
        $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'module_name' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $recipient = User::findOrFail($request->integer('recipient_id'));
        $moduleName = $request->string('module_name', 'core.test')->toString();
        $referenceId = $request->integer('reference_id') ?: 1;

        $service->notify(
            $recipient,
            'core.test',
            ['module_name' => $moduleName, 'reference_id' => $referenceId],
            $request->user(),
            $moduleName,
            $referenceId,
            route('core.notifications.index'),
        );

        return redirect()->route('core.notifications.index');
    }

    public function markRead(CoreNotification $notification, Request $request, NotificationService $service): RedirectResponse
    {
        abort_unless($notification->recipient_id === $request->user()->id, 403);

        $service->markRead($notification);

        return back();
    }

    public function markUnread(CoreNotification $notification, Request $request, NotificationService $service): RedirectResponse
    {
        abort_unless($notification->recipient_id === $request->user()->id, 403);

        $service->markUnread($notification);

        return back();
    }

    public function markAllRead(Request $request, NotificationService $service): RedirectResponse
    {
        $service->markAllRead($request->user());

        return back();
    }
}
