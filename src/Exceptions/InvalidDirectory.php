<?php

namespace ReliQArts\Docweaver\Exceptions;

use Exception;

class InvalidDirectory extends Exception
{
    /**
     * @param string    $directory directory
     * @param int       $code      user defined exception code
     * @param Exception $previous  previous exception if nested exception
     */
    public function __construct(string $directory, int $code = 0, Exception $previous = null)
    {
        $message = "Directory ({$directory}) is invalid.";

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }
}
