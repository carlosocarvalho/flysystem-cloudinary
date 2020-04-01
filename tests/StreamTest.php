<?php

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter as Adapter;
use CarlosOCarvalho\Flysystem\Cloudinary\Test\ApplicationCase;
use League\Flysystem\Filesystem;


class StreamTest extends ApplicationCase
{


    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$image_id = md5(strtotime('now')); //sprintf('uploads/%s.png', md5(strtotime('now')));

    }

    /**
     * @return void
     */
    public function test_stream_file()
    {
        $adapter = self::$adapter;
        $id = sprintf('uploads/-stream-%s', self::$image_id);
        $up = $adapter->write($id, $this->getContentFile());
        $this->assertTrue($up);
        $input = $adapter->readStream($id);
        $d = stream_get_contents($input);
        $this->assertTrue($d == $this->getContentFile());
        $adapter->delete($id);
    }

    /**
     * @ex
     * 
     * @return void
     */
    public function test_failure_stream_file()
    {
        try {
            $adapter = self::$adapter;
            $id = sprintf('uploads/-stream-%s', self::$image_id);
            $stream = $adapter->readStream($id);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     *
     * @return void
     */
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
}
