<?php

/*
|--------------------------------------------------------------------------
| Docweaver Routes
|--------------------------------------------------------------------------
|
| This file defines the routes provided by the Docweaver Package.
|
*/

$routeConfig = DocweaverConfig::getRouteConfig();

// Controller FQCN
$docController = 'ReliQArts\\Docweaver\\Http\\Controllers\\DocumentationController';

// the route group
Route::group(DocweaverConfig::getRouteGroupBindings(), function () use ($routeConfig, $docController) {
    Route::get('/', "${docController}@index")->name($routeConfig['names']['index']);
    Route::get('{product}', "${docController}@productIndex")->name($routeConfig['names']['product_index']);
    Route::get('{product}/{version}/{page?}', "${docController}@show")->name($routeConfig['names']['product_page']);
});
