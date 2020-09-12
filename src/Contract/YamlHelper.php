<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract;

use Symfony\Component\Yaml\Exception\ParseException;

interface YamlHelper
{
    /**
     * @throws ParseException
     */
    public function parse(string $input, int $flags = 0);
}
