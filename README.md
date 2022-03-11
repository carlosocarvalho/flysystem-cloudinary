# flysystem-cloudinary V2
Adapter for theleague php flysystem for Cloudin

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/40851dce873643d4b8c4f720694237da)](https://app.codacy.com/app/carlosocarvalho-git/flysystem-cloudinary?utm_source=github.com&utm_medium=referral&utm_content=carlosocarvalho/flysystem-cloudinary&utm_campaign=Badge_Grade_Dashboard)
[![Author](https://img.shields.io/badge/autor-@carlosocarvalho-blue.svg?style=flat-square)](https://twitter.com/carlosocarvalho)
[![Latest Stable Version](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/v)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary) [![Total Downloads](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/downloads)](https://packagist.org/packages/carlosocarvalho/flysystem-cloudinary) [![License](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/license)](https://packagist.org/packages/carlosocarvalho/flysystem-cloudinary)
[![Suggesters](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/suggesters)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary)
[![Dependents](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/dependents)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary)
[![composer.lock](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/composerlock)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary)
[![Monthly Downloads](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/d/monthly)](//packagist.org/packages/carlosocarvalho/flysystem-cloudinary)

Install

```bash
  composer require carlosocarvalho/flysystem-cloudinary
```

Add the following keys to your .env file
```bash
API_KEY=
API_SECRET=
CLOUD_NAME=
```

Example

```php

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter as Adapter;

$config = [
    'api_key' => ':key',
    'api_secret' => ':secret',
    'cloud_name' => ':name',
];

$container = new Adapter($config);

$filesystem = new League\Flysystem\Filesystem( $container );

```

## List contents and others actions use Filesystem api

```php

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
        $adapter = new CloudinaryAdapter($config);

        return new FilesystemAdapter(
            new Filesystem($adapter, $config),
            $adapter,
            $config
        );
    });

```

<a href="https://github.com/carlosocarvalho/laravel-storage-cloudinary"> Access this repository </a>
