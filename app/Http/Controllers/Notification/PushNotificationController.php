<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushNotificationController extends Controller
{
    //save device token
    public function saveToken (Request $request)
    {
        $request->validate([
            'token_fcm' =>'required'
        ]);
        $user = Auth::user();
        $user->update(['device_token'=>$request->token_fcm]);
        return response()->json(['token saved successfully.']);

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

        //get users
        $users=User::where('accept_notification',1)->whereNotNull('device_token')->get();

        //get tokens users
        $firebaseToken =$users->pluck('device_token')->all();
        //dd($firebaseToken);
        //add server api key from firebase
        $SERVER_API_KEY = "AAAAVCYn75s:APA91bHwJJgmeXdm5eJPSPt21xCbyoYRORkmLn-1PZ73oPUVK48a4heJpWC696bIAxzUlp6va46X_rqQdGN2qcrjDwGtO9qDtzh66GMDdcugjAIa05EOdkV82ms9oZzbjMWEgYNi8cuv";
        /**
         * send notification with firebase cloud messaging
         */
        $data = [
            "registration_ids"=>$firebaseToken,
            //"to" =>"cYmwkUtnTH-7ztwP92PFef:APA91bF2Ji4yUxfCsgs_Ng0B5lwEsmqhJQd6IB_AZNSwbL6oYC8VViwbVHryt9pk8VyB1Sz3fItjC9X2Qxkz5__OT9Lain-vAh7zuSt8V6UZyMROb1FVQnDgs8FCTGO1Kv7FXvSb2l4k",
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
        //if($response == 200)
        //save id notification with is users in notification_user table
        $push_notification->users()->syncWithoutDetaching($users);
        dd($response);

    }



    //display all notification for one user
    public function displayNotifications(Request $request){
        $user_id = Auth::user()->id;
        $notifications = Notification::whereHas('users', function ($q) use($user_id) {
            $q->where('users.id', $user_id);
        })->get();

       return response()->json([
                'message'=>$notifications]);
    }


    //show content of  specific notification
    public function showNotification($id){
        $notification = Notification::find($id);
        if (is_null($notification)) {
           return response()->json([  'error'=>'not exist']);
        }
        return response()->json([ 'message'=>$notification]);
    }

    //user want to stop receiving notfications
    public function closeNotification(Request $request){

            $user = Auth::user();
            if( $user->accept_notification==0){
                return response()->json(['notification already closed']);
            }
            else{
                $user->accept_notification=0;
                $user->save();
                return response()->json(['notification closed successfully.']);
            }
            return response()->json(['error'=>'can\'t close notifications']);
    }

    //user want to allow recieving notifications
    public function openNotification(Request $request){

             $user = Auth::user();
            if( $user->accept_notification==1){
                return response()->json(['notifications already opened']);
            }
            else{
                $user->accept_notification=1;
                $user->save();
                return response()->json(['notifications opened successfully.']);
            }
            return response()->json(['error'=>'can\'t close notifications']);

    }
    public function markasread($id){
        $user=Auth::user();
       $t=$user->notifications()->sync([$id => ['read' =>1]]);
        dd($t);
    }

    public function deleteNotification($idNotification){
        $user_id=Auth::id();
        $notification=NotificationUser::where('user_id',$user_id)->where('notification_id',$idNotification)->first();
        if($notification){
            try {
                $notification->delete();
                return response()->json(['notification deleted successfully.']);
            } catch (\Throwable $th) {
                return response()->json(['error'=>'can\'t delete notification',$th->getMessage()]);
            }
        }
        else{
            return response()->json(['error'=>'this notification not founded']);
        }
    }

    public function clearNotifications(Request $request){
        $user_id=Auth::id();
        $notifications=NotificationUser::where('user_id',$user_id)->get();
        try {
            foreach($notifications as $item){
                $item->delete();
            }
            return response()->json(['notification deleted successfully.']);
        }catch(\Throwable $th){
            return response()->json(['error'=>'can\'t delete notifications',$th->getMessage()]);
        }
    }
}
