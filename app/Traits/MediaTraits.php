<?php
namespace App\Traits;
use App\Models\Media;
use App\Models\Reaction;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

Trait MediaTraits{

    function createMedia($media, $type_id , $type){
        $imageName = time().'.'. $media->extension();  
        $media->move(public_path('assets/images'), $imageName);    
        // link media with comment
        $media = new Media();
        $media->media = $imageName; 
        $media->type = 'image'; 
        $media->user_id= Auth::id();
        if($type == 'comment'){
            $media->comment_id = $type_id;
        }
        elseif($type == 'post'){
            $media->post_id = $type_id;
        }
        elseif($type == 'infographicSeries'){
            $media->infographic_series_id = $type_id;
        }
        elseif($type == 'infographic'){
            $media->infographic_id = $type_id;
        }
        elseif($type == 'book'){
            $media->book_id = $type_id;
        }
        elseif($type == 'group'){
            $media->group_id = $type_id;
        }
        $media->save();   
        elseif($type == 'reaction'){
            $media->reaction_id = $type_id;
            $media->type = $type; 
            
        }
         $media->save();
         return $media;  
    }

    function updateMedia($media, $media_id){
        //get current media
        $currentMedia= Media::find($media_id);
        //delete current media
        File::delete(public_path('assets/images/'.$currentMedia->media));

        // upload new media
        $imageName = time().'.'. $media->extension();  
        $media->move(public_path('assets/images'), $imageName);    

        // update current media
        $currentMedia->media = $imageName; 
        $currentMedia->save();
    }

    
    function deleteMedia($media_id){
        $currentMedia= Media::find($media_id);
         //delete current media
        File::delete(public_path('assets/images/'.$currentMedia->media));
        $currentMedia->delete();
    }


}
