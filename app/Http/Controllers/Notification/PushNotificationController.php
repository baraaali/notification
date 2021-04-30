<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushNotificationController extends Controller
{

    public function sendPushNotification(Request $request)
    {
        //validate data
        $validateData=$request->validate([
            'title'=>'required|string',
            'body'=>'required'
        ]);
        //store notification in database
        $push_notification=new Notification();
        $push_notification->title=$request->input('title');
        $push_notification->body=$request->input('body');
        $push_notification->save();
        //get list of users whose accept receiving notifications
         $list_users=User::where('accept_notification',1)->whereNotNull('device_token')->get();

        $users_id = [];
        foreach ($list_users as $item) {
        $users_id[] = $item['id'];
        }
        //save id notification with is users in notification_user table
        $push_notification->users()->syncWithoutDetaching($users_id);
        //get tokens users
        $firebaseToken = $list_users->pluck('device_token')->all();
        //add server api key from firebase
        $SERVER_API_KEY = "AAAAVCYn75s:APA91bHwJJgmeXdm5eJPSPt21xCbyoYRORkmLn-1PZ73oPUVK48a4heJpWC696bIAxzUlp6va46X_rqQdGN2qcrjDwGtO9qDtzh66GMDdcugjAIa05EOdkV82ms9oZzbjMWEgYNi8cuv";
        /**
         * send notification with firebase cloud messaging
         */
        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "title" => $request->title,
                "body" => $request->body,
            ]
        ];
        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);
        dd( $response);
    }


    //display all notification for one user
    public function displayNotifications($id){

        $notifications = Notification::whereHas('users', function ($q) use($id) {
            $q->where('users.id', $id);
        })->get();

       return response()->json([
                'message'=>$notifications]);}

    //show a specific notification
    public function showNotification($id){
        $notification = Notification::find($id);
        if (is_null($notification)) {
           return response()->json([  'error'=>'not exist']);
        }
        return response()->json([ 'message'=>$notification]);
    }






}
