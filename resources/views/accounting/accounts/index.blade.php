@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Chart of Accounts</h1>
                    <p class="mt-2 text-gray-600">Manage your accounting accounts</p>
                </div>
                <div>
                    <a href="{{ route('admin.accounts.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        New Account
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
                               placeholder="Account code, name, or description">
                    </div>
                    
                    <div>
                        <label for="account_type" class="block text-sm font-medium text-gray-700">Account Type</label>
                        <select name="account_type" id="account_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Types</option>
                            <option value="asset" {{ request('account_type') === 'asset' ? 'selected' : '' }}>Asset</option>
                            <option value="liability" {{ request('account_type') === 'liability' ? 'selected' : '' }}>Liability</option>
                            <option value="equity" {{ request('account_type') === 'equity' ? 'selected' : '' }}>Equity</option>
                            <option value="revenue" {{ request('account_type') === 'revenue' ? 'selected' : '' }}>Revenue</option>
                            <option value="expense" {{ request('account_type') === 'expense' ? 'selected' : '' }}>Expense</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="account_category" class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="account_category" id="account_category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Categories</option>
                            <option value="current_asset" {{ request('account_category') === 'current_asset' ? 'selected' : '' }}>Current Asset</option>
                            <option value="fixed_asset" {{ request('account_category') === 'fixed_asset' ? 'selected' : '' }}>Fixed Asset</option>
                            <option value="current_liability" {{ request('account_category') === 'current_liability' ? 'selected' : '' }}>Current Liability</option>
                            <option value="long_term_liability" {{ request('account_category') === 'long_term_liability' ? 'selected' : '' }}>Long Term Liability</option>
                            <option value="equity" {{ request('account_category') === 'equity' ? 'selected' : '' }}>Equity</option>
                            <option value="revenue" {{ request('account_category') === 'revenue' ? 'selected' : '' }}>Revenue</option>
                            <option value="operating_expense" {{ request('account_category') === 'operating_expense' ? 'selected' : '' }}>Operating Expense</option>
                            <option value="non_operating_expense" {{ request('account_category') === 'non_operating_expense' ? 'selected' : '' }}>Non Operating Expense</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="is_active" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="is_active" id="is_active" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Status</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
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

        <!-- Accounts Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:p-6">
                @if($accounts->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($accounts as $account)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $account->account_code }}</div>
                                        <div class="text-sm text-gray-500">{{ $account->account_name }}</div>
                                        @if($account->description)
                                            <div class="text-sm text-gray-400">{{ Str::limit($account->description, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $account->type_color === 'green' ? 'bg-green-100 text-green-800' : 
                                               ($account->type_color === 'red' ? 'bg-red-100 text-red-800' : 
                                               ($account->type_color === 'blue' ? 'bg-blue-100 text-blue-800' : 
                                               ($account->type_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($account->type_color === 'orange' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800')))) }}">
                                            {{ ucfirst($account->account_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $account->category_color === 'green' ? 'bg-green-100 text-green-800' : 
                                               ($account->category_color === 'emerald' ? 'bg-emerald-100 text-emerald-800' : 
                                               ($account->category_color === 'red' ? 'bg-red-100 text-red-800' : 
                                               ($account->category_color === 'rose' ? 'bg-rose-100 text-rose-800' : 
                                               ($account->category_color === 'blue' ? 'bg-blue-100 text-blue-800' : 
                                               ($account->category_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($account->category_color === 'orange' ? 'bg-orange-100 text-orange-800' : 
                                               ($account->category_color === 'amber' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800'))))))) }}">
                                            {{ ucfirst(str_replace('_', ' ', $account->account_category)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $account->balance_for_display }}</div>
                                        <div class="text-sm text-gray-500">Opening: ₦{{ number_format($account->opening_balance, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $account->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $account->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('admin.accounts.show', $account) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">View</a>
                                            <a href="{{ route('admin.accounts.edit', $account) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            
                                            <form method="POST" action="{{ route('admin.accounts.toggle-status', $account) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                    {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            
                                            <form method="POST" action="{{ route('admin.accounts.update-balance', $account) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-blue-600 hover:text-blue-900">Update Balance</button>
                                            </form>
                                            
                                            @if(!$account->is_system_account)
                                                <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" class="inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this account?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $accounts->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No accounts found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first account.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.accounts.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                New Account
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
