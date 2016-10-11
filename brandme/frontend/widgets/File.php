<?php

namespace Frontend\Widgets;

class File
{

    /**
     * Verifies if an external resource actually exists
     */
    public static function is404($file)
    {
        $headers = @get_headers($file);

        return false !== strpos($headers[0], '404');
    }
}