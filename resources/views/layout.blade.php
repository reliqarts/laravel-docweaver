@extends($viewTemplateInfo['master_template'])

@php
$accents = (empty($viewTemplateInfo['accents']) ? [] : $viewTemplateInfo['accents']);
$accents['product_line'] = (bool) $accents['product_line'];
$scripts = '<script type="text/javascript" src="/vendor/docweaver/js/docweaver.js"></script>';
$styles = '<link media="all" type="text/css" rel="stylesheet" href="/vendor/docweaver/css/docweaver.css" />';
@endphp

@section($viewTemplateInfo['master_section'])
<div id="docweaver-wrapper" class="docweaver-wrapper docs-wrapper">
    @isset($currentProduct)
    <nav id="docweaver-product-bar" class="navbar navbar-expand-sm navbar-light">
        <a id="docweaver-docs-home-link" class="docs-home" href="{!! route($routeConfig['names']['index']) !!}">
            <span></span>
            <span></span>
            <span></span>
        </a>
        <a id="docweaver-current-product-name" class="navbar-brand" href="{!! route($routeConfig['names']['product_index'], $currentProduct->key) !!}">{{ $currentProduct->getName() }}</a>
        <div class="docweaver-navbar-collapse-replacement navbar-fake-collapse" id="docweaver-navbar-collapse-replacement">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <div class="version-switcher btn-group ml-auto">
                        <button disabled class="current-version btn btn-primary btn-sm" type="button">{{ $currentVersion }}</button>
                        <button class="version-menu-btn btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <div class="version-menu dropdown-menu dropdown-menu-right">
                            <h6 class="dropdown-header">Versions</h6>
                            @foreach ($currentProduct->getVersions() as $versionTag => $versionName)
                                @if ($currentVersion != $versionTag)
                                <a class="dropdown-item" href="{!! route('docs.show', [$currentProduct->key, $versionTag]) !!}">{{ $versionName }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    @else
    <div id="docweaver-product-line" class="{{ $accents['product_line'] ? 'show' : 'hide' }}"></div>
    @endisset
    <div class="docs container">
    @yield('docweaver-content')
    </div>
    @if(empty($accents['footnotes']) ? true : $accents['product_line'])
    <aside id="docweaver-footnotes">
        <p class="by-line">Docs by <a href="http://docweaver.reliqarts.com" target="docweaver.rqa">Docweaver</a>.</p>
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