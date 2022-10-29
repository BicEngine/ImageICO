<?php

declare(strict_types=1);

namespace Bic\Image\Ico;

use Bic\Image\Bmp\Internal\BitMapInfoHeader;
use Bic\Image\Ico\Internal\IcoDirectory;

final class IcoMetadata
{
    public function __construct(
        public readonly IcoDirectory $directory,
        public readonly BitMapInfoHeader $info,
    ) {
    }
}
