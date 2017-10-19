@extends($viewTemplateInfo['master_template'])

@php
$accents = (empty($viewTemplateInfo['accents']) ? [] : $viewTemplateInfo['accents']);
$scripts = '<script type="text/javascript" src="/vendor/doc-weaver/js/doc-weaver.js"></script>';
$styles = '<link media="all" type="text/css" rel="stylesheet" href="/vendor/doc-weaver/css/doc-weaver.css" />';
@endphp

@section($viewTemplateInfo['master_section'])
<div id="doc-weaver-wrapper" class="doc-weaver-wrapper docs-wrapper">
    @isset($currentProduct)
    <nav id="doc-weaver-product-bar" class="navbar navbar-expand-sm navbar-light">
        <a id="doc-weaver-docs-home-link" class="docs-home" href="{!! route($routeConfig['names']['index']) !!}">
            <span></span>
            <span></span>
            <span></span>
        </a>
        <a class="navbar-brand" href="{!! route($routeConfig['names']['product_index'], $currentProduct['key']) !!}">{{ $currentProduct['name'] }}</a>
        <div class="doc-weaver-navbar-collapse-replacement navbar-fake-collapse" id="doc-weaver-navbar-collapse-replacement">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <div class="version-switcher btn-group ml-auto">
                        <button disabled class="current-version btn btn-primary btn-sm" type="button">{{ $currentVersion }}</button>
                        <button class="version-menu-btn btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <div class="version-menu dropdown-menu dropdown-menu-right">
                            <h6 class="dropdown-header">Versions</h6>
                            @foreach ($currentProduct['versions'] as $versionTag => $versionName)
                                @if ($currentVersion != $versionTag)
                                <a class="dropdown-item" href="{!! route('docs.show', [$currentProduct['key'], $versionTag]) !!}">{{ $versionName }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    @else
    @if(empty($accents['product_line']) ? true : $accents['product_line'])
    <div id="doc-weaver-product-line"></div>
    @endif
    @endisset
    <div class="docs container">
    @yield('doc-weaver-content')
    </div>
    @if(empty($accents['footnotes']) ? true : $accents['product_line'])
    <aside id="doc-weaver-footnotes">
        <p class="by-line">Docs by <a href="http://docweaver.reliqarts.com" target="doc-weaver.rqa">Doc Weaver</a>.</p>
    </aside>
    @endif  
</div>
@endsection

@if(!empty($viewTemplateInfo['style_stack']))
@push($viewTemplateInfo['style_stack'], $styles)
@else
{!! $styles !!}
@endif

@if(!empty($viewTemplateInfo['script_stack']))
@push($viewTemplateInfo['script_stack'], $scripts)
@else
{!! $scripts !!}
@endif