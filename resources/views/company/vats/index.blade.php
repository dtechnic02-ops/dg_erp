@extends('company.layout')

@section('content')

<div class="container-fluid">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2>🧾 VAT & Tax Management</h2>
    </div>

    <!-- ADD VAT FORM -->

    <div style="
        background:#1e293b;
        padding:20px;
        border-radius:10px;
        margin-bottom:20px;
    ">

        <form action="{{ route('company.vats.store') }}" method="POST">

            @csrf

            <div style="
                display:grid;
                grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
                gap:15px;
            ">

                <div>
                    <label>VAT Name</label>

                    <input type="text"
                           name="name"
                           placeholder="Example: VAT 13%"
                           required
                           style="
                                width:100%;
                                padding:10px;
                                border:none;
                                border-radius:6px;
                                background:#0f172a;
                                color:white;
                           ">
                </div>

                <div>
                    <label>Rate (%)</label>

                    <input type="number"
                           step="0.01"
                           name="rate"
                           required
                           style="
                                width:100%;
                                padding:10px;
                                border:none;
                                border-radius:6px;
                                background:#0f172a;
                                color:white;
                           ">
                </div>

                <div>
                    <label>Default VAT</label>

                    <select name="is_default"
                            style="
                                width:100%;
                                padding:10px;
                                border:none;
                                border-radius:6px;
                                background:#0f172a;
                                color:white;
                            ">

                        <option value="0">No</option>
                        <option value="1">Yes</option>

                    </select>
                </div>

                <div style="display:flex;align-items:end;">

                    <button type="submit"
                            style="
                                width:100%;
                                background:#3b82f6;
                                color:white;
                                border:none;
                                padding:10px;
                                border-radius:6px;
                                cursor:pointer;
                            ">

                        Save VAT

                    </button>

                </div>

            </div>

        </form>

    </div>

    <!-- VAT TABLE -->

    <div style="
        background:#1e293b;
        padding:20px;
        border-radius:10px;
    ">

        <div style="overflow-x:auto;">

            <table style="
                width:100%;
                border-collapse:collapse;
            ">

                <thead>

                    <tr style="background:#0f172a;">

                        <th style="padding:12px;">#</th>
                        <th style="padding:12px;">VAT Name</th>
                        <th style="padding:12px;">Rate</th>
                        <th style="padding:12px;">Default</th>
                        <th style="padding:12px;">Action</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($vats as $key => $vat)

                    <tr style="border-bottom:1px solid #334155;">

                        <td style="padding:12px;">
                            {{ $key + 1 }}
                        </td>

                        <td style="padding:12px;">
                            {{ $vat->name }}
                        </td>

                        <td style="padding:12px;">
                            {{ $vat->rate }}%
                        </td>

                        <td style="padding:12px;">

                            @if($vat->is_default)
                                ✅ Yes
                            @else
                                ❌ No
                            @endif

                        </td>

                        <td style="padding:12px;">

                            <form action="{{ route('company.vats.delete', $vat->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Delete this VAT?')">

                                @csrf

                                <button type="submit"
                                        style="
                                            background:#ef4444;
                                            color:white;
                                            border:none;
                                            padding:8px 12px;
                                            border-radius:6px;
                                            cursor:pointer;
                                        ">

                                    Delete

                                </button>

                            </form>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="5"
                            style="
                                padding:20px;
                                text-align:center;
                            ">

                            No VAT Found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection