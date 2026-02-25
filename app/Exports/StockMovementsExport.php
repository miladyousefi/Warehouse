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

class StockMovementsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $movements;
    protected $locale;

    public function __construct($movements)
    {
        $this->movements = $movements;
        $this->locale = app()->getLocale() === 'tr' ? 'name_tr' : 'name_en';
    }

    public function collection()
    {
        return $this->movements;
    }

    public function headings(): array
    {
        $locale = app()->getLocale();
        if ($locale === 'tr') {
            return [
                'Tarih',
                'Ürün',
                'Tür',
                'Miktar',
                'Birim Fiyat',
                'Depo',
                'Kaynak Depo',
                'Tedarikçi',
                'Fatura No',
                'Kullanıcı',
                'Notlar',
            ];
        }

        return [
            'Date',
            'Product',
            'Type',
            'Quantity',
            'Unit Price',
            'Warehouse',
            'From Warehouse',
            'Supplier',
            'Invoice No',
            'User',
            'Notes',
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->movement_date->format('Y-m-d H:i'),
            $movement->product?->{$this->locale} ?? '-',
            $this->getTypeLabel($movement->type),
            (float) $movement->quantity,
            (float) $movement->unit_cost,
            $movement->warehouse?->{$this->locale} ?? '-',
            $movement->fromWarehouse?->{$this->locale} ?? '-',
            $movement->supplier?->name ?? '-',
            $movement->factor_number ?? '-',
            $movement->user?->name ?? '-',
            $movement->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
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
            'A:K' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    private function getTypeLabel($type): string
    {
        $locale = app()->getLocale();
        
        if ($locale === 'tr') {
            return match($type) {
                'in' => 'Giriş',
                'out' => 'Çıkış',
                'transfer' => 'Transfer',
                'adjustment' => 'Ayarlama',
                default => $type,
            };
        }

        return match($type) {
            'in' => 'Input',
            'out' => 'Output',
            'transfer' => 'Transfer',
            'adjustment' => 'Adjustment',
            default => $type,
        };
    }
}
