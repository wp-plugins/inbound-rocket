<?php

class InvalidArgumentException extends BaseInvalidArgumentException
{
    public static function fileNotExists($fileName)
    {
        return new static(sprintf('File "%s" does not exist', $fileName));
    }
}
