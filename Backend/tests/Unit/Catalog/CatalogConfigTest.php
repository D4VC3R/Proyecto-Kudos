<?php

namespace Tests\Unit\Catalog;

use Tests\TestCase;

class CatalogConfigTest extends TestCase
{
    public function test_usa_los_valores_por_defecto_si_las_variables_estan_vacias(): void
    {
        $names = ['CATALOG_USER_AGENT', 'CATALOG_IMPORT_LIMIT', 'TMDB_API_TOKEN'];
        $previous = [];

        foreach ($names as $name) {
            $previous[$name] = [
                'env' => array_key_exists($name, $_ENV) ? $_ENV[$name] : null,
                'server' => array_key_exists($name, $_SERVER) ? $_SERVER[$name] : null,
            ];
            $_ENV[$name] = '';
            $_SERVER[$name] = '';
        }

        try {
            // Con env('X', 'defecto') el valor sería '' (la variable existe, aunque vacía); por eso se usa "?:".
            $config = require config_path('catalog.php');

            $this->assertSame('KudosBot/1.0 (+https://kudos.dcerdan.es)', $config['user_agent']);
            $this->assertSame(50, $config['import_limit']);
            $this->assertNull($config['providers']['tmdb']['token']);
        } finally {
            foreach ($names as $name) {
                foreach (['env' => '_ENV', 'server' => '_SERVER'] as $key => $global) {
                    if ($previous[$name][$key] === null) {
                        unset($GLOBALS[$global][$name]);
                    } else {
                        $GLOBALS[$global][$name] = $previous[$name][$key];
                    }
                }
            }
        }
    }
}
