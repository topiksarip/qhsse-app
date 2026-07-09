<?php

namespace App\Core\Files;

use InvalidArgumentException;

final readonly class FileReference
{
    public function __construct(
        public string $moduleName,
        public int $referenceId,
        public string $collection = 'default',
    ) {
        if (! preg_match('/^[a-z0-9._-]+$/', $moduleName)) {
            throw new InvalidArgumentException('Invalid module name.');
        }

        if ($referenceId < 1) {
            throw new InvalidArgumentException('Reference id must be greater than zero.');
        }

        if (! preg_match('/^[a-z0-9._-]+$/', $collection)) {
            throw new InvalidArgumentException('Invalid file collection.');
        }
    }
}
