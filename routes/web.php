<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use ReliqArts\Docweaver\Contracts\ConfigProvider;

/**
 * @var ConfigProvider
 */
$configProvider = resolve(ConfigProvider::class);

// Controller Fully Qualified...
$docController = 'ReliqArts\\Docweaver\\Http\\Controllers\\DocumentationController';

// the route group
Route::group($configProvider->getRouteGroupBindings(), function () use ($configProvider, $docController) {
    Route::get(
        '/',
        sprintf('%s@index', $docController)
    )->name($configProvider->getIndexRouteName());
    Route::get(
        '{product}',
        sprintf('%s@productIndex', $docController)
    )->name($configProvider->getProductIndexRouteName());
    Route::get(
        '{product}/{version}/{page?}',
        sprintf('%s@show', $docController)
    )->name($configProvider->getProductPageRouteName());
});
