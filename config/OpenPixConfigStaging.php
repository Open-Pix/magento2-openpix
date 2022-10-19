<?php

namespace OpenPix\Pix\Helper;
class OpenPixConfig
{
    const OPENPIX_ENV = 'staging';

    public function __construct()
    {
    }

    public function getOpenPixApiUrl()
    {
        return 'https://api.woovi.dev';
    }

    public function getOpenPixPluginUrlScript(): string
    {
        return 'https://plugin.woovi.dev/v1/openpix-dev.js';
    }
}
