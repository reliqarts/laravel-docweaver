@extends('doc-weaver::layout')

@section('doc-weaver-content')
<section class="sidebar">
	<small><a href="#" id="doc-expand" class="doc-weaver-doc-expand">▶</a></small>
	{!! $index !!}
</section>

<article>
	{!! $content !!}
</article>
@endsection