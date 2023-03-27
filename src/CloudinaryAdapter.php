<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary;

use CarlosOCarvalho\Flysystem\Cloudinary\Converter\AsIsPathConverter;
use CarlosOCarvalho\Flysystem\Cloudinary\Converter\IPathConverter;
use Cloudinary\Cloudinary;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\Media;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Exception\NotFound;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\InvalidVisibilityProvided;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use Exception;
use Throwable;


class CloudinaryAdapter implements FilesystemAdapter
{
    protected AdminApi $adminApi;
    protected UploadApi $uploadApi;

    protected Cloudinary $driver;

    /**
     * Cloudinary does not support visibility - all is public
     */
    private string $visibility;

    private IPathConverter $converter;

    private const EXTRA_METADATA_FIELDS = [
        'version',
        'width',
        'height',
        'url',
        'secure_url',
        'next_cursor',
    ];

    /**
     * Sets configuration, and dependency Cloudinary Api.
     *
     * @param array|null $options Cloudinary configuration
     */
    public function __construct(array $options = null, IPathConverter $converter = null)
    {
        Configuration::instance($options);

        $this->adminApi = new AdminApi($options);
        $this->uploadApi = new UploadApi($options);
        $this->converter = $converter ?: new AsIsPathConverter();
    }

    /**
     * Write a new file.
     * Create temporary stream with content.
     * Pass to writeStream.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return void false on failure file meta data on success
     * @throws \Cloudinary\Api\Exception\ApiError
     */
    public function write(string $path, string $contents, Config $config): void
    {
        // 1. Save to temporary local file -- it will be destroyed automatically
        $tempFile = tmpfile();
        fwrite($tempFile, $contents);

        // 2. Use Cloudinary to send
        $this->writeStream($path, $tempFile, $config);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $contents
     * @param Config   $config Config object
     *
     * @return void false on failure file meta data on success
     * @throws \Cloudinary\Api\Exception\ApiError
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $public_id = $config->get('public_id', $this->converter->pathToId($path));
        $resource_type = $config->get('resource_type', 'auto');
        $upload_options = $config->get('upload_options', []);
        $resourceMetadata = stream_get_meta_data($contents);

        $this->uploadApi->upload(
            $resourceMetadata['uri'],
            [
                ...$upload_options,
                'public_id'     => $public_id,
                'resource_type' => $resource_type,
            ]
        );
    }

    /**
     * Copy a file.
     * Copy content from existing url.
     *
     * @param string                   $source
     * @param string                   $destination
     * @param \League\Flysystem\Config $config
     *
     * @return void
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $url = $this->getUrl($source);
            $this->uploadApi->upload($url, ['public_id' => $this->converter->pathToId($destination)]);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * Move a file.
     *
     * @param string                   $source
     * @param string                   $destination
     * @param \League\Flysystem\Config $config
     *
     * @return void
     */
    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->uploadApi->rename($this->converter->pathToId($source), $this->converter->pathToId($destination));
        } catch (NotFound $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return void
     */
    public function delete(string $path): void
    {
        try {
            $result = $this->uploadApi->destroy($this->converter->pathToId($path), ['invalidate' => true])['result'];

            if ($result !== 'ok') {
                throw new UnableToDeleteFile('file not found');
            }
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, '', $exception);
        }
    }

    /**
     * @param string $path
     *
     * @return bool
     * @throws UnableToCheckExistence|\Cloudinary\Api\Exception\ApiError
     *
     */
    public function directoryExists(string $path): bool
    {
        $folders = [];
        $needle = substr($path, 0, strrpos($path, '/'));

        $response = null;
        do {
            $response = (array)$this->adminApi->subFolders($needle, [
                'max_results' => 4,
                'next_cursor' => $response['next_cursor'] ?? null,
            ]);

            $folders = array_merge($folders, $response['folders']);
        } while (array_key_exists('next_cursor', $response) && !is_null($response['next_cursor']));

        $folders_found = array_filter(
            $folders,
            static function ($e) use ($path) {
                return $e['path'] === $path;
            }
        );

        return count($folders_found);
    }

    /**
     * Delete a directory.
     * Delete Files using directory as a prefix.
     *
     * @param string $path
     *
     * @return void
     * @throws \Cloudinary\Api\Exception\ApiError
     */
    public function deleteDirectory(string $path): void
    {
        $this->adminApi->deleteFolder($path);
    }

    /**
     * Create a directory.
     * Cloudinary does not really embrace the concept of "directories".
     * Those are more like a part of a name / public_id.
     * Just keep swimming.
     *
     * @param string $path directory name
     * @param Config $config
     *
     * @return void
     * @throws \Cloudinary\Api\Exception\ApiError
     */
    public function createDirectory(string $path, Config $config): void
    {
        $this->adminApi->createFolder($path);
    }

    /**
     * Check whether a file exists.
     * Using url to check response headers.
     * Maybe I should use api resource?
     *
     * substr(get_headers(cloudinary_url_internal($path))[0], -6 ) == '200 OK';
     * need to test that for speed
     *
     * @param string $path
     *
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        try {
            $this->adminApi->asset($this->converter->pathToId($path));
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
     * @return string
     */
    public function read(string $path): string
    {
        $contents = file_get_contents(Media::fromParams($this->converter->pathToId($path)));
        return (string)$contents;
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream(string $path): bool|array
    {
        return fopen(Media::fromParams($this->converter->pathToId($path)), 'rb');
    }

    /**
     * List contents of a directory.
     *
     * @param string $path
     * @param bool   $deep
     *
     * @return array
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $resources = [];

        // get resources array
        $response = null;
        do {
            $response = (array)$this->adminApi->assets([
                'type'        => 'upload',
                'prefix'      => $this->converter->pathToId($path),
                'max_results' => 500,
                'next_cursor' => $response['next_cursor'] ?? null,
            ]);
            $resources = array_merge($resources, $response['resources']);
        } while (array_key_exists('next_cursor', $response));

        // parse resources
        foreach ($resources as $i => $resource) {
            //$resources[$i] = $this->prepareResourceMetadata($resource);
            yield $this->mapToFileAttributes($resource);
        }
        return $resources;
    }

    /**
     * Get Resource data
     *
     * @param string $path
     *
     * @return array
     */
    public function getResource(string $path): array
    {
        return (array)$this->adminApi->asset($this->converter->pathToId($path));
    }

    /**
     * Get all the metadata of a file or directory.
     *
     * @param string $path
     *
     * @return \League\Flysystem\FileAttributes
     */
    public function fileSize(string $path): FileAttributes
    {
        return $this->fetchFileMetadata($path, StorageAttributes::ATTRIBUTE_FILE_SIZE);
    }

    /**
     * @param mixed $visibility
     *
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
        return $this->fetchFileMetadata($path, StorageAttributes::ATTRIBUTE_VISIBILITY);
    }

    /**
     * Get the mimetype of a file.
     * Actually I don't think cloudinary supports mimetypes.
     * Or I am just stupid and cannot find it.
     * This is an ugly hack.
     *
     * @param string $path
     *
     * @return \League\Flysystem\FileAttributes
     */
    public function mimetype(string $path): FileAttributes
    {
        return $this->fetchFileMetadata($path, StorageAttributes::ATTRIBUTE_MIME_TYPE);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return \League\Flysystem\FileAttributes
     */
    public function lastModified(string $path): FileAttributes
    {
        return $this->fetchFileMetadata($path, StorageAttributes::ATTRIBUTE_LAST_MODIFIED);
    }

    /**
     * Get the URL of an image with optional transformation parameters
     *
     * @param string $path
     *
     * @return string
     */
    public function getUrl(string $path): string
    {
        try {
            $response = $this->adminApi->asset($this->converter->pathToId($path));
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
     *
     * @return FileAttributes
     */
    private function fetchFileMetadata(string $path, string $type): FileAttributes
    {
        try {
            $result = $this->getResource($path);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, '', $exception);
        }

        $attributes = $this->mapToFileAttributes($result);

        if (!$attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type);
        }

        return $attributes;
    }

    /**
     * mapToFileAttributes map all attributes
     *
     * @param array $resource
     *
     * @return FileAttributes
     */
    private function mapToFileAttributes(array $resource): FileAttributes
    {
        return new FileAttributes(
            $this->converter->idToPath($resource),
            (int)$resource['bytes'],
            'public',
            (int)strtotime($resource['created_at']),
            sprintf('%s/%s', $resource['resource_type'], $resource['format']),
            $this->extractExtraMetadata($resource)
        );
    }

    /**
     * Undocumented function
     *
     * @param array $metadata
     *
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
