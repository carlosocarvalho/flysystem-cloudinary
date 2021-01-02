<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary;

use Exception;
use Cloudinary as ClDriver;
use Cloudinary\Api as Api;
use Cloudinary\Uploader;
use League\Flysystem\Config;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FileAttributes;
// use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperationFailed;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use Throwable;


/**
 *
 */
class CloudinaryAdapter implements FilesystemAdapter
{
    /**
     * @var Cloudinary\Api
     */
    protected $api;

    private $visibility;

    private const EXTRA_METADATA_FIELDS = [
        'version',
        'width',
        'height',
        'url',
        'secure_url',
        'next_cursor',
    ];
    /**
     * Cloudinary does not suppory visibility - all is public
     */

    /**
     * Constructor
     * Sets configuration, and dependency Cloudinary Api.
     * @param array $options Cloudinary configuration
     * @param Api   $api    Cloudinary Api instance
     */
    public function __construct(array $options)
    {
        ClDriver::config($options);
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
    public function write($path, $contents, Config $options): void
    {
        // 1. Save to temporary local file -- it will be destroyed automatically
        $tempFile = tmpfile();
        fwrite($tempFile, $contents);
        // 2. Use Cloudinary to send
        $this->writeStream($path, $tempFile, $options);
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
    public function writeStream($path, $resource, Config $options): void
    {
        $public_id = $options->get('public_id', $path);
        $resource_type = $options->get('resource_type', 'auto');
        $resourceMetadata = stream_get_meta_data($resource);
        $uploadedMetadata = Uploader::upload(
            $resourceMetadata['uri'],
            [
                'public_id' => $public_id,
                'resource_type' => $resource_type,
            ]
        );
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
    public function copy(string $source, string $destination, Config $config): void
    {

        try {
            $url = cloudinary_url_internal($source);
            Uploader::upload($url, ['public_id' => $destination]);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }
    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (FilesystemOperationFailed $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }
    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path): void
    {
        try {
            $result = Uploader::destroy($path, ['invalidate' => true])['result'];
            if ($result != 'ok')
                throw new UnableToDeleteFile('file not found');
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, '', $exception);
        }
    }
    /**
     * Delete a directory.
     * Delete Files using directory as a prefix.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDirectory($dirname): void
    {
        $this->api->delete_folder($dirname);
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
    public function createDirectory($dirname, Config $options): void
    {
        $this->api->create_folder($dirname, (array) $options);
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
    public function fileExists($path): bool
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
    public function read($path): string
    {
        $contents = file_get_contents(cloudinary_url($path));
        return (string) $contents;
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
        return fopen(cloudinary_url($path), 'r');
    }
    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents(string $directory, bool $hasRecursive): iterable
    {
        $resources = [];

        // get resources array
        $response = null;
        do {
            $response = (array) $this->api->resources([
                'type' => 'upload',
                'prefix' => $directory,
                'max_results' => 500,
                'next_cursor' => isset($response['next_cursor']) ? $response['next_cursor'] : null,
            ]);
            $resources = array_merge($resources, $response['resources']);
        } while (array_key_exists('next_cursor', $response));

        // parse resourses
        foreach ($resources as $i => $resource) {
            //$resources[$i] = $this->prepareResourceMetadata($resource);
            yield  $this->mapToObject($resource);
            //

        }
        return $resources;
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
    public function fileSize($path): FileAttributes
    {
        return $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_FILE_SIZE);
    }

    /**
     * @param mixed $visibility
     * @throws InvalidVisibilityProvided
     * @throws FilesystemException
     */
    public function setVisibility(string $path, $visibility): void
    {
    }

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function visibility(string $path): FileAttributes
    {
        return $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_VISIBILITY);
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
    public function mimetype($path): FileAttributes
    {
        return $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_MIME_TYPE);
    }
    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function lastModified(string $path): FileAttributes
    {
        return $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_LAST_MODIFIED);
    }
    
    /**
     * fetchFileMetadata get all attributes
     *
     * @param string $path
     * @param string $type
     * @return FileAttributes
     */
    private function fetchFileMetadata(string $path, string $type): FileAttributes
    {
        try {
            $result =  $this->getResource($path);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, '', $exception);
        }
        $attributes = $this->mapToObject($result, $path);

        if (!$attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type, '');
        }
        return $attributes;
    }

    /**
     * mapToObject map all attributes
     *
     * @param [type] $resource
     * @return FileAttributes
     */
    private function mapToObject($resource): FileAttributes
    {   
        return new FileAttributes(
            $resource['public_id'],
            (int) $resource['bytes'],
            'public',
            (int) strtotime($resource['created_at']),
            (string) sprintf('%s/%s', $resource['resource_type'] , $resource['format']),
            $this->extractExtraMetadata((array) $resource)
        );
    }
    
    /**
     * Undocumented function
     *
     * @param array $metadata
     * @return array
     */
    private function extractExtraMetadata(array $metadata): array
    {
        $extracted = [];

        foreach (static::EXTRA_METADATA_FIELDS as $field) {
            if (isset($metadata[$field]) && $metadata[$field] !== '') {
                $extracted[$field] = $metadata[$field];
            }
        }

        return $extracted;
    }
}
