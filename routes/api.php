<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('login', [App\Http\Controllers\API\UserController::class, 'login']);
Route::post('register', [App\Http\Controllers\API\UserController::class, 'register']);
Route::post('forgot', [App\Http\Controllers\API\UserController::class, 'forgot']);
Route::post('reset', [App\Http\Controllers\API\UserController::class, 'reset']);

Route::middleware(['ApiUserCheck'])->group(function () {
    Route::post('profile',[App\Http\Controllers\API\ProfileController::class, 'index']);
    Route::post('profile/update',[App\Http\Controllers\API\ProfileController::class, 'update']);
    Route::post('user/followandunfollow',[App\Http\Controllers\API\ProfileController::class, 'followStatusChange']);
    Route::get('user/followerList',[App\Http\Controllers\API\ProfileController::class, 'followerList']);
    Route::get('user/followingList',[App\Http\Controllers\API\ProfileController::class, 'followingList']);
    Route::post('user/reportUser',[App\Http\Controllers\API\ProfileController::class, 'reportUser']);
    //article
    Route::post('user/article',[App\Http\Controllers\API\ArticleController::class, 'articleList']);
    Route::post('user/article/create',[App\Http\Controllers\API\ArticleController::class, 'createArticle']);
    Route::post('user/article/update',[App\Http\Controllers\API\ArticleController::class, 'articleUpdate']);
    Route::post('user/article/delete',[App\Http\Controllers\API\ArticleController::class, 'articleDelete']);
    Route::post('user/article/like',[App\Http\Controllers\API\ArticleController::class, 'likeArticle']);
    Route::post('user/article/report',[App\Http\Controllers\API\ArticleController::class, 'reportArticle']);
    Route::get('user/article/detail/{articleId}/{forumId?}',[App\Http\Controllers\API\ArticleController::class, 'articleDetail']);

    // Comment
    Route::post('user/article/comment',[App\Http\Controllers\API\CommentController::class, 'storeComment']);
    Route::post('user/article/comment/like',[App\Http\Controllers\API\CommentController::class, 'likeComment']);
    Route::post('user/article/comment/report',[App\Http\Controllers\API\CommentController::class, 'reportComment']);

    // Replies
    Route::post('user/article/comment/reply',[App\Http\Controllers\API\CommentReplyController::class, 'storeReplies']);
    Route::post('user/article/comment/reply/like',[App\Http\Controllers\API\CommentReplyController::class, 'likeReplies']);



});

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
