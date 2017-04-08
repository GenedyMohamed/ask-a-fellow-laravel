<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    Route::get('/', function () {
        if (Auth::user())
            return redirect('/home');
        return view('welcome');
    });


    Route::get('/about', 'StaticController@about');
    Route::get('/howitworks', 'StaticController@howitworks');

    Route::get('/user/update', 'UserController@updateInfoPage');
    Route::post('/user/update', 'UserController@updateInfo');
    Route::get('/user/{id}', 'UserController@show');
    Route::get('/user/{id}/questions', 'UserController@show');
    Route::get('/user/{id}/answers', 'UserController@showProfileAnswers');


    Route::get('/admin', 'AdminController@index');
    Route::get('/admin/add_badge', 'AdminController@add_badge');
    Route::post('/admin/add_badge/{id}', 'AdminController@save_badge');
    Route::post('/admin/remove_badge/{id}', 'AdminController@remove_badge');
    Route::get('/admin/add_course', 'AdminController@add_course_page');
    Route::get('/admin/add_major', 'AdminController@add_major_page');
    Route::post('/admin/add_major', 'AdminController@add_major');
    Route::post('/admin/add_course', 'AdminController@add_course');
    Route::get('/admin/delete_course/{id}', 'AdminController@delete_course');
    Route::get('/admin/delete_major/{id}', 'AdminController@delete_major');
    Route::get('/admin/update_course/{id}', 'AdminController@update_course_page');
    Route::get('/admin/update_major/{id}', 'AdminController@update_major_page');
    Route::post('/admin/update_course/{id}', 'AdminController@update_course');
    Route::post('/admin/update_major/{id}', 'AdminController@update_major');
    Route::get('/admin/feedbacks', 'AdminController@view_feedbacks');
    Route::get('/admin/reports', 'AdminController@view_reports');
    Route::get('/admin/mail/many', 'AdminController@manyMailView');
    Route::get('/admin/mail/one/{id}', 'AdminController@oneMailView');
    Route::get('/admin/users', 'AdminController@listUsers');
    Route::get('/admin/mail/log', 'AdminController@showMailLog');
    Route::get('/admin/statistics','AdminController@statistics');
    Route::post('/mail/{type}', 'AdminController@processMailToUsers');


    Route::get('/browse', 'AppController@browse');
    Route::get('/list_courses/{major}/{semester}', 'AjaxController@getCourses');
    Route::get('/browse/{course_id}', 'AppController@list_questions');
    Route::post('/browse/{course_id}', 'AppController@post_question');
    Route::get('/browse/{major}/{semester}', 'AppController@list_questions_all');
    Route::post('/browse/{major}/{semester}', 'AppController@post_question_all');
    Route::get('/answers/{question_id}', 'AppController@inside_question');
    Route::post('/answers/{question_id}', 'AppController@post_answer');
    Route::get('/delete_answer/{id}', 'AppController@delete_answer');
    Route::get('/delete_question/{id}', 'AppController@delete_question');


    Route::get('/vote/answer/{answer_id}/{type}', 'AjaxController@vote_answer');
    Route::get('/vote/question/{answer_id}/{type}', 'AjaxController@vote_question');


    Route::get('/notifications_partial/', 'AjaxController@view_notifications_partial');
    Route::get('/notifications/', 'AppController@view_notifications');
    Route::get('/mark_notification/{notification_id}/{read}', 'AjaxController@mark_notification');
    Route::get('/subscriptions', 'AppController@subscription_page');
    Route::post('/subscriptions', 'AppController@subscribe_to_courses');


    Route::post('/feedback', 'AppController@send_feedback');
    Route::get('/report_question', 'AjaxController@send_report_question');
    Route::get('/report_answer', 'AjaxController@send_report_answer');
    Route::get('/verify/{token}', 'AuthController@verify');
    Route::post('/user/post_component', 'AppController@post_component');
    //
});

Route::group(['middleware' => 'web'], function () {
    Route::auth();
    Route::get('/register/verify/{token}', 'Auth\AuthController@verify');
    Route::get('/home', 'HomeController@index');
});

/*
|==========================================================================
| API Routes
|==========================================================================
|
| These routes are related to the API routes of this project
| The routes inside this prefix Matches The "/api/v1/your_route" URL
*/

Route::group(['prefix' => 'api/v1', 'middleware' => ['cors']], function () {

    /*
        |--------------------------
        | Question API Routes
        |--------------------------
    */

    /*
     * Question header viewing
     */
    Route::get('questions/{id}', 'API\QuestionAPIController@view_question_header');

    /*
     * Question viewing with answers and sorting.
     * */
    Route::get('answers/{id}/{order}', 'API\QuestionAPIController@view_answers');

    /**
     * Users Authentication
     */
    Route::post('register', 'API\AuthAPIController@register');
    Route::get('register/verify/{token}', 'API\AuthAPIController@verify');
    Route::post('login', 'API\AuthAPIController@login');
    Route::post('logout', 'API\AuthAPIController@logout');


    /**
     *  Users Profile
     */

    Route::get('user/{id}', 'API\UserAPIController@getUser');
    /*
     * browse majors and semesters API
     */
    Route::get('browse', 'ApiController@browse');
    /*
     * browse courses API
     */
    Route::get('/list_courses/{major}/{semester}', 'ApiController@getCourses');
    /*
     * Browse Questions of a course API
     */
    Route::get('/browse/{course_id}', 'ApiController@list_questions');
    /*
     *  Vote a question
     */
    Route::get('/vote/question/{answer_id}/{type}', 'ApiController@vote_question');
    /*
     *  Post a question
     */
    Route::post('/browse/{course_id}', 'ApiController@post_question');
    /*
     *  Post an answer
     */
    Route::post('/answers/{question_id}', 'ApiController@post_answer');
    /*
     * Home page data
     */
    Route::get('/home','ApiController@home');

});


