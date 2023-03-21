<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupResource;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Media;
use App\Models\Timeline;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\UserExceptionResource;
use App\Models\Mark;
use App\Models\UserBook;
use App\Models\UserException;
use App\Models\Week;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Description: GroupController for Osboha group.
 *
 * Methods: 
 * - CRUD
 * - group posts list
 */

class GroupController extends Controller
{

    use ResponseJson, MediaTraits;


    public function index()
    {
        $group = Group::all();
        if (Auth::user()->can('list groups')) {
            return $this->jsonResponseWithoutMessage(GroupResource::collection($group), 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }
    public function GroupByType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->can('list groups')) {

            $groups = Group::where('type_id', $request->type_id)->get();
            if ($groups->isNotEmpty()) {
                return $this->jsonResponseWithoutMessage(GroupResource::collection($groups), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function create(Request $request)
    {

        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type_id' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }


        if (Auth::user()->can('create group')) {
            $timeline = new Timeline;
            $timeline->name = $request->name;
            $timeline->description = $request->description;
            $timeline->type_id = $request->type_id;
            $timeline->save();
            $input['creator_id'] = Auth::id();
            $input['timeline_id'] = $timeline->id;
            $group = Group::create($input);
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $this->createMedia($file, $group->id, 'group');
            }
            return $this->jsonResponseWithoutMessage('Group Craeted', 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Find an existing group by its id and display it.
     * 
     * @param  $group_id
     * @return group info [users , administrators] - posts;
     */

    public function show($group_id)
    {

        $response['info'] = Group::with('users', 'groupAdministrators')->withCount('userAmbassador')->where('id', $group_id)->first();

        if ($response['info']) {
            $response['authInGroup'] = UserGroup::where('user_id', Auth::id())->where('group_id', $group_id)->first();
            if ($response['authInGroup'] || Auth::user()->hasRole('admin')) {

                //group posts
                $response['post'] = Timeline::find($response['info']->id);

                //week avg
                $response['week'] = Week::latest()->first();
                $users = Group::with('users')->where('id', $group_id);

                $response['week_avg'] = Mark::where('week_id', $response['week']->id)->whereIn('user_id', $users->pluck('id'))->avg('out_of_100');

                return $this->jsonResponseWithoutMessage($response, 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } //end if group found

        //group not found
        else {
            throw new NotFound;
        }
    }

    public function update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'group_id' => 'required',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type_id' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $group = Group::find($request->group_id);
        $user_type = UserGroup::where('group_id', $request->group_id)->where('user_id', Auth::id())->pluck('user_type')->first();
        if ($group) {

            if (Auth::user()->can('edit group') && $user_type != "ambassador") {
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $currentMedia = Media::where('group_id', $group->id);

                    if ($currentMedia) {
                        $this->updateMedia($file, $currentMedia->id);
                    } else {
                        $this->createMedia($file, $group->id, 'group');
                    }
                }

                $group->update($input);
                return $this->jsonResponseWithoutMessage("Group Updated", 'data', 200);
            } //endif Auth

            else {
                throw new NotAuthorized;
            }
        } //end if group found

        else {
            throw new NotFound;
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('delete group')) {
            $group = Group::find($request->group_id);
            if ($group) {

                $currentMedia = Media::where('group_id', $group->id)->first();

                //if exist delete image
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }

                $group->delete();

                return $this->jsonResponseWithoutMessage('Group Deleted', 'data', 200);
            } else {
                throw new NotFound();
            }
        }
        //endif Auth

        else {
            throw new NotAuthorized;
        }
    }


    /**
     * Get all books belongs to group users.
     * 
     * @param  $group_id
     * @return jsonResponseWithoutMessage;
     */
    public function books($group_id)
    {
        $group = Group::with('users')->where('id', $group_id)->first();
        $books = UserBook::whereIn('user_id', $group->pluck('id'))->get();

        if ($books) {
            return $this->jsonResponseWithoutMessage($books, 'data', 200);
        } else {
            throw new NotFound;
        }
    }


    /**
     * Read all exceptions in a group by group Administrators
     *
     * @param $group_id
     * @return jsonResponseWithoutMessage
     */

    public function groupExceptions($group_id)
    {

        $userInGroup = UserGroup::where('group_id', $group_id)
            ->where('user_id', Auth::id())->pluck('user_type')
            ->first();

        if ($userInGroup != 'ambassador') {
            $group = Group::with('users')->where('id', $group_id)->first();
            $exceptions = UserException::whereIn('user_id', $group->pluck('id'))->latest()->get();
            return $this->jsonResponseWithoutMessage(UserExceptionResource::collection($exceptions), 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    //the function will return all posts - discuss it later
    public function list_group_posts($group_id)
    {

        $group = Group::find($group_id);
        $timeLine = Timeline::find($group->timeline_id)->posts;

        if ($timeLine) {
            return $timeLine;
        } else {
            throw new NotFound;
        }
    }
}
