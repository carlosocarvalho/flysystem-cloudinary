# flysystem-cloudinary
Adapter for theleague php flysystem for Cloudinary

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/40851dce873643d4b8c4f720694237da)](https://app.codacy.com/app/carlosocarvalho-git/flysystem-cloudinary?utm_source=github.com&utm_medium=referral&utm_content=carlosocarvalho/flysystem-cloudinary&utm_campaign=Badge_Grade_Dashboard)
[![Author](https://img.shields.io/badge/autor-@carlosocarvalho-blue.svg?style=flat-square)](https://twitter.com/carlosocarvalho)
[![Latest Stable Version](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/v/stable)](https://packagist.org/packages/carlosocarvalho/flysystem-cloudinary) [![Total Downloads](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/downloads)](https://packagist.org/packages/carlosocarvalho/flysystem-cloudinary) [![Latest Unstable Version](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/v/unstable)](https://packagist.org/packages/carlosocarvalho/flysystem-cloudinary) [![License](https://poser.pugx.org/carlosocarvalho/flysystem-cloudinary/license)](https://packagist.org/packages/carlosocarvalho/flysystem-cloudinary)
[![Build Status](https://travis-ci.org/carlosocarvalho/flysystem-cloudinary.svg?branch=master)](https://travis-ci.org/carlosocarvalho/flysystem-cloudinary)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/40851dce873643d4b8c4f720694237da)](https://www.codacy.com/app/carlosocarvalho-git/flysystem-cloudinary?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=carlosocarvalho/flysystem-cloudinary&amp;utm_campaign=Badge_Grade)

Install

```bash
  composer require carlosocarvalho/flysystem-cloudinary
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

[https://github.com/carlosocarvalho/laravel-storage-cloudinary]


 
 