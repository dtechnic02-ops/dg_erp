<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\Account;
use App\Models\SalesInvoice;
use App\Models\InvoicePayment;

class InvoicePaymentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([

            'sales_invoice_id' =>
                'required',

            'account_id' =>
                'required',

            'amount' =>
                'required|numeric|min:1',

        ]);

        DB::transaction(function ()
        use ($request) {

            $invoice = SalesInvoice::where(
                'company_id',
                auth()->user()->company_id
            )
            ->findOrFail(
                $request->sales_invoice_id
            );

            // BLOCK OVERPAY
            if (
                $request->amount >
                $invoice->due_amount
            ) {

                throw new \Exception(
                    'Payment exceeds due amount.'
                );
            }

            // SAVE PAYMENT
            InvoicePayment::create([

                'company_id' =>
                    auth()->user()->company_id,

                'sales_invoice_id' =>
                    $invoice->id,

                'customer_id' =>
                    $invoice->customer_id,

                'account_id' =>
                    $request->account_id,

                'payment_date' =>
                    $request->payment_date
                    ?? now(),

                'amount' =>
                    $request->amount,

                'note' =>
                    $request->note,

                'created_by' =>
                    auth()->id(),

            ]);

            // UPDATE ACCOUNT BALANCE
            $account = Account::findOrFail(
                $request->account_id
            );

            $account->increment(
                'current_balance',
                $request->amount
            );

            // TOTAL PAID
            $paid = $invoice
                ->payments()
                ->sum('amount');

            // DUE
            $due =
                $invoice->grand_total -
                $paid;

            // STATUS
            if ($due <= 0) {

                $status = 'paid';

            } elseif ($paid > 0) {

                $status = 'partial';

            } else {

                $status = 'unpaid';
            }

            // UPDATE INVOICE
            $invoice->update([

                'paid_amount' =>
                    $paid,

                'due_amount' =>
                    $due,

                'payment_status' =>
                    $status,

            ]);
        });

        return back()->with(
            'success',
            'Payment Added Successfully'
        );
    }
}