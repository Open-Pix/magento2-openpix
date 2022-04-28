<?php

namespace OpenPix\Pix\Helper;
class OpenPixConfig
{
    const OPENPIX_ENV = 'development';

    public function __construct()
    {
    }

    public function getOpenPixApiUrl()
    {
        return 'http://host.docker.internal:5001';
    }

    public function getOpenPixPluginUrlScript(): string
    {
        return 'http://host.docker.internal:4444/openpix.js';
    }
}
