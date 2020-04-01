<?php

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter as Adapter;
use CarlosOCarvalho\Flysystem\Cloudinary\Test\ApplicationCase;
use League\Flysystem\Filesystem;


class DirectoryStreamTest extends ApplicationCase
{

   
    /**
     *
     * @return void
     */
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

    /**
     *
     * @return void
     */
    public function test_create_and_delete_dir()
    {
        $adapter = self::$adapter;
        $dirName = 'test-directory-name';
        $result = $adapter->createDir($dirName);
       
        //$this->assertContains($dirName, $result);
        $this->assertTrue($result);
        $this->assertTrue($adapter->deleteDir($dirName));
        
    }
    


}