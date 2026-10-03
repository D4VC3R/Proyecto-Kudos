<?php

return [
	'paths' => ['api/*', 'sanctum/csrf-cookie'],

	'allowed_methods' => ['*'],

	// Solo los orígenes listados en FRONTEND_URL (separados por comas). Sin valor por defecto: si falta la variable, no se permite ningún origen.
	'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URL', ''))))),

	'allowed_origins_patterns' => [],

	'allowed_headers' => ['*'],

	'exposed_headers' => [],

	'max_age' => 86400,

	// La API autentica con tokens Bearer, no con cookies: no hace falta enviar credenciales entre orígenes.
	'supports_credentials' => false,
];