<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Models\DuplicateUserDeletion;
use App\Models\User;
use App\Models\UserGroup;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DuplicateUserDeletionController extends Controller
{
    use ResponseJson;


    public function getDuplicateDeletions($viewAsUserId = null)
    {
        $actualViewer = auth()->user();
        if ($viewAsUserId) {
            $viewer = User::findOrFail($viewAsUserId);
            if ($viewer->id !== $actualViewer->id) {
                if (
                    !$actualViewer->hasRole('admin') &&
                    $viewer->parent_id !== $actualViewer->id
                ) {
                    throw new NotAuthorized;
                }
            }
        } else {
            $viewer = $actualViewer;
        }
        $viewer = $viewAsUserId ? User::findOrFail($viewAsUserId) : $actualViewer;

        if (!$actualViewer->hasRole(['admin', 'consultant', 'advisor'])) {
            throw new NotAuthorized;
        }

        $response = [];
        $directChildrenIds = [];
        $response['viewer_role'] = $viewer->roles()->pluck('name')->first();
        $response['viewer_name'] = $viewer->fullName;

        $withRelations = [
            'user:id,name,last_name,email',
            'group:id,name,description',
            'group.groupAdministrators' => function ($q) {
                $q->select('users.id', 'users.name', 'users.last_name')
                    ->withPivot('user_type');
            },
            'deletedBy:id,name,last_name,email',
        ];

        $response['by_me'] = DuplicateUserDeletion::with($withRelations)->where('deleted_by', $viewer->id)->get();

        if ($viewer->hasRole(['admin', 'consultant'])) {
            $directChildrenIds = User::where('parent_id', $viewer->id)->pluck('id');
            $response['by_children'] = DuplicateUserDeletion::with($withRelations)->whereIn('deleted_by', $directChildrenIds)->get();
        }

        if ($viewer->hasRole('admin')) {
            $grandChildrenIds = User::whereIn('parent_id', $directChildrenIds)->pluck('id');
            $response['by_grandchildren'] = DuplicateUserDeletion::with($withRelations)->whereIn('deleted_by', $grandChildrenIds)->get();
        }

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    public function markAsDuplicate(Request $request)
    {
        if (Auth::user()->hasAnyRole(['admin', 'consultant', 'advisor'])) {
            $validator = Validator::make($request->all(), [
                'user_group_id' => 'required|exists:user_groups,id',
                'duplicate_in' => 'required|string|max:255',
            ]);
            if ($validator->fails()) {
                return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
            }

            $user_group = UserGroup::with(['user', 'group'])->find($request->user_group_id);

            if (!$user_group) {
                throw new NotFound;
            }

            $user_group->update([
                'termination_reason' => 'duplicate_entry',
            ]);

            $roleLabel = $user_group->user_type === 'support_leader' ? 'قائد الدعم' : 'السفير';
            $logInfo = 'قام ' . Auth::user()->fullName . " بتحديد $roleLabel " . $user_group->user->fullName . ' كمكرر في فريق ' . $user_group->group->name;

            DuplicateUserDeletion::create([
                'user_id' => $user_group->user_id,
                'group_id' => $user_group->group_id,
                'deleted_by' => Auth::id(),
                'duplicate_in' => $request->duplicate_in,
            ]);

            Log::channel('community_edits')->info($logInfo);

            return $this->jsonResponseWithoutMessage('تم تحديد السفير كمكرر بنجاح', 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }
}
