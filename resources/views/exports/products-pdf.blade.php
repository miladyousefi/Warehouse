<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('products.title') ?? 'Products Report' }}</title>

    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
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
            font-size: 10px;
        }

        .timestamp {
            text-align: center;
            font-size: 9px;
            margin-bottom: 15px;
            color: #666;
        }

        .summary {
            text-align: center;
            margin-bottom: 15px;
        }

        .summary p {
            margin: 3px 0;
            font-size: 10px;
        }

        /* ===== Table ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            padding: 6px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #000;
        }

        td {
            padding: 6px 5px;
            border: 1px solid #000;
            font-size: 9px;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* ===== Footer ===== */
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 9px;
            color: #666;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $locale === 'tr' ? 'Ürün Listesi' : 'Products Report' }}</h1>
            <p>The Hunger</p>
        </div>

        <div class="timestamp">
            <p>{{ $locale === 'tr' ? 'Oluşturulma Tarihi' : 'Generated Date' }}: {{ now()->format($locale === 'tr' ? 'd.m.Y H:i' : 'Y-m-d H:i') }}</p>
        </div>

        <div class="summary">
            <p>{{ $locale === 'tr' ? 'Toplam Ürün Sayısı' : 'Total Products' }}: <strong>{{ $products->count() }}</strong></p>
            <p>{{ $locale === 'tr' ? 'Toplam Tutar' : 'Total Sum' }}: <strong>{{ number_format($products->sum(fn($p) => ((float)($p->unit_price ?? 0)) * ((float)($p->stockBalances?->sum('quantity') ?? 0))), 2) }}</strong></p>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 14%;">{{ $locale === 'tr' ? 'Adı' : 'Name' }}</th>
                    <th style="width: 12%;">{{ $locale === 'tr' ? 'Kategori' : 'Category' }}</th>
                    <th style="width: 7%;">{{ $locale === 'tr' ? 'Birim' : 'Unit' }}</th>
                    <th style="width: 10%;" class="text-right">{{ $locale === 'tr' ? 'Birim Fiyat' : 'Unit Price' }}</th>
                    <th style="width: 9%;" class="text-right">{{ $locale === 'tr' ? 'Miktar' : 'Quantity' }}</th>
                    <th style="width: 10%;" class="text-right">{{ $locale === 'tr' ? 'Toplam Fiyat' : 'Total Price' }}</th>
                    <th style="width: 20%;">{{ $locale === 'tr' ? 'Hareket Özeti' : 'Movement Summary' }}</th>
                    <th style="width: 18%;">{{ $locale === 'tr' ? 'Depolar' : 'Warehouses' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $totalStock = (float)($product->stockBalances?->sum('quantity') ?? 0);
                        $unitPrice = (float)($product->unit_price ?? 0);
                        $totalPrice = $unitPrice * $totalStock;
                        $stats = $product->movement_stats ?? null;
                        if (is_array($stats) && ((int)($stats['count'] ?? 0) > 0)) {
                            $lastDate = $stats['last_date'] ?? '-';
                            if ($lastDate !== '-') {
                                try {
                                    $lastDate = \Carbon\Carbon::parse($lastDate)->format('Y-m-d H:i');
                                } catch (\Throwable $e) {
                                }
                            }
                            $movementSummary = $locale === 'tr'
                                ? sprintf(
                                    'Toplam: %d | G: %d C: %d T: %d D: %d | Son: %s',
                                    (int)($stats['count'] ?? 0),
                                    (int)($stats['in'] ?? 0),
                                    (int)($stats['out'] ?? 0),
                                    (int)($stats['transfer'] ?? 0),
                                    (int)($stats['adjustment'] ?? 0),
                                    $lastDate
                                )
                                : sprintf(
                                    'Total: %d | In: %d Out: %d Tr: %d Adj: %d | Last: %s',
                                    (int)($stats['count'] ?? 0),
                                    (int)($stats['in'] ?? 0),
                                    (int)($stats['out'] ?? 0),
                                    (int)($stats['transfer'] ?? 0),
                                    (int)($stats['adjustment'] ?? 0),
                                    $lastDate
                                );
                        } else {
                            $movementSummary = '-';
                        }

                        $nameField = $locale === 'tr' ? 'name_tr' : 'name_en';
                        $warehouseLines = collect($product->stockBalances ?? [])
                            ->map(function ($b) use ($nameField) {
                                $name = $b->warehouse?->{$nameField} ?? $b->warehouse?->name_tr ?? $b->warehouse?->name_en ?? ('#' . (string) ($b->warehouse_id ?? ''));
                                $qty = (float) ($b->quantity ?? 0);
                                if (abs($qty) < 0.0000001) {
                                    return null;
                                }
                                $qtyText = rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.');
                                return trim((string) $name) . ': ' . ($qtyText === '' ? '0' : $qtyText);
                            })
                            ->filter(fn ($v) => $v !== null && $v !== '')
                            ->values()
                            ->all();
                        $warehousesSummary = count($warehouseLines) ? implode('<br>', $warehouseLines) : '-';
                    @endphp
                    <tr>
                        <td>
                            <strong>
                                @if($locale === 'tr')
                                    {{ $product->name_tr }}
                                @else
                                    {{ $product->name_en }}
                                @endif
                            </strong>
                        </td>
                        <td>{{ $product->category?->{'name_' . substr($locale, -2)} ?? '-' }}</td>
                        <td class="text-center">{{ $product->unit?->symbol ?? '-' }}</td>
                        <td class="text-right">
                            {{ number_format($unitPrice, 2) }}
                        </td>
                        <td class="text-right">
                            {{ number_format($totalStock, 2) }}
                        </td>
                        <td class="text-right">
                            {{ number_format($totalPrice, 2) }}
                        </td>
                        <td>{{ $movementSummary }}</td>
                        <td>{!! $warehousesSummary !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center;">
                            {{ $locale === 'tr' ? 'Ürün bulunamadı' : 'No products found' }}
                        </td>
                    </tr>
                @endforelse
                
                <!-- Totals Row -->
                @if($products->count() > 0)
                    @php
                        $totals = $products->reduce(function($carry, $product) {
                            $totalStock = (float)($product->stockBalances?->sum('quantity') ?? 0);
                            $unitPrice = (float)($product->unit_price ?? 0);
                            $totalPrice = $unitPrice * $totalStock;
                            return [
                                'quantity' => $carry['quantity'] + $totalStock,
                                'totalPrice' => $carry['totalPrice'] + $totalPrice,
                            ];
                        }, ['quantity' => 0, 'totalPrice' => 0]);
                    @endphp
                    <tr style="font-weight: bold;">
                        <td colspan="4" style="text-align: right;">
                            <strong>{{ $locale === 'tr' ? 'TOPLAM' : 'TOTAL' }}</strong>
                        </td>
                        <td class="text-right">{{ number_format($totals['quantity'], 2) }}</td>
                        <td class="text-right">{{ number_format($totals['totalPrice'], 2) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>{{ $locale === 'tr' ? 'Bu belge otomatik olarak oluşturulmuştur.' : 'This document was automatically generated.' }}</p>
        </div>
    </div>
</body>
</html>
