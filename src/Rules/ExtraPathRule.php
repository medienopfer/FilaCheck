<?php

namespace Filacheck\Rules;

interface ExtraPathRule extends Rule
{
    /**
     * @return string[] Relative paths that should also be scanned
     */
    public function extraScanPaths(): array;
}
