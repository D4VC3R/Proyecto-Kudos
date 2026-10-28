<?php

namespace App\Services\Media;

use App\Contracts\Media\HostResolverInterface;

/**
 * Resolución DNS real con gethostbyname() (F1-04: extraída de RemoteImageDownloader::assertSafeUrl()).
 */
class DnsHostResolver implements HostResolverInterface
{
    public function resolve(string $host): string
    {
        return gethostbyname($host);
    }
}
