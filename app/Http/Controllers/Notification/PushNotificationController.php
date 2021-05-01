<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushNotificationController extends Controller
{
    //save device token
    public function saveToken (Request $request)
    {
        $user = Auth::user();
        $user['device_token']=$request->token;
        return response()->json(['token saved successfully.',$user->device_token]);

    }

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
            "registration_ids"=> "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiNjU5ZjRiNzlmMGZhYTQwY2QxMDA4MmRkMWRjOTI4ODA2ZmQ3YmM3ZmIyNWZjM2YzNDkzMzU5NGYzMmY0YjQyN2ViYTNkOTg5NzRkNzY4MTIiLCJpYXQiOjE2MTk3ODY1NDMuMzM0OTUsIm5iZiI6MTYxOTc4NjU0My4zMzQ5OTIsImV4cCI6MTY1MTMyMjU0My4zMTU4MTMsInN1YiI6IjIiLCJzY29wZXMiOltdfQ.iykGWgjqVqI-KBXdtP7IUT0RVKkpKWHV3rqo1pKNv83_pMJRszKk12MqoW1ckY4q-O7IAcYlA0ieY566emAzkptpyNxf7g0nMiN6QluGxKDoDTBkW_q_IBWtZR2JkFJ0mMqUvp8byPWVDW0OS2mw9bIo_vlHsQkegMzojJQ51mtSwQE6FgWJmbhNGfMq3465fjUObfqYe3aBNn8ktrFr8IgPklWSFXmLlJ9S8TrxICHbO_Eq4ueCNs3ZT7Ll-NtPkPN8z7hywq_CJbEV_MNuccUYPuzXw9a5ZcrCjV-buOcu_Dwa98tbPNNvNryjDnCashOJi211Tw1i2zEKraqdwFKZiQkPWsF2nCFNvgk6BvP9P27GU9HxXsIWxhJuUz6Bl6ea8eTbFNGSydVFrL2haFVY2thbrLCqBaShgawCDrR0_I-AsvmC7ApKAWs1fn7lErIh22Pu14dJnA4-ehu6FRb6ZYE28dQy_QgKgLpuwBZN3r_kC1LPQ50nZUtEkHPSvQb2BiNwbgtQvi8oXz0DDwOSpwYL327_qBsaNDGxx3NfaUehHlrTSnFVyMdobcxgTR09nYCTNVpqCzwjIkR_mgeIce5FpEd2E0kuEcWV-quyWi0VtfLxPWIo6CShXFi14-C710ofi-i6PWKuNOfrNWcL2luquUut77l4Si9E5sc",
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
    public function displayNotifications(Request $request){
        $user_id = Auth::user()->id;
        $notifications = Notification::whereHas('users', function ($q) use($user_id) {
            $q->where('users.id', $user_id);
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
