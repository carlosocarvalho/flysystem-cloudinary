# flysystem-cloudinary v3
Cloudinary adapter for The PHP League Flysystem v3

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/40851dce873643d4b8c4f720694237da)](https://app.codacy.com/app/carlosocarvalho-git/flysystem-cloudinary?utm_source=github.com&utm_medium=referral&utm_content=carlosocarvalho/flysystem-cloudinary&utm_campaign=Badge_Grade_Dashboard)
[![Author](https://img.shields.io/badge/autor-@carlosocarvalho-blue.svg?style=flat-square)](https://twitter.com/carlosocarvalho)
[![Latest Stable Version](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/v)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary) [![Total Downloads](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/downloads)](https://packagist.org/packages/carlosocarvalho/flysystem-cloudinary) [![License](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/license)](https://packagist.org/packages/carlosocarvalho/flysystem-cloudinary)
[![Suggesters](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/suggesters)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary)
[![Dependents](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/dependents)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary)
[![composer.lock](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/composerlock)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary)
[![Monthly Downloads](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/d/monthly)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary)

## Install

```bash
  composer require carlosocarvalho/flysystem-cloudinary
```

## Configuration

You can configure the package in two different ways. 

### Using CLOUDINARY_URL
You can configure the library using the environment variable ```CLOUDINARY_URL```. Whe using ```CLOUDINARY_URL``` you have access to the underlying Cloudinary SDK without instantiating the adapter or explicit instantiating the Cloudinary SDK.

You can read more in their documentation https://cloudinary.com/documentation/php_integration#setting_the_cloudinary_url_environment_variable

```php

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter;
use League\Flysystem\Filesystem;

$adapter = new CloudinaryAdapter();
$filesystem = new Filesystem( $adapter );

```

### Manual configuration

```php

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter;
use League\Flysystem\Filesystem;

$config = [
    'api_key' => ':key',
    'api_secret' => ':secret',
    'cloud_name' => ':name',
];

$adapter = new CloudinaryAdapter($config);
$filesystem = new Filesystem( $adapter );

```

## Example

### List contents and others actions use Filesystem api

```php

#Options use file type resource





$filesystem->listContents()

```

### Add Resource Type list in container `image`,`video`, `raw`

```php



CloudinaryAdapter::$resourceType = \Cloudinary\Asset\AssetType::IMAGE;
$filesystem->listContents()

```


### For use in laravel

To use in Laravel register you must register the driver. Learn <a href="https://github.com/carlosocarvalho/laravel-storage-cloudinary">how to register a custom filesystem</a> in the Laravel Documentation.

```php

    use Illuminate\Filesystem\FilesystemAdapter;
    use Illuminate\Support\Facades\Storage;
    use League\Flysystem\Filesystem;
    use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter;

    ...

    Storage::extend('cloudinary', function ($app, $config) {
        if(!empty(env('CLOUDINARY_URL'))){
            $adapter = new CloudinaryAdapter();
        }else{
            $adapter = new CloudinaryAdapter($config);
        }

        return new FilesystemAdapter(
            new Filesystem($adapter, $config),
            $adapter,
            $config
        );
    });

```

<a href="https://github.com/carlosocarvalho/laravel-storage-cloudinary"> Access this repository </a>
