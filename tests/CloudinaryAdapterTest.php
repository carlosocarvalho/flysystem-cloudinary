<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary;

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter as Adapter;
use CarlosOCarvalho\Flysystem\Cloudinary\Test\ApplicationCase;
use League\Flysystem\File;
use League\Flysystem\Filesystem;

class CloudinaryAdapterTest extends ApplicationCase
{

    protected static $cloudinary;

    private static $adapter;
    
    private static $image_id;

    private static $config;

    const IMAGE = __DIR__.'/logo-git.png';


    
    



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
        self::$image_id = md5(strtotime('now')); //sprintf('uploads/%s.png', md5(strtotime('now')));
        self::$cloudinary = new Adapter(self::$config);
        self::$adapter = new Filesystem(self::$cloudinary);
    }


    public static function tearDownAfterClass(): void
    {
       self::$adapter->delete(sprintf('uploads/%s', self::$image_id));
    }


    public function testAssertInstanceAdapter()
    {
        $cloudinary = new Adapter(self::$config);
        $this->assertInstanceOf('CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter', $cloudinary);

    }
   
    public function testAUpload()
    {   
        $adapter = self::$adapter;
        $id = sprintf('uploads/%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        //fclose($stream);
        $this->assertTrue($up);
    }

    public function testReadFileName(){
       
        $stub = $this->getMockBuilder(Filesystem::class)
        ->disableOriginalConstructor()
        ->setMethods(['read'])
        ->getMock();
       
        dump(cloudinary_url('okd'));
        // Configure the stub.
        $stub->method('read')
             ->willReturn(['content'=>'file.png']);

         $this->assertEquals(['content'=>'file.png'], $stub->read('file.png'));    
        //$adapter = self::$adapter;
        //$id = sprintf('/uploads/read-%s', self::$image_id);
        //$this->assertTrue($adapter->write($id, $this->getContentFile()));
        //$url = cloudinary_url($id.'.png');
        //sleep(3);
        //dump(file_get_contents($url));
        //dump($adapter->read($id.'.png', array("width" => 100, "height" => 150, "crop" => "fill")));
    }


    public function getContentFile(){
        return file_get_contents(self::IMAGE);
    }



    
}
