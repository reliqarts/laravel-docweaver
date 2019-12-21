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
    <div class="docweaver-wrapper docs-wrapper">
        @isset($currentProduct)
            <nav class="docweaver-product-bar">
                <a class="docweaver-docs-home-link docs-home-link"
                   href="{!! route($docweaverConfigProvider->getIndexRouteName()) !!}">
                    <span></span>
                    <span></span>
                    <span></span>
                </a>
                <a class="docweaver-current-product-name"
                   href="{!! route($docweaverConfigProvider->getProductIndexRouteName(), $currentProduct->getKey()) !!}">{{ $currentProduct->getName() }}</a>
                <div class="docweaver-current-product-versions">
                    <label for="docweaver-version-selector" class="docweaver-version-selector-label">
                        <span>@lang('Version')</span>
                        <select name="docweaver-version-selector" class="docweaver-version-selector">
                            @foreach ($currentProduct->getVersions() as $versionTag => $versionName)
                                <option class="docweaver-version-selector-option"
                                        data-link="{!! route($docweaverConfigProvider->getProductPageRouteName(), [$currentProduct->getKey(), $versionTag, $page]) !!}"
                                    {{ $currentVersion === $versionTag ? 'selected' : null }}>{{ $versionName }}</option>
                            @endforeach
                        </select>
                        <div class="docweaver-version-selector-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </label>
                </div>
            </nav>
        @else
            <div class="docweaver-product-line {{ $templateConfig->isShowProductLine() ? '' : 'invisible' }}"></div>
        @endisset
        <div class="docs container">
            @yield('docweaver-content')
        </div>
        @if($templateConfig->isShowFootnotes())
            <aside class="docweaver-footnotes">
                <p class="by-line">Docs by <a href="http://docweaver.reliqarts.com" target="docweaver.rqa">Docweaver</a>.
                </p>
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
