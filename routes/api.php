<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ProjectController;


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

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
  Route::post('register', [AuthController::class, 'register']);
  Route::post('forget', [AuthController::class, 'forget']);
  Route::post('login', [AuthController::class, 'login']);
  Route::post('logout', [AuthController::class, 'logout']);
  Route::post('refresh', [AuthController::class, 'refresh']);
  Route::post('setting/email', [AuthController::class, 'settingEmail']);
  Route::post('setting/password', [AuthController::class, 'settingPassword']);
});

Route::group(['middleware' => 'api', 'prefix' => 'profile'], function ($router) {
  Route::get('me', [ProfileController::class, 'me']);
  Route::post('me/edit', [ProfileController::class, 'editProfile']);
  Route::post('me/photo/edit', [ProfileController::class, 'editPhoto']);
  Route::post('me/resume/edit', [ProfileController::class, 'editResume']);
  Route::post('about/edit', [ProfileController::class, 'editAbout']);
  Route::get('experience/{take}/{userId}', [ProfileController::class, 'experience']);
  Route::get('certification/{take}/{userId}', [ProfileController::class, 'certification']);
  Route::get('skill/{take}/{userId}', [ProfileController::class, 'skill']);
  Route::get('education/{take}/{userId}', [ProfileController::class, 'education']);
  Route::get('experience/detail/{id}', [ProfileController::class, 'detailExperience']);
  Route::get('certification/detail/{id}', [ProfileController::class, 'detailCertification']);
  Route::get('skill/detail/{id}', [ProfileController::class, 'detailSkill']);
  Route::get('education/detail/{id}', [ProfileController::class, 'detailEducation']);
  Route::post('experience/add', [ProfileController::class, 'addExperience']);
  Route::post('certification/add', [ProfileController::class, 'addCertification']);
  Route::post('skill/add', [ProfileController::class, 'addSkill']);
  Route::post('education/add', [ProfileController::class, 'addEducation']);
  Route::put('experience/edit/{id}', [ProfileController::class, 'editExperience']);
  Route::put('certification/edit/{id}', [ProfileController::class, 'editCertification']);
  Route::put('skill/edit/{id}', [ProfileController::class, 'editSkill']);
  Route::put('education/edit/{id}', [ProfileController::class, 'editEducation']);
  Route::delete('experience/delete/{id}', [ProfileController::class, 'deleteExperience']);
  Route::delete('certification/delete/{id}', [ProfileController::class, 'deleteCertification']);
  Route::delete('skill/delete/{id}', [ProfileController::class, 'deleteSkill']);
  Route::delete('education/delete/{id}', [ProfileController::class, 'deleteEducation']);
});    

Route::group(['middleware' => 'api', 'prefix' => 'project'], function ($router) {
  Route::get('/me', [ProjectController::class, 'meProject']);
  Route::get('/search/{search}', [ProjectController::class, 'searchProject']);
  Route::get('/detail/{id}', [ProjectController::class, 'detailProject']);
  Route::get('/bookmark', [ProjectController::class, 'bookmarkProject']);
  Route::post('/bookmark/{id}', [ProjectController::class, 'addBookmark']);
  Route::get('/userjoin/{projectId}', [ProjectController::class, 'userJoin']);
  Route::get('/user/{userId}/{projectId}', [ProjectController::class, 'detailUser']);
  Route::post('/userjoin/review/{projectId}/{userId}/{review}', [ProjectController::class, 'reviewUserJoin']);
  Route::get('/join', [ProjectController::class, 'joinedProject']);
  Route::post('/join/{id}', [ProjectController::class, 'joinproject']);
  Route::post('add', [ProjectController::class, 'addProject']);
  Route::post('/review/{projectId}/{review}', [ProjectController::class, 'reviewProject']);
  Route::get('/{active}/{take}', [ProjectController::class, 'project']);
}); 

Route::middleware(['jwt.verify'])->group(function () {
  Route::get('/user', [UserController::class, 'read']);
  Route::post('/user/create', [UserController::class, 'create']);
  Route::post('/user/update/{id}', [UserController::class, 'update']);
  Route::get('/user/delete/{id}', [UserController::class, 'delete']);
  Route::get('/user/search', [UserController::class, 'search']);
  Route::get('/user/paginate', [UserController::class, 'paginate']);

  Route::post('/account/update', [AccountController::class, 'update']);
  Route::put('/account/change_password', [AccountController::class, 'password_change']);
});





// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
