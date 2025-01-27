<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary;

use Exception;
use Cloudinary\Cloudinary;
use Cloudinary\Api as Api;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\Media;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Exception\NotFound;
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
use League\Flysystem\UnableToSetVisibility;
use Throwable;

/**
 *
 */
class CloudinaryAdapter implements FilesystemAdapter
{
    /**
     * @var Cloudinary\Api
     */
    protected $adminApi;
    protected $uploadApi;

    /**
     * @var Cloudinary\Cloudinary
     */
    protected $driver;

    private $visibility;

    /** 
     * 
     * @var \Cloudinary\Asset\AssetType::IMAGE|\Cloudinary\Asset\AssetType::VIDEO
     */
    static $resourceType = 'image';


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
    public function __construct(array $options = null)
    {
        Configuration::instance($options);
        $this->adminApi = new AdminApi($options);
        $this->uploadApi = new UploadApi($options);
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
    public function write(string $path, string $contents, Config $options): void
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
        $format = $options->get('format', null);
        $resourceMetadata = stream_get_meta_data($resource);

        $uploadedMetadata = $this->uploadApi->upload(
            $resourceMetadata['uri'],
            [
                'public_id' => $public_id,
                'resource_type' => $resource_type,
                'format' => $format
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
            // $url = cloudinary_url_internal($source);
            $url = $this->getUrl($source);
            $this->uploadApi->upload($url, ['public_id' => $destination]);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }
    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->uploadApi->rename($source, $destination);
        } catch (NotFound $exception) {
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
            $result = $this->uploadApi->destroy($path, ['invalidate' => true])['result'];
            if ($result != 'ok')
                throw new UnableToDeleteFile('file not found');
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, '', $exception);
        }
    }

    /**
     * @throws FilesystemException
     * @throws UnableToCheckExistence
     *
     * @param string $path
     *
     * @return bool
     */
    public function directoryExists(string $path): bool
    {
        $folders = [];
        $needle = substr($path, 0, strripos($path, '/'));

        $response = null;
        do {
            $response = (array) $this->adminApi->subFolders($needle, [
                'max_results' => 4,
                'next_cursor' => isset($response['next_cursor']) ? $response['next_cursor'] : null,
            ]);

            $folders = array_merge($folders, $response['folders']);
        } while (array_key_exists('next_cursor', $response) && !is_null($response['next_cursor']));

        $folders_found = array_filter(
            $folders,
            function ($e) use ($path) {
                return $e['path'] == $path;
            }
        );

        return count($folders_found);
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
        $this->adminApi->deleteFolder($dirname);
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
        $this->adminApi->createFolder($dirname, (array) $options);
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
            $this->adminApi->asset($path);
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
        $contents = file_get_contents(Media::fromParams($path));
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
        return fopen(Media::fromParams($path), 'r');
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
            $response = (array) $this->adminApi->assets([
               'type' => 'list',
                'prefix' => $directory,
                'resource_type' => CloudinaryAdapter::$resourceType,
                'max_results' => 500,
                'next_cursor' => isset($response['next_cursor']) ? $response['next_cursor'] : null,
            ]);
            $resources = array_merge($resources, $response['resources']);
        } while (array_key_exists('next_cursor', $response));

        // parse resourses
        foreach ($resources as $i => $resource) {
            //$resources[$i] = $this->prepareResourceMetadata($resource);
            yield  $this->mapToFileAttributes($resource);
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
        return (array) $this->adminApi->asset($path);
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
    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Adapter does not support visibility controls.');
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
     * Get the URL of an image with optional transformation parameters
     *
     * @param  string|array $path
     * @return string
     */
    public function getUrl(string $path): string
    {
        try {
            $response = $this->adminApi->asset($path);
            return $response['secure_url'];
        } catch (NotFound $exception) {
            return '';
        }
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
        $attributes = $this->mapToFileAttributes($result, $path);

        if (!$attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type, '');
        }

        return $attributes;
    }

    /**
     * mapToFileAttributes map all attributes
     *
     * @param [type] $resource
     * @return FileAttributes
     */
    private function mapToFileAttributes($resource): FileAttributes
    {
        return new FileAttributes(
            $resource['public_id'],
            (int) $resource['bytes'],
            'public',
            (int) strtotime($resource['created_at']),
            (string) sprintf('%s/%s', $resource['resource_type'], $resource['format']),
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
