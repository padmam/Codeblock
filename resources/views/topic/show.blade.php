@extends('master')

@section('css')

@stop

@section('content')
	<h2>{{$topic->title}}</h2>
	@if(count($topic->replies) > 0)
		@foreach($topic->replies as $reply)
			<p class="font-bold">{{$topic->replies->last()->user->username}} - {{$topic->replies->last()->created_at->diffForHumans()}}</p>
			<p>{{$topic->replies->last()->reply}}</p>
			<div class="horizontalRule margin-top-half margin-bottom-half"></div>
		@endforeach
	@else
		<div class="alert info">This topic have no replies yet.</div>
	@endif
	{{ Form::model(null, array('action' => 'ReplyController@createOrUpdate')) }}
	<h3 class="margin-top-one">Reply</h3>
	{{ Form::hidden('topic_id', Route::Input('id')) }}
	{{ Form::label('reply', 'Reply:') }}
	{{ Form::textarea('reply', Input::old('reply'), array('placeholder' => 'Your reply', 'id' => 'reply', 'data-validator' => 'required|min:3')) }}
	{{ $errors->first('reply', '<div class="alert error">:message</div>') }}
	{{ Form::button('Create', array('type' => 'Reply')) }}
	{{ Form::close() }}
@stop

@section('script')

@stop                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       