<?php 


function stream_with_contents(string $contents)
{
    $stream = fopen(tempnam(sys_get_temp_dir(), 'COC'), 'w+b');
    fwrite($stream, $contents);
    rewind($stream);

    return $stream;
}
