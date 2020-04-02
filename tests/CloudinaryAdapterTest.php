<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary;

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter as Adapter;
use CarlosOCarvalho\Flysystem\Cloudinary\Test\ApplicationCase;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;

class CloudinaryAdapterTest extends ApplicationCase
{


    /**
     * Validate instance type is Class Core
     * @return void
     */
    public function test_valid_instance()
    {

        $this->assertInstanceOf('CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter', $this->createCloudinaryInstance());
    }

    /**
     * @test
     * Upload success file on api 
     * @return void
     */
    public function create_and_check_size_and_check_type()
    {
        $adapter = $this->adapter();
        $id = $this->makePathFile(sprintf('%s', $this->imageName()));
        $adapter->write($id, $this->getContentFile());
        $this->assertEquals(filesize(self::IMAGE), $adapter->fileSize($id));
        $this->assertEquals('image/png', $adapter->mimeType($id));
    }

    /**
     * @test
     * Upload success file on api 
     * @return void
     */
    public function create_and_moving_and_failure()
    {

        $adapter = $this->adapter();
        $id = $this->makePathFile(sprintf('%s', $this->imageName()));
        $moveId = $this->makePathFile(sprintf('moved-%s', $this->imageName()));
        $adapter->write($id, $this->getContentFile());

        $adapter->move($id, $moveId);

        $this->assertTrue($adapter->fileExists($moveId));
    }

    /**
     * @test
     *
     * @return void
     */
    public function move_failure_file()
    {
        $adapter = $this->adapter();
        $this->expectException(UnableToMoveFile::class);
        $adapter->move('source.txt', 'destination.txt');
    }


    /**
     * @test
     * Read on file of api
     * @return void
     */
    public function read_file()
    {
        $adapter = $this->adapter();
        $id = $this->makePathFile(sprintf('read-file-%s', $this->imageName()));
        $adapter->write($id, $this->getContentFile());
        $content = $adapter->read($id);
        $this->assertTrue($content == $this->getContentFile());
    }

    /**
     * @test
     * @return void
     */
    public function rename_with_folder()
    {

        $adapter = $this->adapter();
        $id = $this->makePathFile(sprintf('origin-%s', $this->imageName()));
        $renamedId = $this->makePathFile(sprintf('renamed-%s', $this->imageName()));
        $adapter->write($id, $this->getContentFile());
        $adapter->move($id, $renamedId);
        $this->assertTrue($adapter->fileExists($renamedId));
    }
    /**
     * @test
     * DeleteFile remove file by id registered
     * @return void
     */
    public function create_and_delete_file()
    {
        $adapter = $this->adapter();
        $id = $this->makePathFile(sprintf('delete-%s', $this->imageName()));
        $adapter->write($id, $this->getContentFile());
        $adapter->delete($id);
        $this->assertFalse($adapter->fileExists($id));
    }

    /**
     * @test
     * DeleteFile remove file by id registered
     * @return void
     */
    public function failure_delete_file()
    {
        $adapter = $this->adapter();
        $this->expectException(UnableToDeleteFile::class);
        $id = $this->makePathFile(sprintf('no-exist-delete-%s', $this->imageName()));
        $adapter->delete($id);
    }
    /**
     * @test
     * CopyFile  
     * @return void
     */
    public function copy_file()
    {
        $adapter = $this->adapter();

        $id = $this->makePathFile(sprintf('for-copy-%s', $this->imageName()));
        $copyId = $this->makePathFile(sprintf('copy-%s', $this->imageName()));
        $adapter->write($id, $this->getContentFile());
        $adapter->copy($id, $copyId);
        $this->assertTrue($adapter->fileExists($copyId));
    }


    /**
     * @test
     */
    public function writing_and_reading_with_streams(): void
    {
        $writeStream = fopen(self::IMAGE, 'r');
        $id = $this->makePathFile(sprintf('file-stream-%s', $this->imageName()));
        $adapter = $this->adapter();
        $adapter->writeStream($id, $writeStream);
        fclose($writeStream);
        $readStream = $adapter->readStream($id);
        $contents = stream_get_contents($readStream);
        fclose($readStream);
        $this->assertEquals($this->getContentFile(), $contents);
    }

    /**
     * @test
     *
     * @return void
     */
    public function create_and_delete_folder()
    {
        $adapter = $this->adapter();
        $folder = 'folder_for_delete';
        $adapter->createDirectory($folder);
        $writeStream = fopen(self::IMAGE, 'r');
        $id = sprintf('%s/%s', $folder, $this->imageName());
        $adapter = $this->adapter();
        $adapter->writeStream($id, $writeStream);
        $items = $adapter->listContents($folder);

        $data = $items->toArray();
        $this->assertCount(1, $data);
        foreach ($data as $row) {
            $adapter->delete($row->path());
        }
        $adapter->deleteDirectory($folder);
    }
}
