<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class TrialBalanceExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $rows;
    protected $companyName;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($rows, $companyName, $dateFrom, $dateTo)
    {
        $this->rows        = $rows;
        $this->companyName = $companyName;
        $this->dateFrom    = $dateFrom;
        $this->dateTo      = $dateTo;
    }

    public function collection()
    {
        $data = collect();
        $data->push([$this->companyName, '', '', '', '', '']);
        $data->push(["Trial Balance: {$this->dateFrom} to {$this->dateTo}", '', '', '', '', '']);
        $data->push(['', '', '', '', '', '']);

        foreach ($this->rows as $r) {
            $data->push([
                $r->code,
                $r->name,
                $r->account_type,
                number_format($r->total_debit, 2),
                number_format($r->total_credit, 2),
                number_format($r->total_debit - $r->total_credit, 2),
            ]);
        }

        $data->push(['', '', 'TOTAL',
            number_format(collect($this->rows)->sum('total_debit'), 2),
            number_format(collect($this->rows)->sum('total_credit'), 2),
            '',
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ['Code', 'Account Name', 'Type', 'Debit', 'Credit', 'Balance'];
    }

    public function title(): string
    {
        return 'Trial Balance';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '006400']]],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F3F4F6']]],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 15, 'B' => 40, 'C' => 15, 'D' => 18, 'E' => 18, 'F' => 18];
    }
}