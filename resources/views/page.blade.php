@extends('doc-weaver::layout')

@section('doc-weaver-content')
<section id="doc-weaver-sidebar" class="sidebar">
	<aside id="doc-weaver-sidebar-popper" class="sidebar-toggle sidebar-popper">
		<span></span>
		<span></span>
		<span></span>
	</aside>
	<div class="sidebar-content">
		<small><a href="#" id="doc-expand" class="doc-weaver-doc-expand">▶</a></small>
		{!! $index !!}
	</div>
</section>

<article id="doc-weaver-article">
	<div class="article-content">
		{!! $content !!}
	</div>
</article>
@endsection