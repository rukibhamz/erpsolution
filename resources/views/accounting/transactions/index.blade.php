@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Transactions</h1>
                    <p class="mt-2 text-gray-600">Manage your financial transactions</p>
                </div>
                <div>
                    <a href="{{ route('admin.transactions.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        New Transaction
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="Reference, description, or reference number">
                    </div>
                    
                    <div>
                        <label for="transaction_type" class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="transaction_type" id="transaction_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Types</option>
                            <option value="income" {{ request('transaction_type') === 'income' ? 'selected' : '' }}>Income</option>
                            <option value="expense" {{ request('transaction_type') === 'expense' ? 'selected' : '' }}>Expense</option>
                            <option value="transfer" {{ request('transaction_type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                            <option value="adjustment" {{ request('transaction_type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="account_id" class="block text-sm font-medium text-gray-700">Account</label>
                        <select name="account_id" id="account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Accounts</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:p-6">
                @if($transactions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->transaction_reference }}</div>
                                        <div class="text-sm text-gray-500">{{ Str::limit($transaction->description, 50) }}</div>
                                        @if($transaction->reference_number)
                                            <div class="text-sm text-gray-400">Ref: {{ $transaction->reference_number }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->account->account_code }}</div>
                                        <div class="text-sm text-gray-500">{{ $transaction->account->account_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $transaction->type_color === 'green' ? 'bg-green-100 text-green-800' : 
                                               ($transaction->type_color === 'red' ? 'bg-red-100 text-red-800' : 
                                               ($transaction->type_color === 'blue' ? 'bg-blue-100 text-blue-800' : 
                                               ($transaction->type_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'))) }}">
                                            {{ ucfirst($transaction->transaction_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium {{ $transaction->isIncome() ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $transaction->formatted_amount }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $transaction->transaction_date->format('M d, Y') }}</div>
                                        <div class="text-sm text-gray-500">{{ $transaction->created_at->format('g:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $transaction->status_color === 'green' ? 'bg-green-100 text-green-800' : 
                                               ($transaction->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($transaction->status_color === 'red' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('admin.transactions.show', $transaction) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">View</a>
                                            
                                            @if(!$transaction->isApproved())
                                                <a href="{{ route('admin.transactions.edit', $transaction) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            @endif
                                            
                                            @if($transaction->isPending())
                                                <form method="POST" action="{{ route('admin.transactions.approve', $transaction) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                                                </form>
                                                
                                                <form method="POST" action="{{ route('admin.transactions.reject', $transaction) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Reject</button>
                                                </form>
                                            @endif
                                            
                                            @if(!$transaction->isApproved())
                                                <form method="POST" action="{{ route('admin.transactions.cancel', $transaction) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">Cancel</button>
                                                </form>
                                                
                                                <form method="POST" action="{{ route('admin.transactions.destroy', $transaction) }}" class="inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this transaction?')">
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
                        {{ $transactions->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No transactions found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first transaction.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.transactions.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                New Transaction
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
