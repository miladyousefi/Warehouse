<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $products;
    protected $locale;
    protected $totalsData = [];

    public function __construct($products)
    {
        $this->products = $products;
        $this->locale = app()->getLocale() === 'tr' ? 'name_tr' : 'name_en';
        
        // Calculate totals
        $this->calculateTotals();
    }

    private function calculateTotals()
    {
        $totalProducts = 0;
        $totalSum = 0;

        foreach ($this->products as $product) {
            $totalStock = (float) ($product->stockBalances?->sum('quantity') ?? 0);
            $unitPrice = (float) ($product->unit_price ?? 0);
            $totalPrice = $unitPrice * $totalStock;
            
            $totalProducts += 1;
            $totalSum += $totalPrice;
        }

        $this->totalsData = [
            'totalProducts' => $totalProducts,
            'totalSum' => $totalSum,
        ];
    }

    public function collection()
    {
        return $this->products;
    }

    public function headings(): array
    {
        $locale = app()->getLocale();
        if ($locale === 'tr') {
            return [
                'Adı (Türkçe)',
                'Adı (İngilizce)',
                'Kategori',
                'Birim',
                'Birim Fiyat',
                'Miktar',
                'Toplam Fiyat',
                'Durum',
            ];
        }

        return [
            'Name (Turkish)',
            'Name (English)',
            'Category',
            'Unit',
            'Unit Price',
            'Quantity',
            'Total Price',
            'Status',
        ];
    }

    public function map($product): array
    {
        $totalStock = (float) ($product->stockBalances?->sum('quantity') ?? 0);
        $unitPrice = (float) ($product->unit_price ?? 0);
        $totalPrice = $unitPrice * $totalStock;

        return [
            $product->name_tr,
            $product->name_en,
            $product->category?->name_tr ?? '-',
            $product->unit?->symbol ?? '-',
            $unitPrice,
            $totalStock,
            $totalPrice,
            $product->is_active ? ($this->locale === 'name_tr' ? 'Aktif' : 'Active') : ($this->locale === 'name_tr' ? 'Pasif' : 'Inactive'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6'], // Blue
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
            // Data rows
            'A:H' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Totals row (last row)
            $lastRow => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981'], // Green
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            'afterSheet' => function ($event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow() + 1;

                $locale = app()->getLocale();
                $totalLabel = $locale === 'tr' ? 'TOPLAM' : 'TOTAL';
                $totalProductsLabel = $locale === 'tr' ? 'Ürün Sayısı:' : 'Products:';

                // Add totals row
                $sheet->setCellValue('A' . $lastRow, $totalLabel);
                $sheet->setCellValue('F' . $lastRow, $totalProductsLabel . ' ' . $this->totalsData['totalProducts']);
                $sheet->setCellValue('G' . $lastRow, $this->totalsData['totalSum']);

                // Style totals row
                $sheet->getStyle('A' . $lastRow . ':H' . $lastRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'], // Green
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }
}
