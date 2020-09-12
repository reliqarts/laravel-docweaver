<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Helper;

use ReliqArts\Docweaver\Contract\YamlHelper as YamlHelperContract;
use Symfony\Component\Yaml\Yaml;

final class YamlHelper implements YamlHelperContract
{
    public function parse(string $input, int $flags = 0)
    {
        return Yaml::parse($input, $flags);
    }
}
