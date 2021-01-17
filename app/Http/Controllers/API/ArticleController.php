<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;

use App\Article;
use App\Bookmark;
use App\ArticleMedia;
use App\ArticleLikes;
use App\ArticleReport;
use App\User;
use App\Comment;
use App\CommentReply;
use App\CommentLikes;
use App\ArticleCommentReport;
use App\ReplyLikes;
use App\ArticleCommentReplyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
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

    public function createArticle(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'forumId' => 'required',
            'articleTitle' => 'required',
            'articleDescription' => 'required',
            'articleMediaType' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            $article = Article::create([
                'forum_id' =>$request->forumId,
                'type' =>$request->type,
                'is_anonymous' =>$request->isAnonymous,
                'article_title' =>$request->articleTitle,
                'article_description' => $request->articleDescription,
                'created_from' => 1,
                'created_by'=>$userId,
                'updated_by' =>$userId
            ]);

            if($request->articleMediaType==1)
            {
                if ($request->hasFile('media'))
                {
                    $Imagedata= getimagesize($_FILES["media"]['tmp_name']);
                    $image = $request->file('media');
                    $name = time().'.'.$image->getClientOriginalExtension();
                    $destinationPath = public_path('/upload/images/article');
                    $image->move($destinationPath, $name);
                    $img_url = '/upload/images/article/'.$name;
                    $articleMedia = ArticleMedia::create([
                        'article_id' =>$article->id,
                        'media_type' =>$request->articleMediaType,
                        'media_url' => $img_url,
                        'width' => $Imagedata[0],
                        'height' => $Imagedata[1],
                        'created_by'=>$userId,
                        'updated_by' =>$userId
                    ]);
                }
            }

            $data['code']=1;
            $data['message']='Article posted';
            $data['data']=null;

        }

        return response()->json($data);
    }

    /* recommended articles */
    public function allArticleList(Request $request)
    {
        $userId = $this->userId;
        $forumId = 0;
        $articleList= Article::where([['article.forum_id','=',$forumId],['article.is_active','=',1]])
                ->leftJoin('users','article.created_by','=','users.id')
                ->select('users.id as user_id','users.name','users.user_profile_images_url','article.id as article_id','article.forum_id','article.type','article.is_anonymous','article.article_title','article.created_at','article.likes')
                ->orderBy('article.created_at', 'DESC')
                ->paginate(10);
        if(count($articleList)==0)
        {
            $data['code']=1;
            $data['message']='No Post Found';
            $data['data']['articleList']=[];
        }
        else
        {
            $articleUserList = array();
            foreach ($articleList as $article)
            {
                $articleCommentCount=Comment::where([['article_id','=',$article->article_id],['is_deleted','=',0]])->count();
                $commentReplycount=CommentReply::where([['article_id','=',$article->article_id],['is_deleted','=',0]])->count();
                $commentCount=($articleCommentCount+$commentReplycount);
                $getUserImage=null;
                if (!(empty($article->user_profile_images_url)))
                {
                    $getUserImage=$article->user_profile_images_url;
                    $getUserImage=url($getUserImage);
                    $getUserImage=str_replace('index.php/', '', $getUserImage);
                }else
                {
                    $getUserImage='upload/images/appicon.png';
                    $getUserImage=url($getUserImage);
                    $getUserImage=str_replace('index.php/', '', $getUserImage);
                }

                $articleMediaId=null;
                $articleMediaType=null;
                $articleMediaURL=null;
                $imageWidth=null;
                $imageHeight=null;

                $articleMedia=ArticleMedia::where([['article_id','=',$article->article_id]])->get();
                if(count($articleMedia)!=0)
                {
                     $articleMediaType=$articleMedia[0]->media_type;
                     $articleMediaId=$articleMedia[0]->id;

                    if($articleMedia[0]->media_type==1)
                    {

                        $getArticleImage=$articleMedia[0]->media_url;
                        $getArticleImage=url($getArticleImage);
                        $getArticleImage=str_replace('index.php/', '', $getArticleImage);

                        $articleMediaURL=$getArticleImage;
                        $imageWidth=$articleMedia[0]->width;
                        $imageHeight=$articleMedia[0]->height;
                    }
                    else
                    {
                        $articleMediaURL=$articleMedia[0]->media_url;
                    }
                }
                $userArticleLike=ArticleLikes::where([['article_id','=',$article->article_id],['like_by','=',$userId]])->get();
                $isArticleLikeSelected=0;
                if(count($userArticleLike)!=0)
                {
                    $isArticleLikeSelected=1;
                }
                $bookmarkData=Bookmark::where([['article_id','=',$article->article_id],['bookmark_by','=',$userId]])->get();
                $isBookmarked=0;
                if(count($bookmarkData)!=0)
                {
                    $isBookmarked=1;
                }
                $articleUserList[]=array(
                    'type'=>$article->type,
                    'isAnonymous'=>$article->is_anonymous,
                    'forumId' => $article->forum_id,
                    'articleId' => $article->article_id,
                    'articleUserId' =>$article->user_id,
                    'postedBy' => $article->name,
                    'userImage' => $getUserImage,
                    'postedDate' => $article->created_at,
                    'articleTitle' => $article->article_title,
                    'articleMediaId' => $articleMediaId,
                    'articleMediaType' => $articleMediaType,
                    'articleMediaURL' => $articleMediaURL,
                    'imageWidth' => $imageWidth,
                    'imageHeight' => $imageHeight,
                    'noOfLikes' => $article->likes,
                    'isLikeSelected' => $isArticleLikeSelected,
                    'noOfComments' => $commentCount,
                    'isBookmarked' => $isBookmarked
                );
            }

            $data['code']=1;
            $data['message']=null;
            $data['data']['current_page']=$articleList->currentPage();
            $data['data']['per_page']=$articleList->perPage();
            $data['data']['total']=$articleList->total();
            $data['data']['next_page_url']=$articleList->nextPageUrl();
            $data['data']['prev_page_url']=$articleList->previousPageUrl();
            $data['data']['articleList']=$articleUserList;
        }

        return response()->json($data);
    }
    /* my article */
    public function articleList(Request $request)
    {
        $userId = $this->userId;
        $forumId = 0;
        $articleList= Article::where([['article.forum_id','=',$forumId],['article.created_by','=',$userId],['article.is_active','=',1]])
                ->leftJoin('users','article.created_by','=','users.id')
                ->select('users.id as user_id','users.name','users.user_profile_images_url','article.id as article_id','article.forum_id','article.type','article.is_anonymous','article.article_title','article.created_at','article.likes')
                ->orderBy('article.created_at', 'DESC')
                ->paginate(10);
        if(count($articleList)==0)
        {
            $data['code']=1;
            $data['message']='No Post Found';
            $data['data']['articleList']=[];
        }
        else
        {
            $articleUserList = array();
            foreach ($articleList as $article)
            {
                $articleCommentCount=Comment::where([['article_id','=',$article->article_id],['is_deleted','=',0]])->count();
                $commentReplycount=CommentReply::where([['article_id','=',$article->article_id],['is_deleted','=',0]])->count();
                $commentCount=($articleCommentCount+$commentReplycount);
                $getUserImage=null;
                if (!(empty($article->user_profile_images_url)))
                {
                    $getUserImage=$article->user_profile_images_url;
                    $getUserImage=url($getUserImage);
                    $getUserImage=str_replace('index.php/', '', $getUserImage);
                }else
                {
                    $getUserImage='upload/images/appicon.png';
                    $getUserImage=url($getUserImage);
                    $getUserImage=str_replace('index.php/', '', $getUserImage);
                }

                $articleMediaId=null;
                $articleMediaType=null;
                $articleMediaURL=null;
                $imageWidth=null;
                $imageHeight=null;

                $articleMedia=ArticleMedia::where([['article_id','=',$article->article_id]])->get();
                if(count($articleMedia)!=0)
                {
                     $articleMediaType=$articleMedia[0]->media_type;
                     $articleMediaId=$articleMedia[0]->id;

                    if($articleMedia[0]->media_type==1)
                    {

                        $getArticleImage=$articleMedia[0]->media_url;
                        $getArticleImage=url($getArticleImage);
                        $getArticleImage=str_replace('index.php/', '', $getArticleImage);

                        $articleMediaURL=$getArticleImage;
                        $imageWidth=$articleMedia[0]->width;
                        $imageHeight=$articleMedia[0]->height;
                    }
                    else
                    {
                        $articleMediaURL=$articleMedia[0]->media_url;
                    }
                }
                $userArticleLike=ArticleLikes::where([['article_id','=',$article->article_id],['like_by','=',$userId]])->get();
                $isArticleLikeSelected=0;
                if(count($userArticleLike)!=0)
                {
                    $isArticleLikeSelected=1;
                }
                $bookmarkData=Bookmark::where([['article_id','=',$article->article_id],['bookmark_by','=',$userId]])->get();
                $isBookmarked=0;
                if(count($bookmarkData)!=0)
                {
                    $isBookmarked=1;
                }
                $articleUserList[]=array(
                    'type'=>$article->type,
                    'isAnonymous'=>$article->is_anonymous,
                    'forumId' => $article->forum_id,
                    'articleId' => $article->article_id,
                    'articleUserId' =>$article->user_id,
                    'postedBy' => $article->name,
                    'userImage' => $getUserImage,
                    'postedDate' => $article->created_at,
                    'articleTitle' => $article->article_title,
                    'articleMediaId' => $articleMediaId,
                    'articleMediaType' => $articleMediaType,
                    'articleMediaURL' => $articleMediaURL,
                    'imageWidth' => $imageWidth,
                    'imageHeight' => $imageHeight,
                    'noOfLikes' => $article->likes,
                    'isLikeSelected' => $isArticleLikeSelected,
                    'noOfComments' => $commentCount,
                    'isBookmarked' => $isBookmarked
                );
            }

            // $data['data']['first_page_url']=$articleList->onFirstPage();
            // $data['data']['from']=$articleList->currentPage();
            // $data['data']['last_page']=$articleList->lastPage();
            // $data['data']['last_page_url']=$articleList->lastPage();
            // $data['data']['path']=$articleList->url();
            // $data['data']['to']=$articleList->currentPage();
            // $data['data']['dummy']=$articleList;

            $data['code']=1;
            $data['message']=null;
            $data['data']['current_page']=$articleList->currentPage();
            $data['data']['per_page']=$articleList->perPage();
            $data['data']['total']=$articleList->total();
            $data['data']['next_page_url']=$articleList->nextPageUrl();
            $data['data']['prev_page_url']=$articleList->previousPageUrl();
            $data['data']['articleList']=$articleUserList;
        }

        return response()->json($data);
    }

    public function articleDiscussionSearch(Request $request,$forumId)
    {
        $header = $request->header('Authorization');
        $userId = $request->header('userId');
        $validator = Validator::make($request->all(), [
            'articleTitle' => 'required'
        ]);
        if ($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            if($forumId==0)
            {
                $articleList= Article::where([['article.forum_id','=',$forumId],['article.is_active','=',1],['article.created_from','=',2],['article.article_title', 'like', '%'.$request->articleTitle.'%']])
                ->leftJoin('admin','article.created_by','=','admin.id')
                ->select('admin.id as user_id','admin.name','admin.user_profile_images_url','article.id as article_id','article.forum_id','article.type','article.is_anonymous','article.article_title','article.created_at','article.likes')
                ->orderBy('article.created_at', 'DESC')
                ->paginate(10);
            }
            else
            {
                $articleList= Article::where([['article.forum_id','=',$forumId],['article.is_active','=',1],['article.created_from','=',1],['article.article_title', 'like', '%'.$request->articleTitle.'%']])
                    ->leftJoin('users','article.created_by','=','users.id')
                    ->select('users.id as user_id','users.name','users.user_profile_images_url','article.id as article_id','article.forum_id','article.type','article.is_anonymous','article.article_title','article.created_at','article.likes')
                    ->orderBy('article.created_at', 'DESC')
                    ->paginate(10);
            }
            if(count($articleList)==0)
            {
                $data['code']=1;
                $data['message']='No Post Found';
                $data['data']['articleList']=[];
            }
            else
            {
                $articleUserList = array();
                foreach ($articleList as $article)
                {

                    $articleCommentCount=Comment::where([['article_id','=',$article->article_id],['is_deleted','=',0]])->count();
                    $commentReplycount=CommentReply::where([['article_id','=',$article->article_id],['is_deleted','=',0]])->count();
                    $commentCount=($articleCommentCount+$commentReplycount);


                    $getUserImage=null;
                    if (!(empty($article->user_profile_images_url)))
                    {
                        $getUserImage=$article->user_profile_images_url;
                        $getUserImage=url($getUserImage);
                        $getUserImage=str_replace('index.php/', '', $getUserImage);
                    }else
                    {
                        $getUserImage='upload/images/appicon.png';
                        $getUserImage=url($getUserImage);
                        $getUserImage=str_replace('index.php/', '', $getUserImage);
                    }

                    $articleMediaId=null;
                    $articleMediaType=null;
                    $articleMediaURL=null;
                    $imageWidth=null;
                    $imageHeight=null;

                    $articleMedia=ArticleMedia::where([['article_id','=',$article->article_id]])->get();
                    if(count($articleMedia)!=0)
                    {
                        $articleMediaType=$articleMedia[0]->media_type;
                        $articleMediaId=$articleMedia[0]->id;

                        if($articleMedia[0]->media_type==1)
                        {

                            $getArticleImage=$articleMedia[0]->media_url;
                            $getArticleImage=url($getArticleImage);
                            $getArticleImage=str_replace('index.php/', '', $getArticleImage);

                            $articleMediaURL=$getArticleImage;
                            $imageWidth=$articleMedia[0]->width;
                            $imageHeight=$articleMedia[0]->height;
                        }
                        else
                        {
                            $articleMediaURL=$articleMedia[0]->media_url;
                        }
                    }




                    $userArticleLike=ArticleLikes::where([['article_id','=',$article->article_id],['like_by','=',$userId]])->get();
                    $isArticleLikeSelected=0;
                    if(count($userArticleLike)!=0)
                    {
                        $isArticleLikeSelected=1;
                    }


                    $bookmarkData=Bookmark::where([['article_id','=',$article->article_id],['bookmark_by','=',$userId]])->get();
                    $isBookmarked=0;
                    if(count($bookmarkData)!=0)
                    {
                        $isBookmarked=1;
                    }

                    $articleUserList[]=array(
                        'type'=>$article->type,
                        'isAnonymous'=>$article->is_anonymous,
                        'forumId' => $article->forum_id,
                        'articleId' => $article->article_id,
                        'articleUserId' =>$article->user_id,
                        'postedBy' => $article->name,
                        'userImage' => $getUserImage,
                        'postedDate' => $article->created_at,
                        'articleTitle' => $article->article_title,
                        'articleMediaId' => $articleMediaId,
                        'articleMediaType' => $articleMediaType,
                        'articleMediaURL' => $articleMediaURL,
                        'imageWidth' => $imageWidth,
                        'imageHeight' => $imageHeight,
                        'noOfLikes' => $article->likes,
                        'isLikeSelected' => $isArticleLikeSelected,
                        'noOfComments' => $commentCount,
                        'isBookmarked' => $isBookmarked
                        );
                }
                $data['code']=1;
                $data['message']=null;
                $data['data']['current_page']=$articleList->currentPage();
                $data['data']['per_page']=$articleList->perPage();
                $data['data']['total']=$articleList->total();
                $data['data']['next_page_url']=$articleList->nextPageUrl();
                $data['data']['prev_page_url']=$articleList->previousPageUrl();
                $data['data']['articleList']=$articleUserList;
            }

        }
        return response()->json($data);
    }

    public function articleDetail(Request $request,$articleId,$forumId=0)
    {
        $userId = $this->userId;
        $articleDetail= Article::where([['id','=',$articleId],['forum_id','=',$forumId],['is_active','=',1]])->get();
        $userArticlePostDetail=User::select('id','name','user_profile_images_url')->where([['id','=',$articleDetail[0]->created_by]])->get();
        if(count($articleDetail)==0)
        {
            $data['code']=1;
            $data['message']='No Post Found';
            $data['data']=null;
        }
        else
        {
            $updateArticle= Article::find($articleId);
            $updateArticle->no_of_views = ($articleDetail[0]->no_of_views + 1);
            $updateArticle->save();
            $articleMedia=ArticleMedia::where([['article_id','=',$articleId]])->get();
            $userArticleLike=ArticleLikes::where([['article_id','=',$articleId],['like_by','=',$userId]])->get();
            $isArticleLikeSelected=0;
            if(count($userArticleLike)!=0)
            {
                $isArticleLikeSelected=1;
            }
            $userArticleReport=ArticleReport::where([['article_id','=',$articleId],['reported_by','=',$userId]])->get();
            $isArticleReportSelected=0;
            if(count($userArticleReport)!=0)
            {
                $isArticleReportSelected=1;
            }
            $articleCommentCount=Comment::where([['article_id','=',$articleId],['is_deleted','=',0]])->count();
            $commentReplycount=CommentReply::where([['article_id','=',$articleId],['is_deleted','=',0]])->count();
            $commentCount=($articleCommentCount+$commentReplycount);
            $getUserImage=null;
            if (!(empty($userArticlePostDetail[0]->user_profile_images_url)))
            {
                $getUserImage=$userArticlePostDetail[0]->user_profile_images_url;
                $getUserImage=url($getUserImage);
                $getUserImage=str_replace('index.php/', '', $getUserImage);
            }else
            {
                $getUserImage='upload/images/appicon.png';
                $getUserImage=url($getUserImage);
                $getUserImage=str_replace('index.php/', '', $getUserImage);
            }
            $bookmarkData=Bookmark::where([['article_id','=',$articleId],['bookmark_by','=',$userId]])->get();
            $isBookmarked=0;
            if(count($bookmarkData)!=0)
            {
                $isBookmarked=1;
            }
            $article = array();
            $comments=array();
            $article['articleUserId'] = $userArticlePostDetail[0]->id;
            $article['postedBy']=$userArticlePostDetail[0]->name;
            $article['userImage']=$getUserImage;
            $article['postedDate']=$articleDetail[0]->created_at;
            $article['type']=$articleDetail[0]->type;
            $article['isAnonymous']=$articleDetail[0]->is_anonymous;
            $article['articleId']=(int) $articleId;
            $article['articleTitle']=$articleDetail[0]->article_title;
            $article['forumId']=$articleDetail[0]->forum_id;
            $article['articleDescription']=$articleDetail[0]->article_description;
            $articleMediaId=null;
            $articleMediaType=null;
            $articleMediaURL=null;
            $imageWidth=null;
            $imageHeight=null;
            if(count($articleMedia)!=0)
            {
                $articleMediaId=$articleMedia[0]->id;
                $articleMediaType=$articleMedia[0]->media_type;
                if($articleMedia[0]->media_type==1)
                {

                    $getArticleImage=$articleMedia[0]->media_url;
                    $getArticleImage=url($getArticleImage);
                    $getArticleImage=str_replace('index.php/', '', $getArticleImage);
                    $articleMediaURL=$getArticleImage;
                    $imageWidth=$articleMedia[0]->width;
                    $imageHeight=$articleMedia[0]->height;
                }
                else
                {
                    $articleMediaURL=$articleMedia[0]->media_url;
                }
            }
            $article['articleMediaId']=$articleMediaId;
            $article['articleMediaType']=$articleMediaType;
            $article['articleMediaURL']=$articleMediaURL;
            $article['imageWidth']=$imageWidth;
            $article['imageHeight']=$imageHeight;
            $article['noOfLikes']=$articleDetail[0]->likes;
            $article['noOfComments']=$commentCount;
            $article['isBookmarked']=$isBookmarked;
            $article['isLikeSelected']=$isArticleLikeSelected;
            $article['isReportSelected']=$isArticleReportSelected;
            $commentsList = Article::find($articleId)->comments()->where('is_deleted','=',0)->orderBy('comment_date', 'DESC')->get();
            foreach ($commentsList as $comment)
            {
                $replies=array();
                $userDetail=User::select('id','name','user_profile_images_url')->where([['id','=',$comment->comment_by]])->get();
                $userCommentLike=CommentLikes::where([['comment_id','=',$comment->id],['article_id','=',$articleId],['like_by','=',$userId]])->get();
                $isCommentLikeSelected=0;
                if(count($userCommentLike)!=0)
                {
                    $isCommentLikeSelected=1;
                }
                $userCommentReport=ArticleCommentReport::where([['comment_id','=',$comment->id],['article_id','=',$articleId],['reported_by','=',$userId]])->get();
                $isCommentReportSelected=0;
                if(count($userCommentReport)!=0)
                {
                    $isCommentReportSelected=1;
                }
                $getCommentUserImage=null;
                if (!(empty($userDetail[0]->user_profile_images_url)))
                {
                    $getCommentUserImage=$userDetail[0]->user_profile_images_url;
                    $getCommentUserImage=url($getCommentUserImage);
                    $getCommentUserImage=str_replace('index.php/', '', $getCommentUserImage);
                }
                $commentsReplyList = Comment::find($comment->id)->commentsReplies()->where('is_deleted','=',0)->orderBy('comment_date', 'ASC')->get();
                foreach ($commentsReplyList as $commentReply) {

                    $userReplyDetail=User::select('id','name','user_profile_images_url')->where([['id','=',$commentReply->comment_by]])->get();

                    $userReplyLike=ReplyLikes::where([['reply_id','=',$commentReply->id],['comment_id','=',$comment->id],['article_id','=',$articleId],['like_by','=',$userId]])->get();
                    $isReplyLikeSelected=0;
                    if(count($userReplyLike)!=0)
                    {
                        $isReplyLikeSelected=1;
                    }

                    $userReplyReport=ArticleCommentReplyReport::where([['reply_id','=',$commentReply->id],['comment_id','=',$comment->id],['article_id','=',$articleId],['reported_by','=',$userId]])->get();
                    $isReplyReportSelected=0;
                    if(count($userReplyReport)!=0)
                    {
                        $isReplyReportSelected=1;
                    }

                    $getCommentReplyUserImage=null;
                    if (!(empty($userReplyDetail[0]->user_profile_images_url)))
                    {
                        $getCommentReplyUserImage=$userReplyDetail[0]->user_profile_images_url;
                        $getCommentReplyUserImage=url($getCommentReplyUserImage);
                        $getCommentReplyUserImage=str_replace('index.php/', '', $getCommentReplyUserImage);
                    }

                    $replies[]=array(
                        'replyUserId'=>$userReplyDetail[0]->id,
                        'replyUserName'=>$userReplyDetail[0]->name,
                        'replyUserImage'=>$getCommentReplyUserImage,
                        'replyId'=>$commentReply->id,
                        'parentCommentId'=>$commentReply->comment_id,
                        'userReply'=>$commentReply->comment,
                        'replyNoOfLikes'=>$commentReply->likes,
                        'isLikeSelected'=>$isReplyLikeSelected,
                        'isReportSelected'=>$isReplyReportSelected
                    );
                }
                $commentReplycount=CommentReply::where([['comment_id','=',$comment->id],['is_deleted','=',0]])->count();
                $comments[]=array(
                    'commentUserId'=>$userDetail[0]->id,
                    'commentUserName'=>$userDetail[0]->name,
                    'commentUserImage'=>$getCommentUserImage,
                    'parentCommentId'=>$comment->id,
                    'userComment'=>$comment->comment,
                    'noOfLikes'=>$comment->likes,
                    'noOfReply'=>$commentReplycount,
                    'isLikeSelected'=>$isCommentLikeSelected,
                    'isReportSelected'=>$isCommentReportSelected,
                    'replies'=>$replies
                );
            }
            $data['code']=1;
            $data['message']=null;
            $data['data']['article']=$article;
            $data['data']['comments']=$comments;
        }
        return response()->json($data);
    }

    public function articleEditDetail(Request $request,$forumId,$articleId)
    {
        $userId = $this->userId;
        $articleDetail= Article::where([['id','=',$articleId],['forum_id','=',$forumId],['created_by','=',$userId],['is_active','=',1]])->get();
        if(count($articleDetail)==0)
        {
            $data['code']=1;
            $data['message']='No Post Found';
            $data['data']=null;
        }
        else
        {
            $articleMedia=ArticleMedia::where([['article_id','=',$articleId]])->get();
            $article['type']=$articleDetail[0]->type;
            $article['isAnonymous']=$articleDetail[0]->is_anonymous;
            $article['forumId']=$articleDetail[0]->forum_id;
            $article['articleId']=$articleDetail[0]->id;
            $article['articleTitle']=$articleDetail[0]->article_title;
            $article['articleDescription']=$articleDetail[0]->article_description;
            $articleMediaId=null;
            $articleMediaType=null;
            $articleMediaURL=null;
            $imageWidth=null;
            $imageHeight=null;
            if(count($articleMedia)!=0)
            {
                $articleMediaId=$articleMedia[0]->id;
                $articleMediaType=$articleMedia[0]->media_type;
                if($articleMedia[0]->media_type==1)
                {
                    $getArticleImage=$articleMedia[0]->media_url;
                    $getArticleImage=url($getArticleImage);
                    $getArticleImage=str_replace('index.php/', '', $getArticleImage);
                    $articleMediaURL=$getArticleImage;
                    $imageWidth=$articleMedia[0]->width;
                    $imageHeight=$articleMedia[0]->height;
                }
                else
                {
                    $articleMediaURL=$articleMedia[0]->media_url;
                }
            }
            $article['articleMediaId']=$articleMediaId;
            $article['articleMediaType']=$articleMediaType;
            $article['articleMediaURL']=$articleMediaURL;
            $article['imageWidth']=$imageWidth;
            $article['imageHeight']=$imageHeight;

            $data['code']=1;
            $data['message']=null;
            $data['data']['article']=$article;
        }
        return response()->json($data);
    }

    public function articleUpdate(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'forumId' => 'required',
            'articleId' => 'required',
            'articleTitle' => 'required',
            'articleDescription' => 'required',
            'isMediaEdited' => 'required'
        ]);
        if ($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            if($request->isMediaEdited==1)
            {
                if($request->articleMediaType==1)
                {
                    if ($request->hasFile('media'))
                    {
                        $Imagedata= getimagesize($_FILES["media"]['tmp_name']);
                        $image = $request->file('media');
                        $name = time().'.'.$image->getClientOriginalExtension();
                        $destinationPath = public_path('/upload/images/article');
                        $image->move($destinationPath, $name);
                        $img_url = '/upload/images/article/'.$name;

                        $updateArticleMedia = ArticleMedia::find($request->articleMediaId);
                        $updateArticleMedia->media_type = $request->articleMediaType;
                        $updateArticleMedia->media_url = $img_url;
                        $updateArticleMedia->width = $Imagedata[0];
                        $updateArticleMedia->height = $Imagedata[1];
                        $updateArticleMedia->updated_at = date('Y-m-d H:i:s');
                        $updateArticleMedia->save();
                    }
                }
            }
            $updateArticle= Article::find($request->articleId);
            $updateArticle->is_anonymous = $request->isAnonymous;
            $updateArticle->article_title = $request->articleTitle;
            $updateArticle->article_description = $request->articleDescription;
            $updateArticle->updated_at = date('Y-m-d H:i:s');
            $updateArticle->save();
            $data['code']=1;
            $data['message']='Article Updated Successfully';
            $data['data']=null;
        }
        return response()->json($data);
    }

    public function articleDelete(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'articleId' => 'required'
        ]);
        if ($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            $updateArticle= Article::find($request->articleId);
            $updateArticle->is_active = 0;
            $updateArticle->date_fo_inactive = date('Y-m-d H:i:s');
            $updateArticle->updated_at = date('Y-m-d H:i:s');
            $updateArticle->save();
            DB::table('article_comments')
            ->where('article_id', $request->articleId)
            ->update(array('is_deleted' => 1,'comment_deleted_date'=>date('Y-m-d H:i:s'),'comment_deleted_by'=>$userId));
            DB::table('comment_reply')
            ->where('article_id', $request->articleId)
            ->update(array('is_deleted' => 1,'comment_deleted_date'=>date('Y-m-d H:i:s'),'comment_deleted_by'=>$userId));
            $data['code']=1;
            $data['message']='Article Deleted Successfully';
            $data['data']=null;
        }
        return response()->json($data);
    }

    public function likeArticle(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'articleId' => 'required'
        ]);
        if($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            $ArticleLikes = ArticleLikes::where('article_id',$request->articleId)->where('like_by',$userId)->first();
            if(!isset($ArticleLikes->id))
            {
                $createLikes = ArticleLikes::create([
                    'article_id' =>$request->articleId,
                    'like_by' =>$userId
                ]);
                Article::find($request->articleId)->increment('likes',1);
            }else{
                Article::find($request->articleId)->decrement('likes',1);
                ArticleLikes::where([['article_id', '=', $request->articleId],['like_by','=',$userId]])->delete();
            }
            $articleLikes= Article::select('id','likes')->where([['id','=',$request->articleId],['is_active','=',1]])->get();
            $data['code']=1;
            $data['message']=null;
            $data['data']=$articleLikes;
        }

        return response()->json($data);
    }

    public function reportArticle(Request $request)
    {
        $userId = $this->userId;
        $validator = Validator::make($request->all(), [
            'articleId' => 'required'
        ]);
        if ($validator->fails())
        {
            $data['code']=0;
            $data['message']='Some data is missing';
            $data['data']=null;
        }
        else
        {
            $report=Article::select('no_of_people_reported')->where('id','=',$request->articleId)->get();

            $updateReport = Article::find($request->articleId);
            $updateReport->is_reported = 1;
            $updateReport->no_of_people_reported = ($report[0]->no_of_people_reported+1);

            if($updateReport->save())
            {
                $createReport = ArticleReport::create([
                    'article_id' =>$request->articleId,
                    'reported_by' =>$userId
                    ]);

                $data['code']=1;
                $data['message']=null;
                $data['data']['articleId']=(int) $request->articleId;
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
