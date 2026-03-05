<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $locale === 'tr' ? 'Stok Hareket Raporu' : 'Stock Movement Report' }}</title>

    <style>
        @page {
            size: A4;
            margin: 9mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
            color: #1f2937;
            background: #fff;
        }

        .container {
            width: 100%;
            margin: 0;
        }

        .header {
            margin-bottom: 8px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
        }

        .title {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 3px;
            color: #111827;
        }

        .subtitle {
            margin: 0;
            font-size: 9px;
            color: #4b5563;
        }

        .meta {
            margin-top: 4px;
            font-size: 8px;
            color: #6b7280;
        }

        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px;
            margin-bottom: 7px;
        }

        .summary-card {
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 6px;
            background: #fff;
            vertical-align: top;
        }

        .summary-label {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .summary-value {
            font-size: 11px;
            font-weight: 700;
            color: #111827;
        }

        .type-summary {
            margin-bottom: 6px;
            padding: 5px 8px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            background: #fff;
        }

        .table-wrapper {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: 5px 4px;
            text-align: left;
            font-size: 8px;
            font-weight: 700;
            color: #111827;
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
        }

        tbody td {
            padding: 4px 4px;
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
        }

        tbody tr:nth-child(even) {
            background: #fcfcfc;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 1px 6px;
            font-size: 7px;
            font-weight: 700;
            border: 1px solid transparent;
            line-height: 1.4;
        }

        .badge-in {
            color: #1f2937;
            background: #ffffff;
            border-color: #d1d5db;
        }

        .badge-out {
            color: #1f2937;
            background: #ffffff;
            border-color: #d1d5db;
        }

        .badge-transfer {
            color: #1f2937;
            background: #ffffff;
            border-color: #d1d5db;
        }

        .badge-adjustment {
            color: #1f2937;
            background: #ffffff;
            border-color: #d1d5db;
        }

        .no-data {
            text-align: center;
            padding: 14px;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            color: #475569;
        }

        .footer {
            margin-top: 8px;
            font-size: 8px;
            color: #64748b;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }

        .w-date {
            width: 16%;
        }

        .w-product {
            width: 19%;
        }

        .w-type {
            width: 10%;
        }

        .w-qty,
        .w-price {
            width: 10%;
        }

        .w-warehouse {
            width: 15%;
        }

        .w-supplier {
            width: 10%;
        }

        .w-factor {
            width: 10%;
        }
    </style>
</head>

<body>
@php
    $isTr = $locale === 'tr';
    $totalMovements = $movements->count();
    $totalQuantity = (float) $movements->sum('quantity');
    $totalPrice = (float) $movements->sum(fn($movement) => (float) $movement->quantity * (float) ($movement->unit_cost ?? 0));
    $inCount = $movements->where('type', 'in')->count();
    $outCount = $movements->where('type', 'out')->count();
    $transferCount = $movements->where('type', 'transfer')->count();
    $adjustmentCount = $movements->where('type', 'adjustment')->count();
@endphp
<div class="container">
    <div class="header">
        <h1 class="title">{{ $isTr ? 'Stok Hareket Raporu' : 'Stock Movement Report' }}</h1>
        <p class="subtitle">{{ $isTr ? 'Filtrelenmiş stok hareketleri dökümü' : 'Filtered stock movement statement' }}</p>
        <div class="meta">
            {{ $isTr ? 'Oluşturulma Tarihi' : 'Generated At' }}: {{ now()->format('d.m.Y H:i:s') }}
        </div>
    </div>

    @if($movements->count() > 0)
        <table class="summary-table">
            <tr>
                <td class="summary-card">
                    <div class="summary-label">{{ $isTr ? 'Toplam Hareket' : 'Total Movements' }}</div>
                    <div class="summary-value">{{ number_format($totalMovements, 0, ',', '.') }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-label">{{ $isTr ? 'Toplam Miktar' : 'Total Quantity' }}</div>
                    <div class="summary-value">{{ number_format($totalQuantity, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-label">{{ $isTr ? 'Toplam Tutar' : 'Total Amount' }}</div>
                    <div class="summary-value">{{ number_format($totalPrice, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-label">{{ $isTr ? 'Benzersiz Ürün' : 'Unique Products' }}</div>
                    <div class="summary-value">{{ number_format($movements->pluck('product_id')->filter()->unique()->count(), 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>

        <div class="type-summary">
            {{ $isTr ? 'Tür Dağılımı' : 'Type Breakdown' }}:
            {{ $isTr ? 'Giriş' : 'Input' }} {{ $inCount }} |
            {{ $isTr ? 'Çıkış' : 'Output' }} {{ $outCount }} |
            {{ $isTr ? 'Transfer' : 'Transfer' }} {{ $transferCount }} |
            {{ $isTr ? 'Düzeltme' : 'Adjustment' }} {{ $adjustmentCount }}
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th class="w-date">{{ $isTr ? 'Tarih' : 'Date' }}</th>
                    <th class="w-product">{{ $isTr ? 'Ürün' : 'Product' }}</th>
                    <th class="w-type text-center">{{ $isTr ? 'Tür' : 'Type' }}</th>
                    <th class="w-qty text-right">{{ $isTr ? 'Miktar' : 'Quantity' }}</th>
                    <th class="w-price text-right">{{ $isTr ? 'Birim Fiyat' : 'Unit Price' }}</th>
                    <th class="w-warehouse">{{ $isTr ? 'Depo' : 'Warehouse' }}</th>
                    <th class="w-supplier">{{ $isTr ? 'Tedarikçi' : 'Supplier' }}</th>
                    <th class="w-factor">{{ $isTr ? 'Fatura No' : 'Invoice No' }}</th>
                </tr>
                </thead>

                <tbody>
                @foreach($movements as $movement)
                    <tr>
                        <td>{{ $movement->movement_date->format('d.m.Y H:i') }}</td>
                        <td>{{ $movement->product?->{($locale === 'tr' ? 'name_tr' : 'name_en')} ?? '-' }}</td>
                        <td class="text-center">
                            @if($movement->type === 'in')
                                <span class="badge badge-in">{{ $isTr ? 'Giriş' : 'Input' }}</span>
                            @elseif($movement->type === 'out')
                                <span class="badge badge-out">{{ $isTr ? 'Çıkış' : 'Output' }}</span>
                            @elseif($movement->type === 'transfer')
                                <span class="badge badge-transfer">{{ $isTr ? 'Transfer' : 'Transfer' }}</span>
                            @else
                                <span class="badge badge-adjustment">{{ $isTr ? 'Düzeltme' : 'Adjustment' }}</span>
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

        <div class="no-data">{{ $isTr ? 'Hareket bulunamadı.' : 'No movements found.' }}</div>

    @endif

    <div class="footer">
        {{ $isTr ? 'Rapor Tarihi' : 'Report Date' }}: {{ now()->format('Y-m-d H:i:s') }}
    </div>

</div>
</body>
</html>
