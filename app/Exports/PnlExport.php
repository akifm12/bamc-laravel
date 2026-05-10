<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PnlExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $revenue;
    protected $expenses;
    protected $totalRevenue;
    protected $totalExpenses;
    protected $netProfit;
    protected $companyName;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($revenue, $expenses, $totalRevenue, $totalExpenses, $netProfit, $companyName, $dateFrom, $dateTo)
    {
        $this->revenue       = $revenue;
        $this->expenses      = $expenses;
        $this->totalRevenue  = $totalRevenue;
        $this->totalExpenses = $totalExpenses;
        $this->netProfit     = $netProfit;
        $this->companyName   = $companyName;
        $this->dateFrom      = $dateFrom;
        $this->dateTo        = $dateTo;
    }

    public function collection()
    {
        $data = collect();
        $data->push([$this->companyName, '', '']);
        $data->push(["Profit & Loss: {$this->dateFrom} to {$this->dateTo}", '', '']);
        $data->push(['', '', '']);
        $data->push(['REVENUE', '', '']);
        foreach ($this->revenue as $r) {
            $data->push([$r->code, $r->name, number_format($r->balance, 2)]);
        }
        $data->push(['', 'Total Revenue', number_format($this->totalRevenue, 2)]);
        $data->push(['', '', '']);
        $data->push(['EXPENSES', '', '']);
        foreach ($this->expenses as $r) {
            $data->push([$r->code, $r->name, number_format(abs($r->balance), 2)]);
        }
        $data->push(['', 'Total Expenses', number_format($this->totalExpenses, 2)]);
        $data->push(['', '', '']);
        $data->push(['', 'NET PROFIT / (LOSS)', number_format($this->netProfit, 2)]);
        return $data;
    }

    public function headings(): array
    {
        return ['Code', 'Account', 'Amount (AED)'];
    }

    public function title(): string
    {
        return 'Profit and Loss';
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
        return ['A' => 15, 'B' => 40, 'C' => 18];
    }
}