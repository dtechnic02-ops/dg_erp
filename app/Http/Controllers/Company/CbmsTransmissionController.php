<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CbmsTransmission;
use App\Services\Cbms\CbmsQueueService;
use App\Services\Cbms\CbmsReconciliationService;
use App\Services\Cbms\CbmsTransmissionAuthorizationService;

class CbmsTransmissionController extends Controller
{
    public function index(CbmsTransmissionAuthorizationService $authorization)
    {
        $company = auth()->user()->company;
        $authorization->authorizeView(auth()->user(), $company);
        $query = CbmsTransmission::with(['transmittable.financialYear'])->where('company_id', $company->id);
        $summary = (clone $query)->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $transmissions = $query->latest()->paginate(25);
        return view('company.settings.cbms-transmissions', compact('company', 'transmissions', 'summary'));
    }

    public function show(CbmsTransmission $transmission, CbmsTransmissionAuthorizationService $authorization)
    {
        $company = auth()->user()->company;
        $authorization->authorizeView(auth()->user(), $company);
        abort_unless((int) $transmission->company_id === (int) $company->id, 404);
        $transmission->load(['transmittable.financialYear', 'attempts']);
        return view('company.settings.cbms-transmission-show', compact('company', 'transmission'));
    }

    public function queue(CbmsTransmission $transmission, CbmsTransmissionAuthorizationService $authorization, CbmsQueueService $queue)
    {
        $company = auth()->user()->company;
        $authorization->authorizeOperate(auth()->user(), $company);
        abort_unless((int) $transmission->company_id === (int) $company->id, 404);
        $updated = $queue->queue($transmission->transmittable);
        $queued = $updated->status === CbmsTransmission::STATUS_QUEUED;
        return back()->with($queued ? 'success' : 'error', $queued ? 'CBMS transmission queued safely.' : 'CBMS document is not ready to queue.');
    }

    public function retry(CbmsTransmission $transmission, CbmsTransmissionAuthorizationService $authorization, CbmsQueueService $queue)
    {
        $company = auth()->user()->company;
        $authorization->authorizeOperate(auth()->user(), $company);
        abort_unless((int) $transmission->company_id === (int) $company->id, 404);
        $queue->retry($transmission);
        return back()->with('success', 'Retry queued safely.');
    }

    public function reconcile(CbmsTransmission $transmission, CbmsTransmissionAuthorizationService $authorization, CbmsReconciliationService $reconciliation)
    {
        $company = auth()->user()->company;
        $authorization->authorizeOperate(auth()->user(), $company);
        abort_unless((int) $transmission->company_id === (int) $company->id, 404);
        $updated = $reconciliation->reconcile($transmission);
        $verified = $updated->status === CbmsTransmission::STATUS_SUBMITTED;
        return back()->with($verified ? 'success' : 'error', $verified ? 'Duplicate reconciled by verified exact match.' : 'Awaiting authoritative external verification.');
    }
}
