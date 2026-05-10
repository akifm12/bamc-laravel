<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalanceSheetExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $assets;
    protected $liabilities;
    protected $equity;
    protected $totalAssets;
    protected $totalLiabilities;
    protected $totalEquity;
    protected $totalLE;
    protected $ytdProfit;
    protected $companyName;
    protected $asOf;

    public function __construct($data, $companyName, $asOf)
    {
        $this->assets           = $data['assets'];
        $this->liabilities      = $data['liabilities'];
        $this->equity           = $data['equity'];
        $this->totalAssets      = $data['totalAssets'];
        $this->totalLiabilities = $data['totalLiabilities'];
        $this->totalEquity      = $data['totalEquity'];
        $this->totalLE          = $data['totalLE'];
        $this->ytdProfit        = $data['ytdProfit'];
        $this->companyName      = $companyName;
        $this->asOf             = $asOf;
    }

    public function collection()
    {
        $data = collect();
        $data->push([$this->companyName, '', '']);
        $data->push(["Balance Sheet as at {$this->asOf}", '', '']);
        $data->push(['', '', '']);
        $data->push(['ASSETS', '', '']);
        foreach ($this->assets as $r) {
            $data->push([$r->code, $r->name, number_format($r->balance, 2)]);
        }
        $data->push(['', 'Total Assets', number_format($this->totalAssets, 2)]);
        $data->push(['', '', '']);
        $data->push(['LIABILITIES', '', '']);
        foreach ($this->liabilities as $r) {
            $data->push([$r->code, $r->name, number_format($r->balance, 2)]);
        }
        $data->push(['', 'Total Liabilities', number_format($this->totalLiabilities, 2)]);
        $data->push(['', '', '']);
        $data->push(['EQUITY', '', '']);
        foreach ($this->equity as $r) {
            $data->push([$r->code, $r->name, number_format($r->balance, 2)]);
        }
        $data->push(['', 'YTD Net Profit / (Loss)', number_format($this->ytdProfit, 2)]);
        $data->push(['', 'Total Equity', number_format($this->totalEquity, 2)]);
        $data->push(['', '', '']);
        $data->push(['', 'TOTAL LIABILITIES + EQUITY', number_format($this->totalLE, 2)]);
        return $data;
    }

    public function headings(): array
    {
        return ['Code', 'Account', 'Balance (AED)'];
    }

    public function title(): string
    {
        return 'Balance Sheet';
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