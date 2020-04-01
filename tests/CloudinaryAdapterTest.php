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
    const IMAGE_UPDATE = __DIR__.'/logo-update.png';


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
    //    self::$adapter->delete(sprintf('uploads/%s', self::$image_id));
    //    self::$adapter->delete(sprintf('uploads/renamed-%s', self::$image_id));
    //    self::$adapter->delete(sprintf('uploads/update-%s', self::$image_id));
    }


    public function test_valid_instance()
    {
        $cloudinary = new Adapter(self::$config);
        $this->assertInstanceOf('CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter', $cloudinary);

    }

    /**
     * @depends test_valid_instance
     *
     * @return void
     */
   
    public function test_success_upload_file()
    {   
        $adapter = self::$adapter;
        $id = sprintf('uploads/%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $meta= $adapter->getMetadata($id);
        $this->assertContains($id, $meta);
        $this->assertEquals(filesize(self::IMAGE),$adapter->getSize($id));
        $this->assertEquals('image/png',$adapter->getMimetype($id));
        $this->assertEquals($meta['timestamp'], $adapter->getTimestamp($id));
        $adapter->delete($id);
    }
    /**
     * @depends test_success_upload_file
     *
     * @return void
     */
    public function test_rename_file(){
       
        $adapter = self::$adapter;
        $id = sprintf('uploads/origin-%s', self::$image_id);
        $renamedId = sprintf('uploads/renamed-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $adapter->rename($id, $renamedId);
        $this->assertTrue($adapter->has($renamedId));
        $adapter->delete($renamedId);
        
    }

    public function test_create_and_delete_dir()
    {
        $adapter = self::$adapter;
        $dirName = 'test-directory-name';
        $result = $adapter->createDir($dirName);
       
        //$this->assertContains($dirName, $result);
        $this->assertTrue($result);
        $this->assertTrue($adapter->deleteDir($dirName));
        
    }

    public function test_update_file()
    {
        $adapter = self::$adapter;
        $id = sprintf('uploads/update-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $this->assertTrue($adapter->update($id, file_get_contents(self::IMAGE_UPDATE)));
        $adapter->delete($id);
        
    }


    public function test_update_stream_file()
    {
        $adapter = self::$adapter;
        $id = sprintf('uploads/update-stream-%s', self::$image_id);

        $stream = fopen(self::IMAGE_UPDATE, 'r+');
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $this->assertTrue($adapter->updateStream($id, $stream));
        fclose($stream);
        $adapter->delete($id);
        
    }
    
    public function test_delete_file()
    {
        $adapter = self::$adapter;
        $id = sprintf('uploads/delete-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $this->assertTrue($adapter->delete($id));
        
        
    }

    public function test_copy_file()
    {
        $adapter = self::$adapter;
        $id = sprintf('uploads/for-copy-%s', self::$image_id);
        $copyId = sprintf('uploads/copy-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $this->assertTrue($adapter->copy($id, $copyId));
        $adapter->delete($copyId);
        $adapter->delete($id);
    }

    public function test_list_container()
    {
        $adapter = self::$adapter;
        $id = sprintf('uploads/%s', md5(strtotime('now')));
       
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $result = $adapter->listContents('uploads');
        $arrayFilter = array_filter($result, function($row) use ($id){
            return $row['public_id'] == $id;
        });
        $this->assertCount(1, $arrayFilter);
        $adapter->delete($id);
        
    }

    public function getContentFile(){
        return file_get_contents(self::IMAGE);
    }



    
}
