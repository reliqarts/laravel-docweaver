# Laravel Docweaver

A simple Laravel 5.x product documentation package. 

Doc Weaver is suitable for product documentation and/or knowledge bases. Converts folder(s) of .md files into full-bread complete documentation. Docweaver is inspired by Laravel's very own documentation.

[![Built For Laravel](https://img.shields.io/badge/built%20for-laravel-red.svg?style=flat-square)](http://laravel.com)
[![Build Status](https://img.shields.io/scrutinizer/build/g/reliqarts/docweaver/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/reliqarts/docweaver/build-status/master)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/reliqarts/docweaver.svg?style=flat-square)](https://scrutinizer-ci.com/g/reliqarts/docweaver/)
[![License](https://poser.pugx.org/reliqarts/docweaver/license?format=flat-square)](https://packagist.org/packages/reliqarts/docweaver)
[![Latest Stable Version](https://poser.pugx.org/reliqarts/docweaver/version?format=flat-square)](https://packagist.org/packages/reliqarts/docweaver)
[![Latest Unstable Version](https://poser.pugx.org/reliqarts/docweaver/v/unstable?format=flat-square)](//packagist.org/packages/reliqarts/docweaver)

&nbsp;

## Key Features

Docweaver provides the following features and more out of the box.

- Multi-product support
    - Doc Weaver supports multiple products out-the-box. Just create your product folders and drop in your documentation version directories.
- Plug and play
    - Just install and configure and you're golden! *(approx. 2min)*

## Installation & Usage

### Installation

Install via composer; in console: 
```
composer require reliqarts/docweaver
``` 
or require in *composer.json*:
```js
{
    "require": {
        "reliqarts/docweaver": "^1.0"
    }
}
```
then run `composer update` in your terminal to pull it in.

Once this has finished, you will need to add the service provider to the providers array in your app.php config as follows:
*(n.b. This package supports Laravel's package auto-discovery; if you are using Laravel 5.5 or above you can skip this step.)*

```php
ReliQArts\Docweaver\DocweaverServiceProvider::class,
```

Finally, publish package resources and configuration:

```
php artisan vendor:publish --provider="ReliQArts\Docweaver\DocweaverServiceProvider"
``` 

You may opt to publish only configuration by using the `docweaver:config` tag:

```
php artisan vendor:publish --provider="ReliQArts\Docweaver\DocweaverServiceProvider" --tag="docweaver:config"
``` 
You may publish migrations in a similar manner using the tag `migrations`.

### Setup

Set the desired environment variables so the package knows your image model, controller(s), etc. 

Example environment config:
```
DOC_WEAVER_ROUTE_PREFIX=docs
DOC_WEAVER_DIR=resources/docs
```

These variables, and more are explained within the [config](https://github.com/ReliQArts/laravel-docweaver/blob/master/src/config/config.php) file.

### Documentation Directory

The documentation directory is the place where you put your project documentation directories. It may be changed with the config key `docweaver.storage.dir` or the environment variable `DOC_WEAVER_DIR`. The default documentation directory is `resources/docs`.

#### Structure

Each project directory should contain seperate folders for each documented version. Each version must have at least two (2) markdown files, namely `documentation.md` and `installation.md`, which serve as the sidebar and initial documentation pages respectively.

```
[doc dir]
    │
    └─── Project One
    │       └── 1.0
    │       └── 2.1
    │            └── documentation.md     # sidebar nav
    │            └── installation.md      # initial page
    │
    └─── Project Two
```

----

### Acknowledgements

This package was inspired by Laravel's [documentation](https://github.com/laravel/laravel) and uses its underlying mechanism as a base.