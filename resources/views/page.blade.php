@extends('doc-weaver::layout')

@section('doc-weaver-content')
<section class="sidebar">
	<script async type="text/javascript" src="//cdn.carbonads.com/carbon.js?zoneid=1673&serve=C6AILKT&placement=laravelcom" id="_carbonads_js"></script>
	<small><a href="#" id="doc-expand" style="font-size: 9px; color: #525252;">▶</a></small>
	{!! $index !!}
</section>

<article>
	{!! $content !!}
</article>
@endsection