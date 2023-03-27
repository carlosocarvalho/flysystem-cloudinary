<?php

namespace CarlosOCarvalho\Flysystem\Cloudinary\Converter;

use Cloudinary\Api\ApiResponse;


/**
 * Used to convert from path to Cloudinary public id and vice-versa
 *
 * Implementation must be non-destructive, e.g.
 *
 * ```
 * $converter->idToPath($converter->pathToId($path)) === $path
 * ```
 */
interface IPathConverter
{
    /**
     * Converts path to public id
     *
     * @param string $path
     *
     * @return string
     */
    public function pathToId(string $path): string;

    /**
     * Converts id to path
     *
     * @param ApiResponse|array $response
     *
     * @return string
     */
    public function idToPath(ApiResponse|array $response): string;
}
