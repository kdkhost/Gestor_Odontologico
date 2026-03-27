<?php

namespace App\Http\Controllers;

use App\Services\ViaCepService;
use Illuminate\Http\JsonResponse;

class ViaCepController extends Controller
{
    public function __construct(private readonly ViaCepService $viaCep) {}

    public function show(string $cep): JsonResponse
    {
        $address = $this->viaCep->lookup($cep);

        if (! $address) {
            return response()->json(['message' => 'CEP não encontrado.'], 404);
        }

        return response()->json($address);
    }
}
