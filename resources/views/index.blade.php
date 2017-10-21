@extends('docweaver::layout')

@section('docweaver-content')
<div id="docweaver-product-showcase" class="products product-showcase">
    <h1 class="docweaver-h1">{{ $title }}</h1>
    @if(count($products))
    <div class="product-list row">
        @foreach($products as $productKey => $product)
        <div class="col-md-4">
            <div id="product-{{ $productKey }}" class="product">
                <a href="{!! route($routeConfig['names']['product_index'], $productKey) !!}">
                    <h4 class="product-title">{{ $product->getName() }}</h4>
                    <div class="product-info">
                        <ul class="info-list">
                            <li>Version: {{ $product->getDefaultVersion() }}</li>
                            <li>Last updated: {{ $product->getLastModified()->diffForHumans() }}</li>
                        </ul>
                    </div>
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty no-docs">
        <p>No documentation available. Please come back later.</p>
    </div>
    @endif
</div>
@endsection