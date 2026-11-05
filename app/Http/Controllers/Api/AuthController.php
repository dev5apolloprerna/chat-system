<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\BranchCustomer;
use App\Models\BranchSetting;
use App\Models\BranchUser;
use App\Models\Customer;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','admin_login']]);
    }

    public function login(Request $request)
    {
        if($request->mobile_number !="" && $request->password != "")
        {
            $User = User::where(['mobile_number'=>trim($request->mobile_number),'status'=>1])->first();
            if(!empty($User)){
                $credentials = request(['mobile_number', 'password']);
        
                $token = auth()->guard('api')->attempt($credentials);
        
                if (!$token) {
                    //return response()->json(['error' => 'Unauthorized'], 401);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized User.',
                    ], 401);
                } else {
                    $device_token = $request->device_token;
                    User::where('id',$User->id)->update([
                        'device_token' => $device_token
                    ]);
                    //$user = Auth::user();
                    $user = auth()->guard('api')->user();
                    $Branch_Setting = [];
                    if($user->role_id == 2)
                    {
                        $BranchCustomer=BranchCustomer::where(['customer_user_id'=>$user->id])->join('branch_master','branch_master.branch_id','=','branch_customer.branch_id')->first();
                        $customer=Customer::where(['customer_user_id'=>$user->id])->first();
                        $BranchSetting = BranchSetting::where(["branch_id"=>$BranchCustomer->branch_id])->first();

                        $branch_id=$BranchCustomer->branch_id;
                        $branch_name=$BranchCustomer->branch_name;
                        $address1=$customer->customer_office_address ?? "";
                        $address2=$customer->customer_home_address ?? "";
                        $order_status=$BranchSetting->order_status ?? "";
                        if($BranchSetting != null)
                        {
                            $Branch_Setting = array(
                                "branch_setting_id" => $BranchSetting->branch_setting_id,
                                "branch_id" => $BranchSetting->branch_id,
                                "order_status" => $BranchSetting->order_status,
                                "only_take_away" => $BranchSetting->only_take_away,
                                "delivery_status" => $BranchSetting->delivery_status,
                                "payment_type" => $BranchSetting->payment_type,
                                "online_option" => $BranchSetting->online_option,
                                "punjabi_kitchen_on_time" => isset($BranchSetting->punjabi_kitchen_on_time) && $BranchSetting->punjabi_kitchen_on_time != "" ? date('H:i',strtotime($BranchSetting->punjabi_kitchen_on_time)) : "",
                                "punjabi_kitchen_off_time" => isset($BranchSetting->punjabi_kitchen_off_time) && $BranchSetting->punjabi_kitchen_off_time != "" ? date('H:i',strtotime($BranchSetting->punjabi_kitchen_off_time)) : "",
                                "pavbhaji_kitchen_on_time" => isset($BranchSetting->pavbhaji_kitchen_on_time) && $BranchSetting->pavbhaji_kitchen_on_time != "" ? date('H:i',strtotime($BranchSetting->pavbhaji_kitchen_on_time)) : "",
                                "pavbhaji_kitchen_off_time" => isset($BranchSetting->pavbhaji_kitchen_off_time) && $BranchSetting->pavbhaji_kitchen_off_time !="" ? date('H:i', strtotime($BranchSetting->pavbhaji_kitchen_off_time)) : "",
                                "other_product_on_time" => isset($BranchSetting->other_product_on_time) && $BranchSetting->other_product_on_time ? date('H:i',strtotime($BranchSetting->other_product_on_time)) : $BranchSetting->other_product_on_time,
                                "other_product_off_time" => isset($BranchSetting->other_product_off_time) && $BranchSetting->other_product_off_time != "" ? date('H:i',strtotime($BranchSetting->other_product_off_time)) : "",
                                "branch_qrcode" => $BranchSetting->branch_qrcode,
                                "mobile_number" => $BranchSetting->mobile_number,
                                "upi_id" => $BranchSetting->upi_id,
                                "order_ready_time" => $BranchSetting->order_ready_time
                            );
                        }

                    }else if($user->role_id == 3)
                    {
                        $BranchUser=BranchUser::where(['user_id'=>$user->id])->join('branch_master','branch_master.branch_id','=','branch_user.branch_id')->first();
                        $branch_id=$BranchUser->branch_id;
                        $branch_name=$BranchUser->branch_name;
                        $address1="";
                        $address2="";
                    }else{
                        $branch_id=0;
                        $branch_name="";
                        $address1="";
                        $address2="";
                    }
                    $userdata=array(
                       "id"=>$user->id,
                        "full_name"=>$user->full_name,
                        "email"=>$user->email,
                        "mobile_number"=>$user->mobile_number,
                        "role_id"=>$user->role_id,
                        "branch_id"=>$branch_id,
                        "branch_name"=>$branch_name,
                        "customer_office_address"=>$address1,
                        "customer_home_address"=>$address2,
                        "order_status"=>$order_status ?? "",
                        "status"=>$user->status,
                        "reason"=>$user->reason,
                        "strIP"=> $user->strIP
                    );
                    return response()->json([
                        'status' => 'success',
                        'message' => 'User List Found.',
                        'user' => $userdata,
                        'BranchSetting' => $Branch_Setting,
                        'authorisation' => [
                            'token' => $token,
                            'type' => 'bearer',
                        ]
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User Not Found.',
                ], 401);
            }
        } else 
        {
            return response()->json([
                'status' => 'error',
                'message' => 'Login Id or Password is black.',
            ], 401);
        }
    }
    public function admin_login(Request $request)
    {
         try
        {
            if(($request->mobile_number != "") && $request->password != "" )
            {
                $User = User::where(['mobile_number'=>$request->mobile_number,'role_id'=>1])->first();
                if(!empty($User))
                {
                    if ($User && Hash::check($request->password, $User->password)) 
                    {
                    
                        $credentials = request(['mobile_number', 'password']);
                
                $token = auth()->guard('api')->attempt($credentials);
        
        
                        if (!$token) 
                        {
                            //return response()->json(['error' => 'Unauthorized'], 401);
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Unauthorized User.',
                            ], 401);
                        } else 
                        {
                            $device_token = $token;
                            User::where('id',$User->id)->update([
                                'device_token' => $device_token
                            ]);
        
                            //$user = Auth::user();
                            $user = auth()->guard('api')->user();
                            
                            $userdata=array(
                               "id"=>$user->id,
                                "name"=>$user->full_name,
                                "email"=>$user->email,
                                "mobile_number"=>$user->mobile_number,
                                "role_id"=>$user->role_id,
                                "status"=>$user->status
                            );
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Login Successfully.',
                                'user' => $userdata,
                                // 'key' => 'rzp_test_F1sbFWuynDVD5y',
                                // 'salt' => '138T9QwDedJsi1fY8NXJzTwV',
                                'authorisation' => [
                                    'token' => $token,
                                    'type' => 'bearer',
                                ]
                            ]);
                        }
                    } else 
                    {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Please enter valid password.',
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User Not Found.',
                    ], 401);
                }
            } else 
            {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Login Id or Password is blank.',
                ], 401);
            }
        } catch (\Exception $e) 
        {
            return response()->json([
                    'status' => 'error',
                    'message' => 'something went wrong!',
                ]);
            //report($e);
     
            return false;
        }
    }

    public function logout()
    {
        // Auth::logout();
        // return response()->json([
        //     'status' => '1',
        //     'message' => 'Successfully logged out',
        // ]);
        auth()->guard('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function me()
    {
        return response()->json([
            'status' => '1',
            'user' => auth()->guard('api')->user(),
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'sucess',
            'user' => auth()->guard('api')->user(),
            'authorisation' => [
                'token' => auth()->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
    public function change_password(Request $request)
    {
        if(auth()->guard('api')->user())
        {
            $user_login = auth()->guard('api')->user();
            $userData = User::where(['mobile_number'=>trim($request->mobile_number),'role_id'=>1])->first();
             if(!empty($userData))
             {
                $user=User::where(['id'=>$user_login->id,'role_id'=>1])->first();
                if(!empty($user))
                {
                    $newpassword = $request->new_password;
                    $confirmpassword = $request->new_confirm_password;

                    if($newpassword == $confirmpassword) 
                    {
                        $Branch = DB::table('users')
                            ->where(['status' => 1, 'id' => $user_login->id])
                            ->update([
                                'password' => Hash::make($confirmpassword),
                            ]);
                            return response()->json([
                                    'status' => 'success',
                                    'message' => 'Password Updated Successfully'
                                ]);

                    }else 
                    {
                        return response()->json([
                                'status' => 'error',
                                'message' => 'password and confirm password does not match',
                            ], 401);
                    }
                }else{
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Requested User Not Found.',
                ], 401);

                }

             } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User Not Found.',
                ], 401);
            }
        }else{
            return response()->json([
                    'status' => 'error',
                    'message' => 'User is not Authorised.',
            ], 401);
        }

    }

}