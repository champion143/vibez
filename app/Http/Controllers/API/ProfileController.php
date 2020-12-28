<?php

namespace App\Http\Controllers\API;

use App\Car;
use App\Follow;
use App\Http\Controllers\Controller;
use App\User;
use App\UserReport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

class ProfileController extends Controller
{
    protected $userId;

    public function __construct(Request $request)
    {
        $headers = getallheaders();
        if(isset($headers['token']))
        {
            $check = User::where('api_token',$headers['token'])->first();
            if(!isset($check->id))
            {
                return response()->json(['success'=>false,'data'=>array(),'message'=>'token mis matched'], 401);
                die();
            }else{
                $this->userId = $check->id;
            }
        }else{
            return response()->json(['success'=>false,'data'=>array(),'message'=>'token blanked'], 401);
            die();
        }
    }
    //
    public function index(Request $request)
    {
        $userDetail = User::where('api_token',$request->header('token'))->first();
        if(isset($userDetail->id))
        {
            $userDetail->follower_count = Follow::where('following_id',$userDetail->id)->count();
            $userDetail->following_count = Follow::where('follower_id',$userDetail->id)->count();
            return response()->json(['success'=>true,'data'=>$userDetail,'message'=>'user profile get successfully'], 200);
        }else{
            return response()->json(['success'=>false,'data'=>array(),'message'=>'user not found'], 401);
        }
    }

    // update profile
    public function update(Request $request)
    {
        $userDetail = array();
        $userDetail['first_name'] = $request->input('first_name');
        $userDetail['last_name'] = $request->input('last_name');
        $userDetail['racername'] = $request->input('racername');
        $userDetail['zipcode'] = $request->input('zipcode');
        User::where('id',$this->userId)->update($userDetail);
        $userData = User::where('id',$this->userId)->first();
        return response()->json(['success'=>true,'data'=>$userData,'message'=>'User Profile Updated successfully'], 200);
    }

    public function getCarDetail(Request $request,$id)
    {
        $Car = Car::where('id',$id)->first();
        return response()->json(['success'=>true,'data'=>$Car,'message'=>'Item Registered successfully'], 200);
    }

    // do follow and un follow
    public function followStatusChange(Request $request)
    {
        $following_id = $request->input('following_id');
        $follower_id = $this->userId;
        if($following_id == $follower_id)
        {
            $message = 'User Can not follow own';
            return response()->json(['success'=>true,'data'=>array(),'message'=>$message], 200);
        }else{
            $UserCount = User::where('id',$following_id)->count();
            if($UserCount <= 0)
            {
                $message = "Following User Not Found";
                return response()->json(['success'=>true,'data'=>array(),'message'=>$message], 200);
            }else{
                $count = Follow::where('following_id',$following_id)->where('follower_id',$follower_id)->count();
                if($count > 0)
                {
                    Follow::where('following_id',$following_id)->where('follower_id',$follower_id)->delete();
                    $message = 'User Un-follow Successsfully';
                }else{
                    $follow = new Follow;
                    $follow->following_id = $following_id;
                    $follow->follower_id = $follower_id;
                    $follow->save();
                    $message = 'User Follow Successsfully';
                }
                return response()->json(['success'=>true,'data'=>array(),'message'=>$message], 200);
            }
        }
    }

    // followers list
    public function followerList()
    {
        $follwerList = Follow::where('following_id',$this->userId)->with('followingUser')->get();
        return response()->json(['success'=>true,'data'=>$follwerList,'message'=>"Follower List Get Successfully"], 200);
    }

    // followers list
    public function followingList()
    {
        $follwerList = Follow::where('follower_id',$this->userId)->with('followerUser')->get();
        return response()->json(['success'=>true,'data'=>$follwerList,'message'=>"Following List Get Successfully"], 200);
    }

    // report user
    public function reportUser(Request $request)
    {
        $reciever_id = $request->input('reciever_id');
        $report_id = $request->input('report_id');
        $count = UserReport::where('user_id',$this->userId)->where('reciever_id',$reciever_id)->count();
        if($count > 0)
        {
            return response()->json(['success'=>false,'data'=>array(),'message'=>"User Already Reported this account"], 200);
        }else{
            $UserReport = new UserReport;
            $UserReport->user_id = $this->userId;
            $UserReport->reciever_id = $reciever_id;
            $UserReport->report_id = $report_id;
            $UserReport->save();
            return response()->json(['success'=>true,'data'=>$UserReport,'message'=>"Account Reported Successfully"], 200);
        }
    }


}
