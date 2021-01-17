<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;

use App\Chat;
use App\ChatTag;
use App\User;
use Illuminate\Http\Request;

class ChatController extends Controller
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

    /* send message */
    public function sendMessage(Request $request)
    {
        $message = $request->input('message','');
        $tags_selected = $request->input('tags_selected','');
        $reciever_id = $request->input('reciever_id',0);
        $Chat = new Chat;
        $Chat->message = $message;
        $Chat->user_id = $this->userId;
        $Chat->tag = $tags_selected;
        $Chat->reciever_id = $reciever_id;
        if ($request->hasFile('attachment')) {
            $image = $request->file('attachment');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/attachment');
            $image->move($destinationPath, $name);
            $Chat->file_type = 'docs';
            $Chat->file = $name;
        }
        $Chat->save();
        return response()->json(['success'=>true,'data'=>array(),'message'=>'message send successfully']);
    }

    /* send bulk message */
    public function sendBulkMessage(Request $request)
    {
        $message = $request->input('message','');
        $tags_selected = $request->input('tags_selected','');
        $reciever_ids = $request->input('reciever_id');
        $reciever_ids = json_decode($reciever_ids);
        $filename = "";
        $file_type = "";
        if ($request->hasFile('attachment')) {
            $image = $request->file('attachment');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/attachment');
            $image->move($destinationPath, $name);
            $filename = $name;
            $file_type = "docs";
        }
        foreach($reciever_ids as $reciever_id)
        {
            $Chat = new Chat;
            $Chat->message = $message;
            $Chat->user_id = $this->userId;
            $Chat->tag = $tags_selected;
            $Chat->reciever_id = $reciever_id;
            $Chat->file_type = $file_type;
            $Chat->file = $filename;
            $Chat->save();
        }
        return response()->json(['success'=>true,'data'=>array(),'message'=>'message send successfully']);
    }

    /* get message */
    public function getMessage(Request $request)
    {
        $reciever_id = $request->input('reciever_id',0);
        $allChats = Chat::with('tags')->where('user_id',$this->userId)->where('reciever_id',$reciever_id)->get();
        foreach($allChats as $allChat)
        {
            if($allChat->file_type == 'docs')
            {
                $allChat->file = url('attachment').'/'.$allChat->file;
            }
        }
        return response()->json(['success'=>true,'data'=>$allChats,'message'=>'message get successfully']);
    }

    // chat tag
    public function getChatTag()
    {
        $allChatTags = ChatTag::all();
        return response()->json(['success'=>true,'data'=>$allChatTags,'message'=>'Get All Chat Tags successfully']);
    }
}
