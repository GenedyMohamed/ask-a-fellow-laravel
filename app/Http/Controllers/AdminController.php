<?php

namespace App\Http\Controllers;

use App\Answer;
use App\AnswerReport;
use App\Feedback;
use App\Question;
use App\QuestionReport;
use App\Note;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Filesystem\Filesystem; 
use Response; 
use File; 
use App\Http\Requests;
use App\Major;
use App\Course;
use App\User;
use App\Event;
use App\AdminMail;
use Mail;
use Session;
use Auth;
use Illuminate\Support\Facades\Redirect;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function index()
    {
        return view('admin.list');
    }

    public function add_course_page()
    {
        $courses = Course::all();
        $majors = Major::all();

        return view('admin.add_course', compact(['courses', 'majors']));
    }


    public function add_course(Request $request)
    {
//        dd($request);
        $this->validate($request, [
            'course_code' => 'alpha_num|required',
            'course_name' => 'required',
            'semester' => 'numeric|between:1,10|required',
            'majors.*' => 'numeric|exists:majors,id'
        ]);

        $course = new Course();
        $course->course_code = $request->course_code;
        $course->course_name = $request->course_name;
        $course->semester = $request->semester;
        $course->save();
        $course->majors()->attach($request->majors);
        return redirect('admin/add_course');
    }

    public function delete_course($id)
    {
        $course = Course::find($id);
        $course->delete();
        return redirect('/admin/add_course');
    }

    public function update_course_page($id)
    {

        $course = Course::find($id);
        $majors = Major::all();

        $course_majors = array();
        foreach ($course->majors()->get() as $major)
            $course_majors[] = $major->id;

        return view('admin.update_course', compact(['course', 'majors', 'course_majors']));

    }

    public function update_course($id, Request $request)
    {
        $this->validate($request, [
            'course_code' => 'alpha_num|required',
            'course_name' => 'required',
            'semester' => 'numeric|between:1,10|required',
            'majors.*' => 'numeric|exists:majors,id'
        ]);

        $course = Course::find($id);
        $course->course_code = $request->course_code;
        $course->course_name = $request->course_name;
        $course->semester = $request->semester;
        $course->save();
        $course->majors()->detach();
        $course->majors()->attach($request->majors);
        return redirect('admin/add_course');
    }


    public function add_major_page()
    {
        $majors = Major::all();
        return view('admin.add_major', compact(['courses', 'majors']));
    }

    public function add_major(Request $request)
    {
        $this->validate($request, [
            'faculty' => 'required',
            'major' => 'required',
        ]);
        $major = new Major();
        $major->faculty = $request->faculty;
        $major->major = $request->major;
        $major->save();
        return redirect('/admin/add_major');
    }

    public function delete_major($id)
    {
        $major = Major::find($id);
        $major->delete();
        return redirect('/admin/add_major');
    }

    public function update_major_page($id)
    {
        $major = Major::find($id);
        return view('admin.update_major', compact(['major']));
    }

    public function update_major($id, Request $request)
    {
        $this->validate($request, [
            'faculty' => 'required',
            'major' => 'required',
        ]);

        $major = Major::find($id);
        $major->faculty = $request->faculty;
        $major->major = $request->major;
        $major->save();
        return redirect('admin/add_major');
    }


    public function view_feedbacks()
    {
        $feedbacks = Feedback::all();
        return view('admin.feedbacks', compact(['feedbacks']));
    }

    public function view_reports()
    {
        $question_reports = QuestionReport::all();
        $answer_reports = AnswerReport::all();
        return view('admin.reports', compact(['question_reports', 'answer_reports']));
    }


    public function manyMailView()
    {
        $users = User::where('confirmed', '>=', '1')->get();
        return view('admin.mail_many', compact(['users']));
    }

    public function oneMailView($id)
    {
        $user = User::find($id);
        return view('admin.mail_one', compact(['user']));
    }

    public function processMailToUsers(Request $request, $type)
    {


        if ($type == 0) {
            $sendMail = $this->sendMailToOneUser($request->user_id, $request->mail_subject, $request->mail_content);
            if ($sendMail) {
                Session::flash('mail', 'Mail sent successfully');
                return redirect(url('user/' . $request->user_id));
            } else {
                Session::flash('mail', 'Error sending mail');
                return redirect(url('admin/mail/one/' . $request->user_id));
            }
        } else {
            $sendMail = $this->sendMailToManyUsers($request->users, $request->mail_subject, $request->mail_content);
            if ($sendMail) {
                Session::flash('mail', 'Mail sent successfully');
                return redirect(url('admin/'));
            } else {
                Session::flash('mail', 'Error sending mail');
                return redirect(url('admin/mail/many/'));
            }
        }


    }


    public function sendMailToOneUser($user_id, $mail_subject, $mail_content)
    {
        $user = User::find($user_id);

        $sendMail = Mail::send('admin.emails.general', ['mail_content' => $mail_content, 'name' => $user->first_name], function ($message) use ($user, $mail_subject, $mail_content) {
            $message->to($user->email, $user->first_name)
                ->subject($mail_subject);
        });
        if ($sendMail) {
            $this->saveMail([$user_id], $mail_subject, $mail_content);
        }

        return $sendMail;

    }


    public function sendMailToManyUsers($users, $mail_subject, $mail_content)
    {

        $usersEmails = [];
        foreach ($users as $user) {
            $usersEmails[] = User::find($user)->email;
        }


        $sendMail = Mail::send('admin.emails.general', ['mail_content' => $mail_content, 'name' => 'awesome AskaFellow member'], function ($message) use ($usersEmails, $mail_subject, $mail_content) {
            $message->to([])->bcc($usersEmails)
                ->subject($mail_subject);
        });

        if ($sendMail) {
            $this->saveMail($users, $mail_subject, $mail_content);
        }

        return $sendMail;


    }


    public function saveMail($recipients, $mail_subject, $mail_body)
    {
        $mail = new AdminMail();
        $mail->user_id = Auth::user()->id;
        $mail->subject = $mail_subject;
        $mail->body = $mail_body;
        $mail->save();
        $mail->recipients()->attach($recipients);
    }


    public function showMailLog()
    {
        $mails = AdminMail::orderBy('created_at', 'desc')->get();
        return view('admin.mail_log', compact(['mails']));
    }


    public function listUsers()
    {
        $users = User::orderBy('first_name', 'asc');
        return view('admin.users', compact(['users']));
    }

    public function add_badge()
    {

        $users = User::orderBy('first_name', 'asc');
        return view('admin.badge', compact(['users']));
    }

    public function save_badge($id)
    {
        $user = User::findOrFail($id);
        $user->verified_badge = 1;
        $user->save();
        $users = User::orderBy('first_name', 'asc');
        return view('admin.badge', compact(['users']));
    }

    public function remove_badge($id)
    {
        $user = User::findOrFail($id);
        $user->verified_badge = 0;
        $user->save();
        $users = User::orderBy('first_name', 'asc');
        return view('admin.badge', compact(['users']));
    }

    public function statistics()
    {
        $questions = Question::all()->count();
        $answers = Answer::all()->count();
        $users = User::all()->count();
        return view('admin.statistics', compact(['questions', 'answers', 'users']));
    }
    //function to view all event requests
    public function eventRequests()
    {
        $requests = Event::all()->where('verified',0);
        return view('admin.event_requests')->with('requests',$requests);
    }

    //to view information about the clicked event and its creator with the option to accept or delete it
    public function viewRequest($id)
    {
        $event = Event::find($id);
        $creator = User::find($event->creator_id);
        //return $event;
        return view('admin.event', compact(['event', 'creator']));
    }

    //function to reject event requests by searching and removing it from the database
    public function rejectRequest($id)
    {
        $event = Event::find($id);
        $event-> delete();
        return redirect('admin/event_requests');
    }

    //function to accept event requests by searching and setting its verified flag to true
    public function acceptRequest($id)
    {
        $event =  Event::Find($id);
        $event->verified= 1;
        $event->save();
        return redirect('admin/event_requests');
    }


    //function to get all node upload requests
    public function noteRequests() {
          $notes_upload = DB::table('notes')->where('notes.request_upload', '=', 1)
                  ->join('users', 'notes.user_id', '=', 'users.id')
                  ->join('courses', 'notes.course_id', '=', 'courses.id')
                  ->select('notes.*', 'users.first_name', 'users.last_name', 'courses.course_name', 'courses.course_code') 
                  ->get();
         $notes_delete = DB::table('notes')->where('notes.request_delete', '=', 1)
                  ->join('users', 'notes.user_id', '=', 'users.id')
                  ->join('courses', 'notes.course_id', '=', 'courses.id')
                  ->select('notes.*', 'users.first_name', 'users.last_name', 'courses.course_name', 'courses.course_code') 
                  ->get();

          return view('admin.upload_delete_requests', compact(['notes_upload', 'notes_delete']));
    }

    //approved the uplaod of a note by changing its request_upload status to 0
    public function approveNoteUpload($id) {
        $note = Note::find($id);
        $note->request_upload = 0;
        $note->save();
        return redirect('admin/note_requests');
    }

    //deletes note using its ID
    public function deleteNote($id) {
          $note = Note::find($id);
          File::delete($note->path);
          Note::destroy($id); 

        return redirect('admin/note_requests');
    }

    //opens the note file inline in the browser
    public function viewNote($id) {
       $note =  Note::find($id);  

    return Response::make(file_get_contents($note->path), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$note->title.'"'
    ]);
        
    }

    //Function to Delete the note as an admin
    public function deleteNoteAdmin($id) {
        if(Auth::user()){
        $role  = Auth::user()->role;
      if($role==1){
          $note = Note::find($id);
          $course_id = $note->course_id;
          $note->delete();
          return Redirect::back();
      }else{
            return Redirect::back();
      }

        }}

  
}
