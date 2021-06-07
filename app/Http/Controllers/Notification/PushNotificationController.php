<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationUser;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PushNotificationController extends Controller
{
    //save device token
    public function saveToken (Request $request)
    {
        $validator = Validator::make($request->all(),[
            'token_fcm' =>'required'
        ]);
        if($validator->fails()){
            return response()->json([  'error'=>$validator->errors()]);
        }
        $user = Auth::user();
        $user->update(['device_token'=>$request->token_fcm]);
        return response()->json(['token saved successfully.']);

    }

    public function sendPushNotification(Request $request)
    {
        /*
            you should verify that user is Admin ...

        */
        //validate data
        $validator=Validator::make($request->all(),[
            'title'=>'required|string',
            'body'=>'required'
        ]);
        if($validator->fails()){
            return response()->json([  'error'=>$validator->errors()]);
        }
        //store notification in database
        $push_notification=new Notification();
        $push_notification->title=$request->input('title');
        $push_notification->body=$request->input('body');
        $push_notification->save();

        //get users whose accept recieving notifications
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
            //all tokens of device users
            "registration_ids"=>$firebaseToken,

            /*
              to send notifiation to one user use "to" instead of "registration_ids"
             */
            //"to" =>"wkUtnTH-7ztwP92PFef:APA91bF2Ji4yUxfCsgs_Ng0B5lwEsmqhJQd6IB_AZNSwbL6oYC8VViwbVHryt9pk8VyB1Sz3fItjC9X2Qxkz5__OT9Lain-vAh7zuSt8V6UZyMROb1FVQnDgs8FCTGO1Kv7FXvSb2l4k",
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

        if(!$response)
            trigger_error(curl_error($ch));


            //verify if sucess response ==1
        $sucessResponse = json_decode($response);
        if($sucessResponse->success==1){
            //if notification sended sucessfully then keep users id with notifiation id
            try {
                $push_notification->users()->syncWithoutDetaching($users);
                return response()->json(['response' =>$response ,'message' => 'notification successfully sended ']);
            } catch (\Throwable $th) {
                return response()->json(['error'=>$th->getMessage()]);
            }
        }else{
            return response()->json(['data'=>$response]);
        }


    }



    //display all notification for one user
    public function displayNotifications(Request $request){
        $user_id = Auth::user()->id;
        $notifications = Notification::whereHas('users', function ($q) use($user_id) {
            $q->where('users.id', $user_id);
        })->get();
        if($notifications->isEmpty()){
            return response()->json(['error'=>'empty notifications list']);
        }
       return response()->json([
                'message'=>$notifications]);
    }


    //show content of  specific notification
    public function showNotification($id){
        $notification = Notification::find($id);
        if (!$notification) {
           return response()->json([  'error'=>'notification not founded']);
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
                return response()->json([
                    'data'=>$user,
                    'message' =>'notification closed successfully.']);
            }
            return response()->json(['error'=>'can\'t close notifications']);
    }

    //user want to allow recieving notifications
    public function openNotification(Request $request){

             $user = Auth::user();
            if( $user->accept_notification==1){
                return response()->json([
                    'notifications already opened']);
            }
            else{
                $user->accept_notification=1;
                $user->save();
                return response()->json([
                    'data'=>$user,
                    'message' => 'notifications opened successfully.']);
            }
            return response()->json(['error'=>'can\'t close notifications']);

    }
    //mark notification as read
    public function markasread($id){
        $user=Auth::user();
        $notification_user=NotificationUser::where('user_id',$user->id)->where('notification_id',$id)->get();
        if($notification_user->isEmpty())
            return response()->json(['error' =>'notification not founded']);
        //dd($notification_user);
        try {

            DB::table('notification_user')->where('notification_id',$id)->where('user_id', $user->id)->update([ 'read' => 1]);
            return response()->json([
                'data'=> $notification_user,
                'message'=>'notification marked as read successfully']);

        } catch (\Throwable $th) {
            return response()->json(['error'=>$th->getMessage()]);
        }

    }

    //delete a notification for user
    public function deleteNotification(Request $request,$idNotification){
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
