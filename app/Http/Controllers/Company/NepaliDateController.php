<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\NepaliDateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class NepaliDateController extends Controller
{
    public function __invoke(Request $request, NepaliDateService $nepaliDateService): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $company = Company::with('countryMaster')->findOrFail(auth()->user()->company_id);

        try {
            $bsDate = $nepaliDateService->adToBsForCompany($company, $validated['date']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'date' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'applicable' => $bsDate !== null,
            'bs_date' => $bsDate,
        ]);
    }
}
