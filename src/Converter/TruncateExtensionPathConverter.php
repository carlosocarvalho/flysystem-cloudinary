<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary\Converter;

use Cloudinary\Api\ApiResponse;

/**
 * Truncates file extension from path to convert to Cloudinary public id.
 * This is needed because Cloudinary automatically appends file extensions to the public id to create the filename, which would result in duplicate extensions: `image.jpg.jpg`
 */
class TruncateExtensionPathConverter implements IPathConverter
{
    /**
     * @inheritDoc
     */
    public function pathToId(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension
            ? substr($path, 0, -(strlen($extension) + 1))
            : $path;
    }

    /**
     * @inheritDoc
     */
    public function idToPath(ApiResponse|array $response): string
    {
        return "{$response['public_id']}.{$response['format']}";
    }
}
