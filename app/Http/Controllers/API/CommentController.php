<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;

use App\User;
use App\Comment;
use App\CommentLikes;
use App\ArticleCommentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
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

    public function storeComment(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'articleId' => 'required',
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
            $comment = Comment::create([
                'article_id' =>$request->articleId,
                'comment' =>$request->comment,
                'comment_by' => $userId,
                'comment_date' => date('Y-m-d H:i:s')
            ]);
            $commentDetails= Comment::where([['id','=',$comment->id]])->get();
            $userDetail=User::select('id','name','user_profile_images_url')->where([['id','=',$userId]])->get();
            $getCommentUserImage=null;
            if (!(empty($userDetail[0]->user_profile_images_url)))
            {
                $getCommentUserImage=$userDetail[0]->user_profile_images_url;
                $getCommentUserImage=url($getCommentUserImage);
                $getCommentUserImage=str_replace('index.php/', '', $getCommentUserImage);
            }
            $data['code']=1;
            $data['message']=null;
            $data['data']['commentUserId']=$userDetail[0]->id;
            $data['data']['commentUserName']=$userDetail[0]->name;
            $data['data']['commentUserImage']=$getCommentUserImage;
            $data['data']['parentCommentId']=$commentDetails[0]->id;
            $data['data']['userComment']=$commentDetails[0]->comment;
            $data['data']['noOfLikes']=0;
            $data['data']['noOfReply']=0;
            $data['data']['isLikeSelected']=0;
            $data['data']['isReportSelected']=0;
            $data['data']['replies']=[];
        }


        return response()->json($data);

    }

    public function likeComment(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'articleId' => 'required',
            'parentCommentId' => 'required'
        ]);
        if ($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            $ArticleLikes = CommentLikes::where('article_id',$request->articleId)->where('comment_id',$request->parentCommentId)->where('like_by',$userId)->first();
            if(!isset($ArticleLikes->id))
            {
                $createLikes = CommentLikes::create([
                    'article_id' =>$request->articleId,
                    'comment_id' =>$request->parentCommentId,
                    'like_by' =>$userId
                ]);
                Comment::find($request->parentCommentId)->increment('likes',1);
            }else{
                CommentLikes::where([['article_id', '=', $request->articleId],['comment_id','=',$request->parentCommentId],['like_by','=',$userId]])->delete();
                Comment::find($request->parentCommentId)->decrement('likes',1);
            }
            $commentLikes= Comment::select('id','likes')->where([['id','=',$request->parentCommentId]])->get();
            $data['code']=1;
            $data['message']=null;
            $data['data']= $commentLikes;
        }

        return response()->json($data);
    }

    public function reportComment(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'articleId' => 'required',
            'parentCommentId' => 'required'
        ]);
        if ($validator->fails())
        {

            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            $report=Comment::select('no_of_people_reported')->where('id','=',$request->parentCommentId)->get();
            $updateReport = Comment::find($request->parentCommentId);
            $updateReport->is_reported = 1;
            $updateReport->no_of_people_reported = ($report[0]->no_of_people_reported+1);

            if($updateReport->save())
            {
                $createReport = ArticleCommentReport::create([
                    'article_id' =>$request->articleId,
                    'comment_id' =>$request->parentCommentId,
                    'reported_by' =>$userId
                ]);
                $data['code']=1;
                $data['message']=null;
                $data['data']['articleId']=(int) $request->articleId;
                $data['data']['parentCommentId']=(int) $request->parentCommentId;
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

    // comment delete
    public function articleCommentDelete(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'commentId' => 'required'
        ]);
        if ($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            DB::table('article_comments')
            ->where('article_id', $request->commentId)
            ->update(array('is_deleted' => 1,'comment_deleted_date'=>date('Y-m-d H:i:s'),'comment_deleted_by'=>$userId));
            $data['code']=1;
            $data['message']='Article Comment Deleted Successfully';
            $data['data']=null;
        }
        return response()->json($data);
    }


}
