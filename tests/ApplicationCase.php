<?php 

namespace CarlosOCarvalho\Flysystem\Cloudinary\Test;


require __DIR__.'/Helpers.php';
include_once __DIR__ .'/../vendor/autoload.php';

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter as Adapter;
use League\Flysystem\Filesystem;

use PHPUnit\Framework\TestCase;



class ApplicationCase extends TestCase
{
   
    const IMAGE = __DIR__.'/logo-git.png';
    const IMAGE_UPDATE = __DIR__.'/logo-update.png';

    protected static $cloudinary;

    protected static $adapter;
    
    protected static $image_id;

    protected static $config;
    
    public static function setUpBeforeClass(): void
    {
        self::$config = [
            'api_key' => '788386319666942',
            'api_secret' => 'Uu1UjdDROM4m6lq80l7-9Zqt8Mg',
            'cloud_name' => 'carlosocarvalho',
            "secure_distribution" => null,
            "private_cdn" => false,
            "cname" => null
        ];
        self::$cloudinary = new Adapter(self::$config);
        self::$adapter = new Filesystem(self::$cloudinary);
    }

    public function getContentFile(){
        return file_get_contents(self::IMAGE);
    }

   

}