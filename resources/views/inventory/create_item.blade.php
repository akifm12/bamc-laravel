@extends('layouts.app')

@section('title', 'New Item')

@section('content')

<div class="max-w-3xl">

<form method="POST" action="/inventory/items" class="space-y-4">
    @csrf

    <!-- Basic Info -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Item Details</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Item Code *</label>
                <input type="text" name="code" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Item Name *</label>
                <input type="text" name="name" required
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Arabic Name</label>
                <input type="text" name="name_arabic" dir="rtl"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Category</label>
                <select name="category_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— Select —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Item Type</label>
                <select name="item_type" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="product">Product</option>
                    <option value="service">Service</option>
                    <option value="consumable">Consumable</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Unit of Measure</label>
                <select name="unit_of_measure" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="unit">Unit</option>
                    <option value="kg">KG</option>
                    <option value="litre">Litre</option>
                    <option value="metre">Metre</option>
                    <option value="box">Box</option>
                    <option value="carton">Carton</option>
                    <option value="piece">Piece</option>
                    <option value="set">Set</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="text-xs text-gray-500 block mb-1">Description</label>
                <textarea name="description" rows="2"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm"></textarea>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_sellable" id="is_sellable" checked class="rounded">
                    <label for="is_sellable" class="text-sm text-gray-600">Sellable</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_purchasable" id="is_purchasable" checked class="rounded">
                    <label for="is_purchasable" class="text-sm text-gray-600">Purchasable</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_inventory_tracked" id="is_inventory_tracked" class="rounded">
                    <label for="is_inventory_tracked" class="text-sm text-gray-600">Track Inventory</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Pricing</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Cost Price (AED)</label>
                <input type="number" name="cost_price" value="0" step="0.01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Sale Price (AED)</label>
                <input type="number" name="standard_price" value="0" step="0.01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Purchase Price (AED)</label>
                <input type="number" name="purchase_price" value="0" step="0.01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Costing Method</label>
                <select name="costing_method" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="average">Average Cost</option>
                    <option value="fifo">FIFO</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Tax Code</label>
                <select name="tax_code_id" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— None —</option>
                    @foreach($taxCodes as $tc)
                        <option value="{{ $tc->id }}">{{ $tc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Stock Levels -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">Stock Levels</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Reorder Point</label>
                <input type="number" name="reorder_point" value="0" step="0.01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Reorder Quantity</label>
                <input type="number" name="reorder_qty" value="0" step="0.01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Min Stock Level</label>
                <input type="number" name="min_stock_level" value="0" step="0.01"
                    class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
        </div>
    </div>

    <!-- GL Accounts -->
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">GL Accounts</h3>
        <div class="grid grid-cols-2 gap-4">
            @foreach([
                ['sales_account_id',     'Sales Account',     'REVENUE'],
                ['purchase_account_id',  'Purchase Account',  'EXPENSE'],
                ['inventory_account_id', 'Inventory Account', 'ASSET'],
                ['cogs_account_id',      'COGS Account',      'EXPENSE'],
            ] as [$field, $label, $type])
            <div>
                <label class="text-xs text-gray-500 block mb-1">{{ $label }}</label>
                <select name="{{ $field }}" class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm">
                    <option value="">— Select —</option>
                    @foreach($accounts->get($type, collect()) as $a)
                        <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            @endforeach
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-green-700 text-white text-sm px-5 py-2 rounded hover:bg-green-800">
            Create Item
        </button>
        <a href="/inventory" class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancel</a>
    </div>

</form>
</div>

@endsection