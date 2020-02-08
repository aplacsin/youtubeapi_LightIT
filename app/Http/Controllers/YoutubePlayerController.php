<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use App\Videos;

class YoutubePlayerController extends Controller
{
    /* Main Page */
    public function index()
    { 
        return view('youtube.index');
    }


    
    /* Search Videos */
    public function search(Request $request)
    {
        $options = [
            'maxResults' => 12, 
            'q' => $request->input('query'),
            'type' => 'video'
        ];
       
        if ($request->input('page')) {
             $options['pageToken'] = $request->input('page');
        } 

        $youtube = \App::make('youtube');
        $videos = $youtube->search->listSearch("snippet", $options);

        /* DATABASE */
        try {
        $data = [];        
        foreach ($videos as $video) {
            
            $data[] = [ 
                'query' => $request->input('query'),              
                'video_id' => $video['id']['videoId'],
                'title' => $video['snippet']['title'],                
                'image' => $video['snippet']['thumbnails']['medium']['url'],
                'published' => $video['snippet']['publishedAt']
            ];
            
        }
        $videosdb = new Videos();
        $videosdb->insert($data);        
        $videosdb->save();
        } 
        catch (\Exception $e)
        {            
            return $e;
        }


        /* Check Like */
        if (Auth::check()) {
            $posts = Videos::all();
        }
        else {
            return redirect('login')->with('status', 'Сначала нужно авторизоваться на сайте');
        }


        // after video ends, use relatedToVideoId for suggestions
        return view("youtube.search", compact('posts'), ['videos' => $videos, 'query' => $request->input('query')]);
        
    }




    /* Add Likes Video */
    public function favoriteVideo(Request $video_id)
    {        
        $vid = $video_id->video_id;
        $user = Videos::where('video_id', $vid)->first();
        Auth::user()->favorites()->attach($user->id);
        return back();
    }

    


    /* Remove Likes Video */
    public function unFavoriteVideo(Request $video_id)
    {
        $vid = $video_id->video_id;
        $user = Videos::where('video_id', $vid)->first();
        Auth::user()->favorites()->detach($user->id);
        return back();
    }
}