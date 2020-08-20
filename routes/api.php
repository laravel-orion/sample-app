<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['as' => 'api.'], function() {
    Orion::resource('posts', 'API\PostsController')->withSoftDeletes();
    Orion::belongsToResource('posts', 'user', 'API\PostUserController');
    Orion::hasOneResource('posts', 'meta', 'API\PostPostMetaController');
    Orion::morphOneResource('posts', 'image', 'API\PostImageController');
    Orion::morphManyResource('posts', 'comments', 'API\PostCommentsController');
    Orion::morphToManyResource('posts', 'tags', 'API\PostTagsController');
    Orion::morphToManyResource('tags', 'posts', 'API\TagPostsController');

    Orion::belongsToResource('post_metas', 'post', 'API\PostMetaPostController');

    Orion::resource('roles', 'API\RolesController');
    Orion::belongsToManyResource('users', 'roles', 'API\UserRolesController');
    Orion::hasManyResource('users', 'posts', 'API\UserPostsController'); //TODO: add registrar for relation resources
    Orion::hasManyThroughResource('teams', 'posts', 'API\TeamPostsController');
});

