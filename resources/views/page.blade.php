@extends('docweaver::layout')

@section('docweaver-content')
<section class="docweaver-sidebar sidebar">
	<aside class="docweaver-sidebar-popper sidebar-popper">
		<span></span>
		<span></span>
		<span></span>
	</aside>
	<div class="sidebar-content">
		<small><a href="#" class="docweaver-doc-expand doc-expand">▶</a></small>
		{!! $index !!}
	</div>
</section>

<article class="docweaver-article">
	<div class="docweaver-article-content article-content">
		{!! $content !!}
	</div>
</article>
@endsection
