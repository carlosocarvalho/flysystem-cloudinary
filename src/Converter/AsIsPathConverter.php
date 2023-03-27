<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary\Converter;


use Cloudinary\Api\ApiResponse;

/**
 * Leaves path and public id as is - default implementation
 */
class AsIsPathConverter implements IPathConverter
{
    /**
     * @inheritDoc
     */
    public function pathToId(string $path): string
    {
        return $path;
    }

    /**
     * @inheritDoc
     */
    public function idToPath(ApiResponse|array $response): string
    {
        return $response['public_id'];
    }
}
