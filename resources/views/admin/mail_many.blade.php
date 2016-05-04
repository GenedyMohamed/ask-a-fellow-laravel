@extends('layouts.app')

@section('content')
<div class="container" style="width: 90%; padding-left: 50px">
    <form method="POST" action="{{url('/mail/1')}}">
        {{csrf_field()}}
        <div class="form-group">
            <label for="mail_subject">Mail Subject</label>
            <input type="text" class="form-control" id="mail_subject" name="mail_subject" placeholder="Subject">
        </div>
        <h4 class="mail_example">Hello awesome Ask a Fellow member, </h4>
        <div class="form-group">
            <label for="mail_content">Mail Body</label>
            <textarea class="form-control" id="mail_content" name="mail_content" placeholder="Mail Body"></textarea>
        </div>
        <h4 class="mail_example">Regards,</h4>
        <h4 class="mail_example">TechHub Development Team</h4>
        <div class="form-group">
            <label for="majors">Receipents</label>
            <br>
            @foreach($users as $user)
                <input type="checkbox" name="users[]" value="{{$user->id}}"> {{$user->first_name.' '.$user->last_name}} <br>
            @endforeach
        </div>

        <button type="submit" class="btn btn-default">Send Mail</button>

        <div class="error" style="color:red">
            @include('errors')
        </div>

    </form>
</div>

    <style>
        .mail_example {
            font-style: italic;
            color:#0000ff;
        }
    </style>

@endsection