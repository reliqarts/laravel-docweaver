<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use ReliqArts\Docweaver\Contract\ConfigProvider;
use ReliqArts\Docweaver\Http\Controller\DocumentationController;

/**
 * @var ConfigProvider
 */
$configProvider = resolve(ConfigProvider::class);

// Controller Fully Qualified...
$docController = DocumentationController::class;

// the route group
Route::group($configProvider->getRouteGroupBindings(), static function () use ($configProvider, $docController) {
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
