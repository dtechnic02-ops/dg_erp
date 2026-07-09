<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyClientController extends Controller
{
    public function profile()
    {
        $company = auth()->user()->company;

        return view('company.profile', compact('company'));
    }

    public function edit()
    {
        $company = auth()->user()->company;

        return view('company.profile', compact('company'));
    }

    public function update(Request $request)
{
    $company = auth()->user()->company;

    /**
     * 🔥 VALIDATION
     */

    $request->validate([

        'company_name' =>
            'required',

        'email' =>
            'nullable|email',

        /**
         * 🔥 ONLY JPG PNG
         */

        'logo' =>
            'nullable|image|mimes:jpg,jpeg,png|max:1024',

        'signature' =>
            'nullable|image|mimes:jpg,jpeg,png|max:1024',

    ]);

    /**
     * 🔥 COMPANY FOLDER
     */

    $folder = public_path(
        'companies/' .
        $company->id
    );

    /**
     * 🔥 CREATE FOLDER
     */

    if (!file_exists($folder))
    {
        mkdir(
            $folder,
            0777,
            true
        );
    }

    /**
     * 🔥 UPDATE DATA
     */

    $data = [

        'company_name' =>
            $request->company_name,

        'email' =>
            $request->email,

        'mobile' =>
            $request->mobile,

        'telephone' =>
            $request->telephone,

        'fax_no' =>
            $request->fax_no,

        'website' =>
            $request->website,

        'country' =>
            $request->country,

        'language' =>
            $request->language,

        'pan_number' =>
            $request->pan_number,

        'vat_number' =>
            $request->vat_number,

        'address' =>
            $request->address,

        'address_line_2' =>
            $request->address_line_2,

    ];

    /**
     * 🔥 LOGO
     */

    if ($request->hasFile('logo'))
    {
        /**
         * DELETE OLD LOGO
         */

        if (
            !empty($company->logo_path)
        )
        {
            $oldLogo = public_path(
                'companies/' .
                $company->id .
                '/' .
                $company->logo_path
            );

            if (file_exists($oldLogo))
            {
                unlink($oldLogo);
            }
        }

        /**
         * NEW FILE
         */

        $file =
            $request->file('logo');

        $filename =
            'logo_' .
            time() .
            '.' .
            $file
            ->getClientOriginalExtension();

        /**
         * MOVE FILE
         */

        $file->move(
            $folder,
            $filename
        );

        /**
         * SAVE DB
         */

        $data['logo_path'] =
            $filename;
    }

    /**
     * 🔥 SIGNATURE
     */

    if ($request->hasFile('signature'))
    {
        /**
         * DELETE OLD SIGNATURE
         */

        if (
            !empty(
                $company->signature_path
            )
        )
        {
            $oldSignature = public_path(
                'companies/' .
                $company->id .
                '/' .
                $company->signature_path
            );

            if (
                file_exists($oldSignature)
            )
            {
                unlink($oldSignature);
            }
        }

        /**
         * NEW FILE
         */

        $file =
            $request->file('signature');

        $filename =
            'signature_' .
            time() .
            '.' .
            $file
            ->getClientOriginalExtension();

        /**
         * MOVE FILE
         */

        $file->move(
            $folder,
            $filename
        );

        /**
         * SAVE DB
         */

        $data['signature_path'] =
            $filename;
    }

    /**
     * 🔥 UPDATE
     */

    $company->update($data);

    return back()->with(

        'success',

        'Profile Updated Successfully'

    );
}
}