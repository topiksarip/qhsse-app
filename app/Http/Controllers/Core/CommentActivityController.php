<?php

namespace App\Http\Controllers\Core;

use App\Core\Comments\CommentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\CommentRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Comments\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $moduleName = $request->string('module_name')->toString();
        $referenceId = $request->integer('reference_id') ?: null;

        $comments = Comment::query()
            ->with('author:id,name,email')
            ->when($moduleName, fn ($query) => $query->where('module_name', $moduleName))
            ->when($referenceId, fn ($query) => $query->where('reference_id', $referenceId))
            ->active()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $activities = ActivityLog::query()
            ->with('actor:id,name,email')
            ->when($moduleName, fn ($query) => $query->where('module_name', $moduleName))
            ->when($referenceId, fn ($query) => $query->where('reference_id', $referenceId))
            ->latest()
            ->paginate(15, ['*'], 'activity_page')
            ->withQueryString();

        return Inertia::render('Core/CommentsActivity/Index', [
            'comments' => $comments,
            'activities' => $activities,
            'filters' => $request->only(['module_name', 'reference_id']),
        ]);
    }

    public function store(CommentRequest $request, CommentService $service): RedirectResponse
    {
        $service->add(
            $request->string('module_name')->toString(),
            $request->integer('reference_id'),
            $request->string('body')->toString(),
            $request->user(),
            $request->integer('parent_id') ?: null,
            $request->boolean('is_internal'),
        );

        return redirect()->route('core.comments-activity.index', $request->only(['module_name', 'reference_id']));
    }

    public function destroy(Comment $comment, Request $request, CommentService $service): RedirectResponse
    {
        $service->delete($comment, $request->user());

        return redirect()->route('core.comments-activity.index', [
            'module_name' => $comment->module_name,
            'reference_id' => $comment->reference_id,
        ]);
    }
}
