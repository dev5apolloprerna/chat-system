<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;    
use Kreait\Firebase\Messaging\CloudMessage;
use Auth;
use App\Models\User;
use App\Models\BranchUser;

class FirebasePushController extends Controller
{
    protected $notification;
    public function __construct()
    {
        $this->notification = Firebase::messaging();
    }

    public function setToken(Request $request)
    {
        $token = $request->input('fcm_token');
        $request->user()->update([
            'fcm_token' => $token
        ]); //Get the currrently logged in user and set their token
        return response()->json([
            'message' => 'Successfully Updated FCM Token'
        ]);
    }

    public function notification($request)
    {
        // dd($request);
        //$FcmToken = auth()->user()->fcm_token;
        $BranchUsers = BranchUser::where("branch_id",$request['branch_id'])->orderBy('branch_user_id','desc')->get();
        foreach($BranchUsers as $BranchUser){
            $title = $request['title'];
            $body = $request['body'];
            
            $FcmToken = User::where("id",$BranchUser->user_id)->pluck('device_token')->all();
            // dd($FcmToken);
            $url = 'https://fcm.googleapis.com/fcm/send';
            $data = [
                'registration_ids' => $FcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    "sound"=> "alarm_clock.mp3"
                ],
            ];
            
            $encodedData = json_encode($data);
            $serverKey =  "AAAAxNXKhR0:APA91bGG91_xo8sUitvT7UdQGtESIIRHupng_xSySFhH5vAR9LSngKV5RdOeekpOnXDqz-fsyCuWwZ5-JgCT26ZgGclM9wLagvxHBYj7N9DfvBU0kHHYo2oT4uLUckkDQwdSYNeFYbMV";
            $headers = [
                'Authorization:key=' . $serverKey,
                'Content-Type: application/json',
            ];
        
            $ch = curl_init();
          
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);        
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
            // Execute post
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }        
            // Close connection
            curl_close($ch);
            // FCM response
            //dd($result); 
        }
    }

}