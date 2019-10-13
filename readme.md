# Laravel Docweaver

A simple Laravel 5.x/6.x product documentation package. 

Docweaver is suitable for product documentation and/or knowledge bases. Converts folder(s) of .md files into full-bread complete documentation. Docweaver is inspired by Laravel's very own documentation.

[![Built For Laravel](https://img.shields.io/badge/built%20for-laravel-red.svg?style=flat-square)](http://laravel.com)
[![Build Status (all)](https://img.shields.io/travis/com/reliqarts/laravel-docweaver?style=flat-square)](https://travis-ci.com/reliqarts/laravel-docweaver)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/reliqarts/laravel-docweaver.svg?style=flat-square)](https://scrutinizer-ci.com/g/reliqarts/laravel-docweaver/)
[![Codecov](https://img.shields.io/codecov/c/github/reliqarts/laravel-docweaver.svg?style=flat-square)](https://codecov.io/gh/reliqarts/laravel-docweaver)
[![License](https://poser.pugx.org/reliqarts/laravel-docweaver/license?format=flat-square)](https://packagist.org/packages/reliqarts/laravel-docweaver)
[![Latest Stable Version](https://poser.pugx.org/reliqarts/laravel-docweaver/version?format=flat-square)](https://packagist.org/packages/reliqarts/laravel-docweaver)
[![Latest Unstable Version](https://poser.pugx.org/reliqarts/laravel-docweaver/v/unstable?format=flat-square)](//packagist.org/packages/reliqarts/laravel-docweaver)
[![check it out!](https://img.shields.io/badge/read-the%20docs-blue.svg?style=flat-square)](http://docweaver.reliqarts.com) 

&nbsp;

## Key Features

Docweaver provides the following features and more out of the box.

- Multi-product support
    - Docweaver supports multiple products out-the-box. Just create your product folders and drop in your documentation version directories.
- Plug and play
    - Just install and configure and you're golden! *(approx. 2min)*

## Installation & Usage

### Installation

Install via composer; in console: 
```
composer require reliqarts/laravel-docweaver
``` 
or require in *composer.json*:
```json
{
    "require": {
        "reliqarts/laravel-docweaver": "^2.0"
    }
}
```
then run `composer update` in your terminal to pull it in.

Once this has finished, you will need to add the service provider to the providers array in your app.php config as follows:
*(n.b. This package supports Laravel's package auto-discovery; if you are using Laravel 5.5 or above you can skip this step.)*

```php
ReliqArts\Docweaver\ServiceProvider::class,
```

Ensure that your applications public storage directory is linked and accessible via the browser.

```bash 
php artisan storage:link
```
see: https://laravel.com/docs/master/filesystem

Finally, publish package resources and configuration:

```
php artisan vendor:publish --provider="ReliqArts\Docweaver\ServiceProvider"
``` 

You may opt to publish only configuration by using the `docweaver-config` tag:

```
php artisan vendor:publish --provider="ReliqArts\Docweaver\ServiceProvider" --tag="docweaver-config"
```

### Setup

Set the desired environment variables so the package knows your image model, controller(s), etc. 

Example environment config:
```
DOCWEAVER_ROUTE_PREFIX=docs
DOCWEAVER_DIR=resources/docs
```

These variables, and more are explained within the [config](https://github.com/ReliqArts/laravel-docweaver/blob/master/src/config/config.php) file.

### Documentation Directory

The documentation directory is the place where you put your project documentation directories. It may be changed with the config key `docweaver.storage.dir` or the environment variable `DOCWEAVER_DIR`. The default documentation directory is `resources/docs`.

#### Structure

Each project directory should contain seperate folders for each documented version. Each version must have at least two (2) markdown files, namely `documentation.md` and `installation.md`, which serve as the sidebar and initial documentation pages respectively.

```
[doc dir]
    │
    └─── Project One
    │       └── 1.0 
    │       └── 2.1
    │            └── .docweaver.yml       # meta file (optional)
    │            └── documentation.md     # sidebar nav
    │            └── installation.md      # initial page
    │
    └─── Project Two
```

#### Meta File

Configurations for each doc version may be placed in `.docweaver.yml`. The supported settings are:
- #### name
    Product name.
- #### description
    Product description.

- #### image_url
    Product image url. This may be an absolute url (e.g. `http://mywebsite.com/myimage.jpg`) or an image found in the `images` resource directory.

    To use the `foo.jpg` in the images directory you would set `image_url` to `{{docs}}/images/foo.jpg`.

    For more info. see: [Assets](/docs/{{version}}/assets)

----

### Acknowledgements

This package was inspired by Laravel's [documentation](https://github.com/laravel/laravel) and uses its underlying mechanism as a base.
