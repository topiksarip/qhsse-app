<?php

namespace App\Http\Controllers\Core;

use App\Core\Authorization\ParentAuthorizationRegistry;
use App\Core\Comments\CommentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\CommentRequest;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Comments\Comment;
use App\Models\Modules\Asset\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentActivityController extends Controller
{
    public function __construct(private readonly ParentAuthorizationRegistry $authRegistry) {}

    public function index(Request $request): Response
    {
        $moduleName = $request->string('module_name')->toString();
        $referenceId = $request->integer('reference_id') ?: null;

        abort_if($referenceId === null, 422, 'reference_id required');
        abort_unless($this->authRegistry->isModuleRegistered($moduleName), 403, 'Module not registered');
        abort_unless($this->authRegistry->canAccessParent($moduleName, $referenceId, $request->user()), 403);

        $comments = Comment::query()
            ->with('author:id,name,email')
            ->where('module_name', $moduleName)
            ->where('reference_id', $referenceId)
            ->active()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $activities = ActivityLog::query()
            ->with('actor:id,name,email')
            ->where('module_name', $moduleName)
            ->when($moduleName !== 'asset', fn ($query) => $query->where('module_name', '!=', 'asset'))
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
        $moduleName = $request->string('module_name')->toString();
        $referenceId = $request->integer('reference_id');
        
        abort_unless($this->authRegistry->isModuleRegistered($moduleName), 403);
        abort_unless($this->authRegistry->canAccessParent($moduleName, $referenceId, $request->user()), 403);

        $service->add(
            $moduleName,
            $referenceId,
            $request->string('body')->toString(),
            $request->user(),
            $request->integer('parent_id') ?: null,
            $request->boolean('is_internal'),
        );

        return redirect()->route('core.comments-activity.index', $request->only(['module_name', 'reference_id']));
    }

    public function destroy(Comment $comment, Request $request, CommentService $service): RedirectResponse
    {
        abort_unless($this->authRegistry->isModuleRegistered($comment->module_name), 403);
        abort_unless($this->authRegistry->canAccessParent($comment->module_name, $comment->reference_id, $request->user()), 403);

        $service->delete($comment, $request->user());

        return redirect()->route('core.comments-activity.index', [
            'module_name' => $comment->module_name,
            'reference_id' => $comment->reference_id,
        ]);
    }
}
