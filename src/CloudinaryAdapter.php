<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary;

use Exception;
use Cloudinary;
use Cloudinary\Api as Api;
use Cloudinary\Uploader;
use League\Flysystem\Config;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
/**
 *
 */
class CloudinaryAdapter implements AdapterInterface
{
    /**
     * @var Cloudinary\Api
     */
    protected $api;
    /**
     * Cloudinary does not suppory visibility - all is public
     */
    use NotSupportingVisibilityTrait;
    /**
     * Constructor
     * Sets configuration, and dependency Cloudinary Api.
     * @param array $options Cloudinary configuration
     * @param Api   $api    Cloudinary Api instance
     */
    public function __construct(array $options)
    {
        Cloudinary::config($options);
        $this->api = new Api;
    }
    /**
     * Write a new file.
     * Create temporary stream with content.
     * Pass to writeStream.
     *
     * @param string $path
     * @param string $contents
     * @param Config $options   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $options)
    {
        // 1. Save to temporary local file -- it will be destroyed automatically
        $tempfile = tmpfile();
        fwrite($tempfile, $contents);
        // 2. Use Cloudinary to send
        $uploaded_metadata = $this->writeStream($path, $tempfile, $options);
        return $uploaded_metadata;
    }
    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $options   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $options)
    {
        $resourceMetadata = stream_get_meta_data($resource);
              $uploaded_metadata = Uploader::upload($resourceMetadata['uri'], ['public_id' => $path]);
        return $uploaded_metadata;
    }
    /**
     * Update a file.
     * Cloudinary has no specific update method. Overwrite instead.
     *
     * @param string $path
     * @param string $contents
     * @param Config $options   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $options)
    {
        return $this->write($path, $contents, $options);
    }
    /**
     * Update a file using a stream.
     * Cloudinary has no specific update method. Overwrite instead.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $options   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $options)
    {
        return $this->writeStream($path, $resource, $options);
    }
    /**
     * Rename a file.
     * Paths without extensions.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        $pathInfo = pathinfo($path);
        if ($pathInfo['dirname'] != '.') {
            $pathRemote = $pathInfo['dirname'] . '/' . $pathInfo['filename'];
        } else {
            $pathRemote = $pathInfo['filename'];
        }
        $newpathinfo = pathinfo($newpath);
        if ($newpathinfo['dirname'] != '.') {
            $newpath_remote = $newpathinfo['dirname'] . '/' . $newpathinfo['filename'];
        } else {
            $newpath_remote = $newpathinfo['filename'];
        }
        $result = Uploader::rename($pathRemote, $newpath_remote);
        return $result['public_id'] == $newpathinfo['filename'];
    }
    /**
     * Copy a file.
     * Copy content from existing url.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $url = cloudinary_url_internal($path);
        $result = Uploader::upload($url, ['public_id' => $newpath]);
        return is_array($result) ? $result['public_id'] == $newpath : false;
    }
    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        $result = Uploader::destroy($path, ['invalidate' => true]);
        return is_array($result) ? $result['result'] == 'ok' : false;
    }
    /**
     * Delete a directory.
     * Delete Files using directory as a prefix.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        $response = $this->api->delete_resources_by_prefix($dirname);
        return true;
    }
    /**
     * Create a directory.
     * Cloudinary does not realy embrace the concept of "directories".
     * Those are more like a part of a name / public_id.
     * Just keep swimming.
     *
     * @param string $dirname directory name
     * @param Config $options
     *
     * @return array|false
     */
    public function createDir($dirname, Config $options)
    {
        return ['path' => $dirname];
    }
    /**
     * Check whether a file exists.
     * Using url to check response headers.
     * Maybe I should use api resource?
     *
     * substr(get_headers(cloudinary_url_internal($path))[0], -6 ) == '200 OK';
     * need to test that for spead
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        try {
            $this->api->resource($path);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        $contents = file_get_contents(cloudinary_url($path));
        return compact('contents', 'path');
    }
    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        try {
            $stream = fopen(cloudinary_url($path), 'r');
        } catch (Exception $e) {
            return false;
        }
        return compact('stream', 'path');
    }
    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $hasRecursive = false)
    {
        // get resources array
        $resources = ((array) $this->api->resources([
            'type' => 'upload',
            'prefix' => $directory
        ])['resources']);
        // parse resourses
        foreach ($resources as $i => $resource) {
            $resources[$i] = $this->prepareResourceMetadata($resource);
        }
        return $resources;
    }
    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        return $this->prepareResourceMetadata($this->getResource($path));
    }
    /**
     * Get Resource data
     * @param  string $path
     * @return array
     */
    public function getResource($path)
    {
        return (array) $this->api->resource($path);
    }
    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return $this->prepareSize($this->getResource($path));
    }
    /**
     * Get the mimetype of a file.
     * Actually I don't think cloudinary supports mimetypes.
     * Or I am just stupid and cannot find it.
     * This is an ugly hack.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        return $this->prepareMimetype($this->getResource($path));
    }
    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        return $this->prepareTimestamp($this->getResource($path));
    }
    /**
     * Prepare apropriate metadata for resource metadata given from cloudinary.
     * @param  array $resource
     * @return array
     */
    protected function prepareResourceMetadata($resource)
    {
        $resource['type'] = 'file';
        $resource['path'] = $resource['public_id'];
        $resource = array_merge($resource, $this->prepareSize($resource));
        $resource = array_merge($resource, $this->prepareTimestamp($resource));
        $resource = array_merge($resource, $this->prepareMimetype($resource));
        return $resource;
    }
    /**
     * prepare timestpamp response
     * @param  array $resource
     * @return array
     */
    protected function prepareMimetype($resource)
    {
        // hack
        $mimetype = $resource['resource_type'] . '/' . $resource['format'];
        $mimetype = str_replace('jpg', 'jpeg', $mimetype); // hack to a hack
        return compact('mimetype');
    }
    /**
     * prepare timestpamp response
     * @param  array $resource
     * @return array
     */
    protected function prepareTimestamp($resource)
    {
        $timestamp = strtotime($resource['created_at']);
        return compact('timestamp');
    }
    /**
     * prepare size response
     * @param array $resource
     * @return array
     */
    protected function prepareSize($resource)
    {
        $size = $resource['bytes'];
        return compact('size');
    }
}