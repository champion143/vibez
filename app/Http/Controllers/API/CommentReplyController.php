<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;

use App\User;
use App\CommentReply;
use App\ReplyLikes;
use App\ArticleCommentReplyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class CommentReplyController extends Controller
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

    public function storeReplies(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'articleId' => 'required',
            'parentCommentId' => 'required',
            'comment' => 'required'
        ]);
        if ($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
             $commentReply = CommentReply::create([
                'article_id' =>$request->articleId,
                'comment_id' =>$request->parentCommentId,
                'comment' =>$request->comment,
                'comment_by' => $userId,
                'comment_date' => date('Y-m-d H:i:s')
            ]);
            $replyDetails= CommentReply::where([['id','=',$commentReply->id]])->get();
            $userReplyDetail=User::select('id','first_name','last_name','user_profile_images_url')->where([['id','=',$userId]])->get();
            $getCommentReplyUserImage=null;
            if (!(empty($userReplyDetail[0]->user_profile_images_url)))
            {
                $getCommentReplyUserImage=$userReplyDetail[0]->user_profile_images_url;
                $getCommentReplyUserImage=url($getCommentReplyUserImage);
                $getCommentReplyUserImage=str_replace('index.php/', '', $getCommentReplyUserImage);
            }
            $data['code']=1;
            $data['message']=null;
            $data['data']['replyUserId']=$userReplyDetail[0]->id;
            $data['data']['replyUserName']=$userReplyDetail[0]->first_name." ".$userReplyDetail[0]->last_name;
            $data['data']['replyUserImage']=$getCommentReplyUserImage;
            $data['data']['replyId']=$replyDetails[0]->id;
            $data['data']['parentCommentId']=$replyDetails[0]->comment_id;
            $data['data']['userReply']=$replyDetails[0]->comment;
            $data['data']['replyNoOfLikes']=0;
            $data['data']['isLikeSelected']=0;
            $data['data']['isReportSelected']=0;

        }


        return response()->json($data);
    }


    public function likeReplies(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'articleId' => 'required',
            'parentCommentId' => 'required',
            'replyId' => 'required'
        ]);
        if ($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            $ArticleLikes = ReplyLikes::where('article_id',$request->articleId)->where('comment_id',$request->parentCommentId)->where('reply_id',$request->replyId)->where('like_by',$userId)->first();
            if(!isset($ArticleLikes->id))
            {
                $createLikes = ReplyLikes::create([
                    'article_id' =>$request->articleId,
                    'comment_id' =>$request->parentCommentId,
                    'reply_id' =>$request->replyId,
                    'like_by' =>$userId
                ]);
                CommentReply::find($request->replyId)->increment('likes',1);
            }else{
                ReplyLikes::where([['article_id', '=', $request->articleId],['comment_id','=',$request->parentCommentId],['reply_id','=',$request->replyId],['like_by','=',$userId]])->delete();
                CommentReply::find($request->replyId)->decrement('likes',1);
            }
            $commentLikes= CommentReply::select('id','likes')->where([['id','=',$request->replyId]])->get();
            $data['code']=1;
            $data['message']=null;
            $data['data'] = $commentLikes;
        }

        return response()->json($data);
    }

    public function reportReplies(Request $request)
    {
        $header = $request->header('Authorization');
        $userId = $request->header('userId');

        $validator = Validator::make($request->all(), [
            'articleId' => 'required',
            'parentCommentId' => 'required',
            'replyId' => 'required'
        ]);


        if ($validator->fails())
        {

            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            $report=CommentReply::select('no_of_people_reported')->where('id','=',$request->replyId)->get();

            $updateReport = CommentReply::find($request->replyId);
            $updateReport->is_reported = 1;
            $updateReport->no_of_people_reported = ($report[0]->no_of_people_reported+1);

                if($updateReport->save())
                {

                    $createReport = ArticleCommentReplyReport::create([
                     'article_id' =>$request->articleId,
                     'comment_id' =>$request->parentCommentId,
                     'reply_id' =>$request->replyId,
                     'reported_by' =>$userId
                     ]);

                    $data['code']=1;
                    $data['message']=null;
                     $data['data']['articleId']=(int) $request->articleId;
                    $data['data']['parentCommentId']=(int) $request->parentCommentId;
                    $data['data']['replyId']=(int) $request->replyId;
                    $data['data']['isReportSelected']=1;

                }
                else
                {
                    $data['code']=0;
                    $data['message']="We are facing some issue we will get back to you soon";
                    $data['data']=null;
                }
        }
        return response()->json($data);
    }


}
