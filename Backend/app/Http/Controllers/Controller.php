<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
	use AuthorizesRequests, ValidatesRequests;

	protected function respondData(mixed $data, array $meta = [], int $status = 200): JsonResponse
	{
		$payload = ['data' => $data];

		if ($meta !== []) {
			$payload['meta'] = $meta;
		}

		return response()->json($payload, $status);
	}

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
