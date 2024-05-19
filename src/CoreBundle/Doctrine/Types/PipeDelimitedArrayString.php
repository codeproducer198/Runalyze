<?php

namespace Runalyze\Bundle\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class PipeDelimitedArrayString extends PipeDelimitedArray
{
    /** @var string */
    const PIPE_ARRAY = 'pipe_array_str';

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!is_array($value) || empty($value)) {
            return null;
        }

        // #TSC i use the pipe_array also with string, so remove here | in a array element to avoid problems
        foreach($value as &$v) {
            if (is_string($v)) $v = str_replace('|', ' ', $v);
        }

        return implode('|', $value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || '' == trim($value)) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        return array_map(function ($v) {
            if (is_string($v) && $v !== '') {
                return $v; // #TSC to support strings don't return null
            } else {
                return null;
            }
        }, explode('|', $value));
    }

    public function getName()
    {
        return self::PIPE_ARRAY;
    }
}
