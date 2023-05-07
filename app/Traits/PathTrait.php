<?php

namespace App\Traits;

use App\Models\Post;
use App\Models\PostType;
use App\Models\Week;

trait PathTrait
{

    function getFriendsRequestsPath($user_id)
    {
        return 'user/profile/friends/requests/' . $user_id;
    }

    function getPostPath($post_id)
    {
        return 'post/' . $post_id;
    }

    function getProfilePath($user_id)
    {
        return 'user/profile/' . $user_id;
    }

    function getSuportPostPath()
    {
        $post = Post::where('type_id', PostType::where('type', 'support')->first()->id)
            ->latest()->first();

        if ($post) {
            return 'post/' . $post->id;
        } else {
            return 'support/';
        }
    }

    function getThesesPath($book_id, $thesis_id)
    {
        return 'book/user-single-thesis/' . $book_id . '/' . $thesis_id;
    }

    function getGroupPath($group_id)
    {
        return 'group/group-detail/' . $group_id;
    }

    function getGroupExceptionsPath($group_id)
    {
        return 'group/group-exceptions/' . $group_id;
    }

    function getExceptionPath($exception_id)
    {
        return 'exceptions/list-exception/' . $exception_id;
    }

    function getPendingPostsPath($timeline_id, $post_id = null)
    {
        $path = 'posts/pending/' . $timeline_id;
        if ($post_id) {
            $path .= '/' . $post_id;
        }

        return $path;
    }
}