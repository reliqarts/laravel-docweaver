/**
 * Docweaver Asset Management via Laravel mix.
 * Webpack build steps.
 */

let mix = require('laravel-mix');

// constants
const publicFolder = 'public';
const vendorFolder = `${publicFolder}/vendor`;
const assetFolders = {
    styles: `${publicFolder}/css`,
    fonts: `${publicFolder}/fonts`,
    images: `${publicFolder}/img`,
    scripts: `${publicFolder}/js`,
    svg: `${publicFolder}/svg`,
    vendor: vendorFolder
};

// options
mix.options({
    extractVueStyles: false,
    processCssUrls: true,
    clearConsole: true,
    publicPath: publicFolder
});

if (mix.inProduction()) {
    mix.disableNotifications();
    mix.version();
} else {
    mix.sourceMaps();
}

// update config
mix.webpackConfig({
    externals: {
        'jquery': 'jQuery'
    }
});

// start mixing:
mix
    .js('resources/js/docweaver.js', assetFolders.scripts)
    .sass('resources/sass/docweaver.scss', assetFolders.styles);
