<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary\Test;


include_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ .'/Helpers.php';

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter as Adapter;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;



class ApplicationCase extends TestCase
{

    const IMAGE = __DIR__ . '/logo-git.png';
    const IMAGE_UPDATE = __DIR__ . '/logo-update.png';

    const ROOT = 'cloudinary_test';

    protected static $image_id;

    protected static $config;

    /**
     * @var Filesystem
     */
    private $adapter;

    public function imageName(): string
    {
        return  md5(strtotime('now'));
    }

    public function adapter(): Filesystem
    {
        if (!$this->adapter instanceof Filesystem) {
            $this->adapter = $this->createFilesystemAdapter();
        }
        return $this->adapter;
    }

    public function getContentFile()
    {
        return file_get_contents(self::IMAGE);
    }

    protected function createFilesystemAdapter()
    {
        return   new Filesystem($this->createCloudinaryInstance());
    }

    protected function createCloudinaryInstance()
    {
        self::$config = [
            'api_key' => '788386319666942',
            'api_secret' => 'Uu1UjdDROM4m6lq80l7-9Zqt8Mg',
            'cloud_name' => 'carlosocarvalho',
            "secure_distribution" => null,
            "private_cdn" => false,
            "cname" => null
        ];

        return new Adapter(self::$config);
    }
    

    protected function makePathFile($file): string
    {
         return sprintf('%s/%s', self::ROOT, $file);
    }
}
