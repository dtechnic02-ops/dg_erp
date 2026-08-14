<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CountryRequest;
use App\Models\Country;
use App\Services\PlatformAuthorizationService;

class CountryController extends Controller
{
    public function __construct(private readonly PlatformAuthorizationService $authorization) {}

    public function index()
    {
        $this->authorizeManage();

        return view('admin.countries.index', [
            'countries' => Country::query()->orderBy('name')->paginate(25),
        ]);
    }

    public function store(CountryRequest $request)
    {
        $this->authorizeManage();
        Country::create($request->validated());

        return back()->with('success', 'Country created successfully.');
    }

    public function update(CountryRequest $request, Country $country)
    {
        $this->authorizeManage();
        $country->update($request->validated());

        return back()->with('success', 'Country updated successfully.');
    }

    private function authorizeManage(): void
    {
        abort_unless(
            $this->authorization->can(auth()->user(), 'platform_settings_manage'),
            403,
            'You do not have permission to manage countries.'
        );
    }
}
