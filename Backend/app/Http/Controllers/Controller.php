<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
/**
 * Controlador base para la API.
 * Proporciona métodos comunes para formatear respuestas JSON de error, datos, mutaciones y listas.
 */

abstract class Controller extends BaseController
{
	use AuthorizesRequests, ValidatesRequests;

	/**
	 * Formatea una respuesta JSON de error.
	 *
	 * @param string $code Código de error específico de la aplicación.
	 * @param string $message Mensaje de error descriptivo.
	 * @param array|null $details Información adicional sobre el error (opcional).
	 * @param int $status Código HTTP para la respuesta (por defecto 400).
	 * @return JsonResponse Respuesta JSON con la estructura de error.
	 */
	protected function respondError(string $code, string $message, ?array $details = null, int $status = 400): JsonResponse
	{
		$payload = [
			'error' => [
				'code' => $code,
				'message' => $message,
			],
		];

		if ($details !== null && $details !== []) {
			$payload['error']['details'] = $details;
		}

		return response()->json($payload, $status);
	}

/**
	 * Formatea una respuesta JSON con datos.
	 *
	 * @param mixed $data Los datos a incluir en la respuesta.
	 * @param array $meta Información adicional sobre los datos (opcional).
	 * @param int $status Código HTTP para la respuesta (por defecto 200).
	 * @return JsonResponse Respuesta JSON con la estructura de datos.
	 */
	protected function respondData(mixed $data, array $meta = [], int $status = 200): JsonResponse
	{
		$payload = ['data' => $data];

		if ($meta !== []) {
			$payload['meta'] = $meta;
		}

		return response()->json($payload, $status);
	}

	/**
	 * Formatea una respuesta JSON para mutaciones (creación, actualización, eliminación).
	 *
	 * @param string $message Mensaje descriptivo de la operación realizada.
	 * @param mixed $data Los datos resultantes de la mutación (opcional).
	 * @param array $meta Información adicional sobre la mutación (opcional).
	 * @param int $status Código HTTP para la respuesta (por defecto 200).
	 * @return JsonResponse Respuesta JSON con la estructura de mutación.
	 */
	protected function respondMutation(string $message, mixed $data = null, array $meta = [], int $status = 200): JsonResponse
	{
		$payload = ['message' => $message];

		if ($data !== null) {
			$payload['data'] = $data;
		}

		if ($meta !== []) {
			$payload['meta'] = $meta;
		}

		return response()->json($payload, $status);
	}

	/**
	 * Formatea una respuesta JSON para listas de recursos, incluyendo paginación.
	 *
	 * @param mixed $data Los datos de la lista a incluir en la respuesta.
	 * @param array $meta Información adicional sobre la lista (por ejemplo, detalles de paginación).
	 * @param array $links Enlaces relacionados con la lista (por ejemplo, enlaces de paginación).
	 * @param int $status Código HTTP para la respuesta (por defecto 200).
	 * @return JsonResponse Respuesta JSON con la estructura de lista.
	 */
	protected function respondList(mixed $data, array $meta = [], array $links = [], int $status = 200): JsonResponse
	{
		$payload = ['data' => $data];

		if ($meta !== []) {
			$payload['meta'] = $meta;
		}

		if ($links !== []) {
			$payload['links'] = $links;
		}

		return response()->json($payload, $status);
	}
}
