<?php

namespace App\Catalog\Exceptions;

use RuntimeException;

/** La fuente externa no responde a tiempo, falla o no está configurada (importación bajo demanda, F1-07). */
final class CatalogUnavailableException extends RuntimeException {}
