<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $companyId = session('company_id');
        if (!$companyId) return redirect('/dashboard')->with('error', 'Please select a company.');

        $items = DB::table('items')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->where('items.company_id', $companyId)
            ->where('items.is_deleted', false)
            ->select('items.*', 'item_categories.name as category_name')
            ->orderBy('items.code')
            ->get();

        // Get current stock for each item from warehouse_stock
        foreach ($items as $item) {
            $item->current_stock = DB::table('warehouse_stock')
                ->where('item_id', $item->id)
                ->sum('quantity') ?? 0;
        }

        $totalItems    = $items->count();
        $lowStockItems = $items->filter(fn($i) => $i->current_stock <= $i->reorder_point && $i->reorder_point > 0)->count();
        $totalValue    = $items->sum(fn($i) => $i->current_stock * $i->cost_price);

        return view('inventory.index', compact('items', 'totalItems', 'lowStockItems', 'totalValue'));
    }

    public function createItem()
    {
        $companyId  = session('company_id');
        $categories = DB::table('item_categories')
            ->where('company_id', $companyId)
            ->get();

        $accounts = DB::table('accounts')
            ->where('company_id', $companyId)
            ->whereRaw("is_active = true")
            ->orderBy('code')
            ->get()
            ->groupBy('account_type');

        $taxCodes = DB::table('tax_codes')
            ->where('company_id', $companyId)
            ->get();

        return view('inventory.create_item', compact('categories', 'accounts', 'taxCodes'));
    }

    public function storeItem(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
        ]);

        DB::table('items')->insert([
            'company_id'           => $companyId,
            'category_id'          => $request->category_id ?: null,
            'code'                 => $request->code,
            'name'                 => $request->name,
            'name_arabic'          => $request->name_arabic,
            'description'          => $request->description,
            'item_type'            => $request->item_type ?? 'product',
            'unit_of_measure'      => $request->unit_of_measure ?? 'unit',
            'standard_price'       => $request->standard_price ?? 0,
            'cost_price'           => $request->cost_price ?? 0,
            'purchase_price'       => $request->purchase_price ?? 0,
            'sales_account_id'     => $request->sales_account_id ?: null,
            'purchase_account_id'  => $request->purchase_account_id ?: null,
            'inventory_account_id' => $request->inventory_account_id ?: null,
            'cogs_account_id'      => $request->cogs_account_id ?: null,
            'tax_code_id'          => $request->tax_code_id ?: null,
            'is_inventory_tracked' => $request->has('is_inventory_tracked'),
            'costing_method'       => $request->costing_method ?? 'average',
            'reorder_point'        => $request->reorder_point ?? 0,
            'reorder_qty'          => $request->reorder_qty ?? 0,
            'min_stock_level'      => $request->min_stock_level ?? 0,
            'is_active'            => true,
            'is_sellable'          => $request->has('is_sellable'),
            'is_purchasable'       => $request->has('is_purchasable'),
            'notes'                => $request->notes,
            'is_deleted'           => false,
            'created_by_id'        => auth()->user()->id,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return redirect('/inventory')->with('success', 'Item created successfully.');
    }

    public function showItem($id)
    {
        $companyId = session('company_id');
        $item      = DB::table('items')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->where('items.id', $id)
            ->where('items.company_id', $companyId)
            ->select('items.*', 'item_categories.name as category_name')
            ->first();

        if (!$item) abort(404);

        // warehouse_stock has no company_id — filter by item_id only
        $stockByWarehouse = DB::table('warehouse_stock')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'warehouse_stock.warehouse_id')
            ->where('warehouse_stock.item_id', $id)
            ->select('warehouse_stock.*', 'warehouses.name as warehouse_name')
            ->get();

        $movements = DB::table('stock_movements')
            ->where('item_id', $id)
            ->where('company_id', $companyId)
            ->orderBy('movement_date', 'desc')
            ->limit(20)
            ->get();

        $totalStock = $stockByWarehouse->sum('quantity');

        return view('inventory.show_item', compact('item', 'stockByWarehouse', 'movements', 'totalStock'));
    }

    public function movements(Request $request)
    {
        $companyId = session('company_id');
        $dateFrom  = $request->get('date_from', date('Y-01-01'));
        $dateTo    = $request->get('date_to',   date('Y-m-d'));

        $movements = DB::table('stock_movements')
            ->leftJoin('items', 'items.id', '=', 'stock_movements.item_id')
            ->where('stock_movements.company_id', $companyId)
            ->whereBetween('stock_movements.movement_date', [$dateFrom, $dateTo])
            ->select('stock_movements.*', 'items.name as item_name', 'items.code as item_code')
            ->orderBy('stock_movements.movement_date', 'desc')
            ->get();

        $items = DB::table('items')
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereRaw("is_inventory_tracked = true")
            ->orderBy('name')
            ->get();

        $warehouses = DB::table('warehouses')
            ->where('company_id', $companyId)
            ->get();

        return view('inventory.movements', compact('movements', 'items', 'warehouses', 'dateFrom', 'dateTo'));
    }

    public function recordMovement(Request $request)
    {
        $companyId = session('company_id');
        $request->validate([
            'item_id'       => 'required|integer',
            'movement_type' => 'required|string',
            'quantity'      => 'required|numeric|min:0.01',
            'movement_date' => 'required|date',
        ]);

        $qty  = floatval($request->quantity);
        $item = DB::table('items')->find($request->item_id);

        $isInbound  = in_array($request->movement_type, ['purchase', 'adjustment_in', 'transfer_in', 'opening']);
        $signedQty  = $isInbound ? $qty : -$qty;
        $unitCost   = floatval($request->unit_cost ?? $item->cost_price ?? 0);

        DB::transaction(function () use ($companyId, $request, $qty, $signedQty, $unitCost, $item) {
            // Record stock movement — no created_by_id column
            DB::table('stock_movements')->insert([
                'company_id'    => $companyId,
                'item_id'       => $request->item_id,
                'warehouse_id'  => $request->warehouse_id ?: null,
                'movement_type' => $request->movement_type,
                'movement_date' => $request->movement_date,
                'quantity'      => $signedQty,
                'unit_cost'     => $unitCost,
                'total_cost'    => $qty * $unitCost,
                'reference'     => $request->reference,
                'notes'         => $request->notes,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Update warehouse_stock — no company_id, no quantity_on_hand, no created_at
            $existing = DB::table('warehouse_stock')
                ->where('item_id', $request->item_id)
                ->where('warehouse_id', $request->warehouse_id ?: null)
                ->first();

            if ($existing) {
                DB::table('warehouse_stock')
                    ->where('id', $existing->id)
                    ->update([
                        'quantity'     => max(0, $existing->quantity + $signedQty),
                        'average_cost' => $unitCost > 0 ? $unitCost : $existing->average_cost,
                        'updated_at'   => now(),
                    ]);
            } else {
                // warehouse_stock has no created_at column
                DB::table('warehouse_stock')->insert([
                    'item_id'      => $request->item_id,
                    'warehouse_id' => $request->warehouse_id ?: null,
                    'quantity'     => max(0, $signedQty),
                    'average_cost' => $unitCost,
                    'updated_at'   => now(),
                ]);
            }
        });

        return redirect('/inventory/movements')->with('success', 'Stock movement recorded.');
    }
}