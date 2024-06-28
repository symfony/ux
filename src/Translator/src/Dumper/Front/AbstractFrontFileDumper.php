<?php

namespace Symfony\UX\Translator\Dumper\Front;

abstract class AbstractFrontFileDumper
{
    protected string $dumpDir;

    public function setDumpDir(string $dumpDir): static
    {
        $this->dumpDir = $dumpDir;

        return $this;
    }
}
