<?php

namespace App\Contracts\Media;

/**
 * Resuelve el host de una URL remota a una IP. RemoteImageDownloader lo usa para su protección anti-SSRF;
 * en los tests se falsea para no depender de la red (docs/backend/testing.md §5).
 */
interface HostResolverInterface
{
    /** IPv4 del host o, si no se puede resolver, el propio host (misma semántica que gethostbyname()). */
    public function resolve(string $host): string;
}
