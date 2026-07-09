<?php

namespace App\Core\Comments;

use App\Core\Activity\ActivityService;
use App\Models\Core\Comments\Comment;
use App\Models\User;

class CommentService
{
    public function add(
        string $moduleName,
        int $referenceId,
        string $body,
        User $author,
        ?int $parentId = null,
        bool $isInternal = false,
    ): Comment {
        $comment = Comment::create([
            'module_name' => $moduleName,
            'reference_id' => $referenceId,
            'parent_id' => $parentId,
            'author_id' => $author->id,
            'body' => $body,
            'mentions' => $this->extractMentions($body),
            'is_internal' => $isInternal,
        ]);

        app(ActivityService::class)->log(
            $moduleName,
            $referenceId,
            'comment.created',
            'Comment added',
            $author,
            ['comment_id' => $comment->id, 'is_internal' => $isInternal],
        );

        return $comment;
    }

    public function delete(Comment $comment, User $actor): void
    {
        $comment->update([
            'deleted_at' => now(),
            'deleted_by' => $actor->id,
        ]);

        app(ActivityService::class)->log(
            $comment->module_name,
            $comment->reference_id,
            'comment.deleted',
            'Comment deleted',
            $actor,
            ['comment_id' => $comment->id],
        );
    }

    private function extractMentions(string $body): array
    {
        preg_match_all('/@([A-Za-z0-9._-]+)/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
