<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary;

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter as Adapter;
use CarlosOCarvalho\Flysystem\Cloudinary\Test\ApplicationCase;
use League\Flysystem\Filesystem;


class CloudinaryAdapterTest extends ApplicationCase
{

   
    
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$image_id = md5(strtotime('now')); //sprintf('uploads/%s.png', md5(strtotime('now')));
       
    }


  
    /**
     * Validate instance type is Class Core
     * @return void
     */
    public function test_valid_instance()
    {
        $cloudinary = new Adapter(self::$config);
        $this->assertInstanceOf('CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter', $cloudinary);

    }

    /**
     * @depends test_valid_instance
     * Upload success file on api 
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
     * Read on file of api
     * @return void
     */
    public function test_read_file()
    {   
        $adapter = self::$adapter;
        $id = sprintf('uploads/read-file-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $content = $adapter->read($id);
        $this->assertTrue($content == $this->getContentFile());
        $adapter->delete($id);
    }

    /**
     * @depends test_success_upload_file
     * @return void
     */
    public function test_rename_with_folder(){
       
        $adapter = self::$adapter;
        $id = sprintf('uploads/origin-%s', self::$image_id);
        $renamedId = sprintf('uploads/renamed-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $adapter->rename($id, $renamedId);
        $this->assertTrue($adapter->has($renamedId));
        $adapter->delete($renamedId);
        
    }

    /**
     * @depends test_success_upload_file
     * @return void
     */
    public function test_rename_file(){
       
        $adapter = self::$adapter;
        $id = sprintf('uploads-origin-%s', self::$image_id);
        $renamedId = sprintf('uploads-renamed-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $adapter->rename($id, $renamedId);
        $this->assertTrue($adapter->has($renamedId));
        $adapter->delete($renamedId);
        
    }

    
    /**
     * UpdateFile change name file 
     * @return void
     */
    public function test_update_file()
    {
        $adapter = self::$adapter;
        $id = sprintf('uploads/update-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $this->assertTrue($adapter->update($id, file_get_contents(self::IMAGE_UPDATE)));
        $adapter->delete($id);
        
    }


   
    /**
     * DeleteFile remove file by id registered
     * @return void
     */
    public function test_delete_file()
    {
        $adapter = self::$adapter;
        $id = sprintf('uploads/delete-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $this->assertTrue($adapter->delete($id));
        
        
    }
    /**
     * CopyFile  
     * @return void
     */
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

}
