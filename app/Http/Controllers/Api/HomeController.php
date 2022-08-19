<?php

namespace App\Http\Controllers\Api;

// use App\Models\Home;

use App\Exceptions\NotFound;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Book;
use App\Models\Friend;
use App\Models\Group;
use App\Models\Infographic;
use App\Models\Media;
use App\Models\Post;
use App\Models\UserGroup;
use App\Models\UserProfile;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    use ResponseJson;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // associative array to send all wanted data
        $data_to_home_page=[];  

        // The system shall display random posts from different timelines.
        // we have 4 timelines [
        //     1 => main
        //     2 => profile
        //     3 => book
        //     4 => activity
        // ]

        // Groups timeline
        $my_groups_timelines = UserGroup::join('groups', 'user_groups.group_id', '=', 'groups.id')
				->select('groups.timeline_id')
				->where('user_groups.user_id', '=', Auth::id())->get()->toArray();
        $posts_from_groups = Post::where('timeline_id', $my_groups_timelines)->get()->toArray();
        $data_to_home_page["posts"] = $posts_from_groups;

        // Friends timeline
        $my_friends_ids = Friend::select('friend_id')->where('user_id', Auth::id())->get()->toArray();
        $my_friends_timelines = UserProfile::select('timeline_id')->where('user_id', $my_friends_ids)->get()->toArray();
        $posts_from_friends = Post::where('timeline_id', $my_friends_timelines)->get()->toArray();
        array_push($data_to_home_page["posts"], $posts_from_friends);

        // Main
        $posts_from_main = Post::where('timeline_id', 1)->inRandomOrder()->limit(5)->get();
        array_push($data_to_home_page["posts"], $posts_from_main);

        // Books timeline
        $posts_from_books = Post::where('timeline_id', 3)->inRandomOrder()->limit(5)->get();
        array_push($data_to_home_page["posts"], $posts_from_books);
        
        // The system shall display all teams [Groups] that the user is joined to.
        $groups = UserGroup::where('user_id', Auth::id())->get();
        $data_to_home_page["groups"] = $groups;

        // The system shall display the latest books added.
        $books = Book::latest()->limit(5)->get();
        $data_to_home_page["books"] = $books;

        // The system shall display the latest articles added.
        $articles = Article::latest()->limit(5)->get();
        $data_to_home_page["articles"] = $articles;

        // The system shall display the latest infographics added.
        $infographics = Infographic::latest()->limit(5)->get();
        $data_to_home_page["infographics"] = $infographics;

        // The system shall display the latest videos [Media (type = video)] added.
        $videos = Media::where('type', 'video')->latest()->limit(5)->get();
        $data_to_home_page["videos"] = $videos;

        // The system shall display all courses [Activity] that the user is taking now.
        $courses = Activity::latest()->limit(5)->get();
        $data_to_home_page["courses"] = $courses;

        // Sending data to Home Page
        if($data_to_home_page){
            return $this->jsonResponseWithoutMessage($data_to_home_page, 'data',200);
        }
        else{
            throw new NotFound();
        }
    }
}
