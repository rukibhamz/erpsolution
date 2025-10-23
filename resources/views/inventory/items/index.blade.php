@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Inventory Management</h1>
                    <p class="mt-2 text-gray-600">Manage your inventory items and stock levels</p>
                </div>
                <div>
                    <a href="{{ route('admin.inventory.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        New Item
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="Item code, name, or supplier">
                    </div>
                    
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category_id" id="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Status</option>
                            <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                            <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                            <option value="discontinued" {{ request('status') === 'discontinued' ? 'selected' : '' }}>Discontinued</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="stock_level" class="block text-sm font-medium text-gray-700">Stock Level</label>
                        <select name="stock_level" id="stock_level" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Levels</option>
                            <option value="low_stock" {{ request('stock_level') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                            <option value="out_of_stock" {{ request('stock_level') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                            <option value="needs_reorder" {{ request('stock_level') === 'needs_reorder' ? 'selected' : '' }}>Needs Reorder</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inventory Items Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:p-6">
                @if($inventoryItems->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pricing</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($inventoryItems as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $item->item_code }}</div>
                                        <div class="text-sm text-gray-500">{{ $item->name }}</div>
                                        @if($item->description)
                                            <div class="text-sm text-gray-400">{{ Str::limit($item->description, 50) }}</div>
                                        @endif
                                        @if($item->supplier)
                                            <div class="text-sm text-gray-400">Supplier: {{ $item->supplier }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white" 
                                              style="background-color: {{ $item->category->color }}">
                                            {{ $item->category->name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $item->current_stock }} {{ $item->unit_of_measure }}</div>
                                        <div class="text-sm text-gray-500">Min: {{ $item->minimum_stock }}</div>
                                        <div class="text-sm text-gray-500">Max: {{ $item->maximum_stock }}</div>
                                        @if($item->needsReordering())
                                            <div class="text-sm text-red-600">Reorder Point: {{ $item->reorder_point }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">Purchase: ₦{{ number_format($item->purchase_price, 2) }}</div>
                                        <div class="text-sm text-gray-500">Selling: ₦{{ number_format($item->selling_price, 2) }}</div>
                                        <div class="text-sm text-gray-500">Value: {{ $item->formatted_stock_value }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $item->status_color === 'green' ? 'bg-green-100 text-green-800' : 
                                               ($item->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($item->status_color === 'red' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('admin.inventory.show', $item) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">View</a>
                                            <a href="{{ route('admin.inventory.edit', $item) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            
                                            <form method="POST" action="{{ route('admin.inventory.toggle-status', $item) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                    {{ $item->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            
                                            <form method="POST" action="{{ route('admin.inventory.destroy', $item) }}" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this item?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $inventoryItems->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No inventory items found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first inventory item.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.inventory.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                New Item
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
