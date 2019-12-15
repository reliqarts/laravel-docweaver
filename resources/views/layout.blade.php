@php
    /**
     * @var \ReliqArts\Docweaver\Service\ConfigProvider $docweaverConfigProvider
     */
    $templateConfig = $docweaverConfigProvider->getTemplateConfig();
    $scripts = '<script type="text/javascript" src="/vendor/docweaver/js/docweaver.js"></script>';
    $styles = '<link media="all" type="text/css" rel="stylesheet" href="/vendor/docweaver/css/docweaver.css" />';
@endphp

@extends($templateConfig->getMasterTemplate())

@section($templateConfig->getMasterSection())
<div id="docweaver-wrapper" class="docweaver-wrapper docs-wrapper">
    @isset($currentProduct)
    <nav id="docweaver-product-bar" class="navbar navbar-expand-sm navbar-light">
        <a id="docweaver-docs-home-link" class="docs-home" href="{!! route($docweaverConfigProvider->getIndexRouteName()) !!}">
            <span></span>
            <span></span>
            <span></span>
        </a>
        <a id="docweaver-current-product-name" class="navbar-brand" href="{!! route($docweaverConfigProvider->getProductIndexRouteName(), $currentProduct->getKey()) !!}">{{ $currentProduct->getName() }}</a>
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
                                <a class="dropdown-item" href="{!! route('docs.show', [$currentProduct->getKey(), $versionTag, $page]) !!}">{{ $versionName }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    @else
    <div id="docweaver-product-line" class="{{ $templateConfig->isShowProductLine() ? '' : 'invisible' }}"></div>
    @endisset
    <div class="docs container">
    @yield('docweaver-content')
    </div>
    @if($templateConfig->isShowFootnotes())
    <aside id="docweaver-footnotes">
        <p class="by-line">Docs by <a href="http://docweaver.reliqarts.com" target="docweaver.rqa">Docweaver</a>.</p>
    </aside>
    @endif
</div>
@endsection

@if($templateConfig->hasStyleStack())
@push($templateConfig->getStyleStack(), $styles)
@else
{!! $styles !!}
@endif

@if($templateConfig->hasScriptStack())
@push($templateConfig->getScriptStack(), $scripts)
@else
{!! $scripts !!}
@endif
