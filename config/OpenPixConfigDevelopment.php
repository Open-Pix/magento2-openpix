<?php

// @woovi/do-not-merge

namespace OpenPix\Pix\Helper;
class OpenPixConfig
{
    const OPENPIX_ENV = 'development';
    const OPENPIX_PUBLIC_KEY_BASE64 = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHZk1BMEdDU3FHU0liM0RRRUJBUVVBQTRHTkFEQ0JpUUtCZ1FEVDdWYStxb3pvT2NYUStjSHJWNk85RVE0TgpnZVhvY1ZwRFBBWkpTZVJsbEVlQVVha051MURqY3FweDFmb1l5aEZxRTM3TkNWYzRtK0hvTC9nN1k3VDMyZVJ4CjhpandxMjdoY0ZjL0RFc01ISWdVU0U4cGdPbi96a3ZadXdNb256MkVjdy85NzZzTlUzNnpKOXhMUE53dURnSysKb2dUb0RQTmNkaWtRdi9STHFRSURBUUFCCi0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQo=';

    public function __construct()
    {
    }

    public function getOpenPixApiUrl()
    {
        return 'http://host.docker.internal:5001';
    }

    public function getOpenPixPlatformUrl()
    {
        return 'http://localhost:8103';
    }

    public function getOpenPixPluginUrlScript(): string
    {
        return 'http://localhost:4444/openpix.js';
    }
}
