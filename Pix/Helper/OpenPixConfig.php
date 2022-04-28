<?php

namespace OpenPix\Pix\Helper;
class OpenPixConfig
{
    const OPENPIX_ENV = 'production';

    public function __construct()
    {
    }

    public function getOpenPixApiUrl()
    {
        return 'https://api.openpix.com.br';
    }

    public function getOpenPixPluginUrlScript(): string
    {
        return 'https://plugin.openpix.com.br/v1/openpix.js';
    }
}
