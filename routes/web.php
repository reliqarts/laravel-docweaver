<?php

/*
|--------------------------------------------------------------------------
| DocWeaver Routes
|--------------------------------------------------------------------------
|
| This file defines the routes provided by the DocWeaver Package.
|
*/

$routeConfig = DocWeaverHelper::getRouteConfig();

// Controller FQCN
$docsController = 'ReliQArts\\DocWeaver\\Http\\Controllers\\DocsController';

// the route group
Route::group(DocWeaverHelper::getRouteGroupBindings(), function () use ($routeConfig, $docsController) {
    Route::get('/', "$docsController@index")->name($routeConfig['names']['index']);
    Route::get('{product}', "$docsController@productIndex")->name($routeConfig['names']['product_index']);
    Route::get('{product}/{version}/{page?}', "$docsController@show")->name($routeConfig['names']['product_page']);
});