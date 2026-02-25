<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('stockMovements.title') ?? 'Stock Movement Report' }}</title>

    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        /* ===== Header ===== */
        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .header p {
            margin: 0;
            font-size: 11px;
        }

        .timestamp {
            text-align: center;
            font-size: 10px;
            margin-bottom: 15px;
        }

        .summary {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* ===== Professional Rounded Table ===== */

        .table-wrapper {
            border: 2px solid #000;
            border-radius: 12px;
            padding: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            border-bottom: 2px solid #000;
        }

        th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        td {
            padding: 7px;
            border-bottom: 1px solid #ccc;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* ===== No Data Box ===== */

        .no-data {
            text-align: center;
            padding: 20px;
            border: 2px solid #000;
            border-radius: 12px;
            margin-top: 20px;
        }

        /* ===== Footer ===== */

        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #000;
            padding-top: 8px;
        }
    </style>
</head>

<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>{{ __('stockMovements.title') ?? 'Stock Movement Report' }}</h1>
        <p>The Hunger</p>
    </div>

    <!-- Timestamp -->
    <div class="timestamp">
        Generated: {{ now()->format('d.m.Y H:i:s') }}
    </div>

    @if($movements->count() > 0)

        <!-- Summary -->
        <div class="summary">
            Total Records: {{ $movements->count() }}
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th class="text-center">Type</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th>Warehouse</th>
                    <th>Supplier</th>
                    <th>Invoice No</th>
                </tr>
                </thead>

                <tbody>
                @foreach($movements as $movement)
                    <tr>
                        <td>{{ $movement->movement_date->format('d.m.Y H:i') }}</td>
                        <td>{{ $movement->product?->{($locale === 'tr' ? 'name_tr' : 'name_en')} ?? '-' }}</td>
                        <td class="text-center">
                            @if($movement->type === 'in')
                                Input
                            @elseif($movement->type === 'out')
                                Output
                            @elseif($movement->type === 'transfer')
                                Transfer
                            @else
                                Adjustment
                            @endif
                        </td>
                        <td class="text-right">
                            {{ number_format($movement->quantity, 2, ',', '.') }}
                        </td>
                        <td class="text-right">
                            {{ number_format($movement->unit_cost ?? 0, 2, ',', '.') }}
                        </td>
                        <td>{{ $movement->warehouse?->{($locale === 'tr' ? 'name_tr' : 'name_en')} ?? '-' }}</td>
                        <td>{{ $movement->supplier?->name ?? '-' }}</td>
                        <td>{{ $movement->factor_number ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    @else

        <div class="no-data">
            No movements found.
        </div>

    @endif

    <!-- Footer -->
    <div class="footer">
        {{ now()->format('Y-m-d H:i:s') }}
    </div>

</div>
</body>
</html>