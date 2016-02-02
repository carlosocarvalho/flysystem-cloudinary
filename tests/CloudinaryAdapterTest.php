<?php
/**
 * Created by PhpStorm.
 * User: carlosocarvalho
 * Date: 02/02/2016
 * Time: 13:40
 */


include_once '/vendor/autoload.php';

use CarlosOCarvalho\Flysystem\Cloudinary\CloudinaryAdapter;

class CloudinaryAdapterTest extends PHPUnit_Framework_TestCase
{

  function setbeStrictAboutChangesToGlobalState(){

  }

  public function warningCount(){}
  public function testFailure(){

      //$this->assertInstanceOf('RuntimeException', new Exception);
      $this->assertInstanceOf(CloudinaryAdapter::class, new CloudinaryAdapter(['key'=>':key']));
  }
}
