@extends('docweaver::layout')

@section('docweaver-content')
<section id="docweaver-sidebar" class="sidebar">
	<aside id="docweaver-sidebar-popper" class="sidebar-toggle sidebar-popper">
		<span></span>
		<span></span>
		<span></span>
	</aside>
	<div class="sidebar-content">
		<small><a href="#" id="doc-expand" class="docweaver-doc-expand">▶</a></small>
		{!! $index !!}
	</div>
</section>

<article id="docweaver-article">
	<div id="docweaver-article-content" class="docweaver-article-content article-content">
		{!! $content !!}
	</div>
</article>
@endsection