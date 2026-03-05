<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StockMovementsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $locale = app()->getLocale();

                $dataStartRow = 2;
                $dataEndRow = max(1, $this->movements->count() + 1);

                if ($dataEndRow >= $dataStartRow) {
                    $sheet->getStyle("D{$dataStartRow}:D{$dataEndRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');

                    $sheet->getStyle("E{$dataStartRow}:E{$dataEndRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');

                    $sheet->getStyle("D{$dataStartRow}:E{$dataEndRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $summaryStart = $this->movements->count() + 3;

                $sheet->setCellValue("A{$summaryStart}", $locale === 'tr' ? 'Rapor Özeti' : 'Report Summary');
                $sheet->mergeCells("A{$summaryStart}:C{$summaryStart}");

                $sheet->setCellValue("A" . ($summaryStart + 1), $locale === 'tr' ? 'Toplam Hareket' : 'Total Movements');
                $sheet->setCellValue("B" . ($summaryStart + 1), $this->movements->count());

                $sheet->setCellValue("A" . ($summaryStart + 2), $locale === 'tr' ? 'Toplam Miktar' : 'Total Quantity');
                $sheet->setCellValue("B" . ($summaryStart + 2), (float) $this->movements->sum('quantity'));

                $sheet->setCellValue("A" . ($summaryStart + 3), $locale === 'tr' ? 'Toplam Tutar' : 'Total Amount');
                $sheet->setCellValue(
                    "B" . ($summaryStart + 3),
                    (float) $this->movements->sum(fn ($movement) => (float) $movement->quantity * (float) ($movement->unit_cost ?? 0))
                );

                $sheet->setCellValue("D" . ($summaryStart + 1), $locale === 'tr' ? 'Giriş Sayısı' : 'Input Count');
                $sheet->setCellValue("E" . ($summaryStart + 1), $this->movements->where('type', 'in')->count());

                $sheet->setCellValue("D" . ($summaryStart + 2), $locale === 'tr' ? 'Çıkış Sayısı' : 'Output Count');
                $sheet->setCellValue("E" . ($summaryStart + 2), $this->movements->where('type', 'out')->count());

                $sheet->setCellValue("D" . ($summaryStart + 3), $locale === 'tr' ? 'Transfer Sayısı' : 'Transfer Count');
                $sheet->setCellValue("E" . ($summaryStart + 3), $this->movements->where('type', 'transfer')->count());

                $sheet->setCellValue("D" . ($summaryStart + 4), $locale === 'tr' ? 'Düzeltme Sayısı' : 'Adjustment Count');
                $sheet->setCellValue("E" . ($summaryStart + 4), $this->movements->where('type', 'adjustment')->count());

                $sheet->getStyle("A{$summaryStart}:E" . ($summaryStart + 4))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A{$summaryStart}:E{$summaryStart}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F2937'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A" . ($summaryStart + 1) . ":D" . ($summaryStart + 4))->getFont()->setBold(true);
                $sheet->getStyle("B" . ($summaryStart + 1))->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("B" . ($summaryStart + 2) . ":B" . ($summaryStart + 3))->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("B" . ($summaryStart + 1) . ":E" . ($summaryStart + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("B" . ($summaryStart + 3))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            },
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
