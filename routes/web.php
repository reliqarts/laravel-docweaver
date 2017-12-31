<?php

/*
|--------------------------------------------------------------------------
| Docweaver Routes
|--------------------------------------------------------------------------
|
| This file defines the routes provided by the Docweaver Package.
|
*/

$routeConfig = DocweaverHelper::getRouteConfig();

// Controller FQCN
$docController = 'ReliQArts\\Docweaver\\Http\\Controllers\\DocumentationController';

// the route group
Route::group(DocweaverHelper::getRouteGroupBindings(), function () use ($routeConfig, $docController) {
    Route::get('/', "$docController@index")->name($routeConfig['names']['index']);
    Route::get('{product}', "$docController@productIndex")->name($routeConfig['names']['product_index']);
    Route::get('{product}/{version}/{page?}', "$docController@show")->name($routeConfig['names']['product_page']);
});
