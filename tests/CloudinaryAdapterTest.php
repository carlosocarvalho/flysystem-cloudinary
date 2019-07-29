<?php
/**
 * Created by PhpStorm.
 * User: carlosocarvalho
 * Date: 02/02/2016
 * Time: 13:40
 */

include_once __DIR__ .'/../vendor/autoload.php';

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter as Adapter;

class CloudinaryAdapterTest extends PHPUnit_Framework_TestCase
{

    private $cloudinary;

    public function setUp()
    {
        $config = ['key' => ':key'];
        $this->cloudinary = new Adapter($config);
    }


    public function testFailure()
    {
        $this->assertInstanceOf('CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter', $this->cloudinary);
    }
}
