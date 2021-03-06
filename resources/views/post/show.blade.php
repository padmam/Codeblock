@extends('master')

@section('css')

@stop

@section('content')
	<div class="small-wrapper">
		<h2>
			{{ $post->name }}
			<span class="float-right">
			@if(count($post->revisionHistory) > 0)
				<a href="" class="toogleModal font-bold"><i class="fa fa-clock-o"></i></a>
			@endif
			@if(Auth::check())
					@if(Auth::user()->id != $post->user_id)
						{{HTML::actionlink($url = array('action' => 'PostController@fork', 'params' => array($post->id)), '<i class="fa fa-code-fork"></i>')}}
						{{HTML::actionlink($url = array('action' => 'PostController@forked', 'params' => array($post->id)), $post->forked)}}
					@else
						{{HTML::actionlink($url = array('action' => 'PostController@edit', 'params' => array($post->id)), '<i class="fa fa-pencil"></i>')}}
						{{HTML::actionlink($url = array('action' => 'PostController@delete', 'params' => array($post->id)), '<i class="fa fa-trash-o"></i>', array('class' => 'confirm'))}}
					@endif
				@endif
				@if(!Auth::check())
					<i class="fa fa-star"></i>
				@elseif(Auth::user()->id == $post->user_id)
					<i class="fa fa-star"></i>
				@elseif($post->StaredByUser(Auth::user()->id))
					{{HTML::actionlink($url = array('action' => 'PostController@star', 'params' => array($post->id)), '<i class="fa fa-star"></i>')}}
				@else
					{{HTML::actionlink($url = array('action' => 'PostController@star', 'params' => array($post->id)), '<i class="fa fa-star-o"></i>')}}
				@endif
				{{ $post->starcount }}</span>
		</h2>
		<div class="verticalRule">
			<div class="float-left">
				@if(!empty($post->tags[0]))
					@if(!is_null($post->category))
						<b>Category:</b> {{HTML::actionlink($url = array('action' => 'PostController@category', 'params' => array(urlencode($post->category->name))), $post->category->name)}}
					@endif
				@else
					<p>
						<b>Posted by:</b> {{HTML::actionlink($url = array('action' => 'UserController@show', 'params' => array($post->user->username)), $post->user->username)}}
						@if(!is_null($post->team))
						in <b>{{$post->team->name}}</b> team
						@endif
						<b>on:</b> {{ date('Y-m-d',strtotime($post->created_at)) }}
					</p>
				@endif
			</div>
			<div class="float-right">
				@if(!empty($post->tags[0]))
					<b>Tags:</b>
					@foreach ($post->tags as $tag)
						{{HTML::actionlink($url = array('action' => 'PostController@tag', 'params' => array(urlencode($tag->name))), $tag->name, array('class' => 'label'))}}
					@endforeach
				@else
					@if(!is_null($post->category))
						<b>Category:</b> {{HTML::actionlink($url = array('action' => 'PostController@category', 'params' => array(urlencode($post->category->name))), $post->category->name)}}
					@endif
				@endif
			</div>
		</div>
		<hr>
		@if(!is_null($post->original))
			<p class="margin-top-half"><b>Forked from:</b> {{HTML::actionlink($url = array('action' => 'UserController@show', 'params' => array($post->original->user->id)), $post->original->user->username)}}/{{HTML::actionlink($url = array('action' => 'PostController@show', 'params' => array($post->original->id)), $post->original->name)}}</p>
			<hr>
		@endif
		<p id="description"><b>Description:</b> {{ $post->description }}</p>
		<p><b>Code:</b></p>
		@if(!is_null($post->category))
			<textarea class="code-editor readonly" data-lang="{{ strtolower($post->category->name) }}" id="blockCode">{{ $post->code }}</textarea>
		@else
			<textarea class="code-editor readonly" data-lang="xml" id="blockCode">{{ $post->code }}</textarea>
		@endif
		@if(Auth::check() && $post->private != 1)
			<div class="margin-bottom-half margin-top-half">
				<p><b>Embed Codeblock:</b></p>
				<pre class="embed">{{htmlentities('<object type="text/html" data="https://'.strtolower($siteName).'/embed/'.$post->slug.'" width="100%"></object>')}}</pre>
			</div>
		@endif
		@if ($post->private != 1)
			@if(count($post->comments) > 0)
				<h2 class="margin-top-half">Comments</h2>
				@foreach ($post->comments as $comment)
					@if($comment->status == 0)
						@if($comment->parent == 0)
							<div class="comment" id="comment-{{ $comment->id}}">
								<div>
									@if(Auth::check() && Auth::user()->id != $comment->user_id)
										@if ($rate->check($comment->id) == '+')
											{{ $rate->calc($comment->id) }}
											{{HTML::actionlink($url = array('action' => 'RateController@minus', 'params' => array($comment->id)), '<i class="fa fa-caret-down"></i>')}}
										@elseif($rate->check($comment->id) == '-')
											{{HTML::actionlink($url = array('action' => 'RateController@plus', 'params' => array($comment->id)), '<i class="fa fa-caret-up"></i>')}}
											{{ $rate->calc($comment->id) }}
										@else
											{{HTML::actionlink($url = array('action' => 'RateController@plus', 'params' => array($comment->id)), '<i class="fa fa-caret-up"></i>')}}
											{{ $rate->calc($comment->id) }}
											{{HTML::actionlink($url = array('action' => 'RateController@minus', 'params' => array($comment->id)), '<i class="fa fa-caret-down"></i>')}}
										@endif
									@else
										{{ $rate->calc($comment->id) }}
									@endif
								</div>
								<div>
									<b>{{ date('Y-m-d', strtotime($comment->created_at)) }}</b> - {{HTML::actionlink($url = array('action' => 'UserController@show', 'params' => array($comment->user_id)), $comment->user['username'])}}
									@if(Auth::check())
										<span class="pull-right">
										@if($comment->user_id == Auth::user()->id || HTML::hasPermission('CommentController@edit'))
												<a href="{{URL::action('PostController@show', $post->slug)}}/{{$comment->id}}"><i class="fa fa-pencil"></i></a>
											@endif
											@if(Auth::user()->id == $comment->user_id || HTML::hasPermission('CommentController@delete'))
												<a href="{{URL::action('CommentController@delete', $comment->id)}}"><i class="fa fa-trash-o"></i></a>
											@endif
										</span>
									@endif
									<p>{{ HTML::mention(HTML::markdown($comment->comment)) }}</p>
									@if(Auth::check())
										<a class="reply" href="#comment-{{$comment->id}}">Reply</a>
									@endif
									@include('comment.child')
								</div>
							</div>
						@endif
					@endif
				@endforeach
			@endif
			@if(Auth::check())
				<div id="comment">
					@if(is_null($commentToEdit))
						{{ Form::model($commentToEdit, array('action' => array('CommentController@createOrUpdate'))) }}
					@else
						{{ Form::model($commentToEdit, array('action' => array('CommentController@createOrUpdate', $commentToEdit->id))) }}
					@endif
					<h3><a class="float-left close-reply" href="#comment">Cancel</a> Make a comment</h3>
					{{ Form::hidden('post_id', $post->id); }}
					{{ Form::hidden('parent'); }}
					{{ Form::textarea('comment', Input::old('comment'), array('id' => 'comment', 'class' => 'mentionarea', 'rows' => '2', 'placeholder' => 'Your comment', 'data-validator' => 'required|min:3')) }}
					{{ $errors->first('comment', '<div class="alert error">:message</div>') }}
					<div class="margin-top-minus-one font-small">You can use {{HTML::actionlink($url = array('action' => 'MenuController@markdown'), 'markdown')}} in comments!</div>
					{{ Form::button('Comment', array('type' => 'submit')) }}
					{{ Form::close() }}
				</div>
			@else
				<p>You have to be logged in to comment, {{HTML::actionlink($url = array('action' => 'UserController@login'), 'sign up now')}}.</p>
			@endif
		@endif
	</div>
	@include('post.historyModal', array('post' => $post))
@stop

@section('script')
	@if(!is_null($post->category))
		{{HTML::codemirror($post->category->name)}}
	@endif
@stop                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       