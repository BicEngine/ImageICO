<?php

declare(strict_types=1);

namespace Bic\Image\ICO;

use Bic\Image\BMP\Metadata\BitMapInfoHeader;
use Bic\Image\ICO\Metadata\ICODirectoryEntry;

final class ICOMetadata
{
    public function __construct(
        public readonly ICODirectoryEntry $entry,
        public readonly BitMapInfoHeader $info,
    ) {
    }
}
