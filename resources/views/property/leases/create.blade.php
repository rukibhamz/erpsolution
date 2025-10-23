@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Create New Lease</h1>
                    <p class="mt-2 text-gray-600">Set up a new lease agreement between tenant and property</p>
                </div>
                <div>
                    <a href="{{ route('admin.leases.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Back to Leases
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('admin.leases.store') }}" class="p-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Lease Information</h3>
                    </div>
                    
                    <div>
                        <label for="property_id" class="block text-sm font-medium text-gray-700">Property *</label>
                        <select name="property_id" id="property_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('property_id') border-red-300 @enderror">
                            <option value="">Select Property</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" {{ (old('property_id') == $property->id || ($selectedProperty && $selectedProperty->id == $property->id)) ? 'selected' : '' }}>
                                    {{ $property->name }} - {{ $property->property_code }} (₦{{ number_format($property->rent_amount, 2) }}/month)
                                </option>
                            @endforeach
                        </select>
                        @error('property_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tenant_id" class="block text-sm font-medium text-gray-700">Tenant *</label>
                        <select name="tenant_id" id="tenant_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('tenant_id') border-red-300 @enderror">
                            <option value="">Select Tenant</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" {{ (old('tenant_id') == $tenant->id || ($selectedTenant && $selectedTenant->id == $tenant->id)) ? 'selected' : '' }}>
                                    {{ $tenant->full_name }} - {{ $tenant->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('tenant_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date *</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('start_date') border-red-300 @enderror">
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date *</label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('end_date') border-red-300 @enderror">
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Financial Information -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Financial Terms</h3>
                    </div>

                    <div>
                        <label for="monthly_rent" class="block text-sm font-medium text-gray-700">Monthly Rent (₦) *</label>
                        <input type="number" name="monthly_rent" id="monthly_rent" value="{{ old('monthly_rent') }}" step="0.01" min="0" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('monthly_rent') border-red-300 @enderror">
                        @error('monthly_rent')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="deposit_amount" class="block text-sm font-medium text-gray-700">Deposit Amount (₦)</label>
                        <input type="number" name="deposit_amount" id="deposit_amount" value="{{ old('deposit_amount') }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('deposit_amount') border-red-300 @enderror">
                        @error('deposit_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="late_fee_amount" class="block text-sm font-medium text-gray-700">Late Fee Amount (₦)</label>
                        <input type="number" name="late_fee_amount" id="late_fee_amount" value="{{ old('late_fee_amount', 0) }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('late_fee_amount') border-red-300 @enderror">
                        @error('late_fee_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="late_fee_days" class="block text-sm font-medium text-gray-700">Late Fee After (Days)</label>
                        <input type="number" name="late_fee_days" id="late_fee_days" value="{{ old('late_fee_days', 5) }}" min="1"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('late_fee_days') border-red-300 @enderror">
                        @error('late_fee_days')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="rent_due_date" class="block text-sm font-medium text-gray-700">Rent Due Date (Day of Month)</label>
                        <input type="number" name="rent_due_date" id="rent_due_date" value="{{ old('rent_due_date') }}" min="1" max="31"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('rent_due_date') border-red-300 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Day of the month when rent is due (1-31)</p>
                        @error('rent_due_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Additional Charges -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Additional Charges</label>
                        <div class="mt-2 space-y-2" id="additional-charges">
                            <div class="flex space-x-2">
                                <input type="text" name="additional_charges[0][name]" placeholder="Charge name" 
                                       class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <input type="number" name="additional_charges[0][amount]" placeholder="Amount" step="0.01" min="0"
                                       class="w-32 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <button type="button" onclick="removeCharge(this)" class="text-red-600 hover:text-red-800">Remove</button>
                            </div>
                        </div>
                        <button type="button" onclick="addCharge()" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">+ Add Charge</button>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="md:col-span-2">
                        <label for="terms_and_conditions" class="block text-sm font-medium text-gray-700">Terms and Conditions</label>
                        <textarea name="terms_and_conditions" id="terms_and_conditions" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('terms_and_conditions') border-red-300 @enderror">{{ old('terms_and_conditions') }}</textarea>
                        @error('terms_and_conditions')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Renewal Settings -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Renewal Settings</h3>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="auto_renewal" id="auto_renewal" value="1" 
                                   {{ old('auto_renewal') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <label for="auto_renewal" class="ml-2 text-sm text-gray-700">Enable automatic renewal</label>
                        </div>
                    </div>

                    <div>
                        <label for="renewal_notice_days" class="block text-sm font-medium text-gray-700">Renewal Notice (Days)</label>
                        <input type="number" name="renewal_notice_days" id="renewal_notice_days" value="{{ old('renewal_notice_days', 30) }}" min="1"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('renewal_notice_days') border-red-300 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Days before expiry to send renewal notice</p>
                        @error('renewal_notice_days')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="{{ route('admin.leases.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Create Lease
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let chargeIndex = 1;

function addCharge() {
    const container = document.getElementById('additional-charges');
    const newCharge = document.createElement('div');
    newCharge.className = 'flex space-x-2';
    newCharge.innerHTML = `
        <input type="text" name="additional_charges[${chargeIndex}][name]" placeholder="Charge name" 
               class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        <input type="number" name="additional_charges[${chargeIndex}][amount]" placeholder="Amount" step="0.01" min="0"
               class="w-32 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        <button type="button" onclick="removeCharge(this)" class="text-red-600 hover:text-red-800">Remove</button>
    `;
    container.appendChild(newCharge);
    chargeIndex++;
}

function removeCharge(button) {
    button.parentElement.remove();
}

// Auto-fill rent amount when property is selected
document.getElementById('property_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        const rentMatch = selectedOption.text.match(/₦([\d,]+\.?\d*)/);
        if (rentMatch) {
            const rentAmount = rentMatch[1].replace(/,/g, '');
            document.getElementById('monthly_rent').value = rentAmount;
        }
    }
});

// Calculate end date when start date changes
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = new Date(this.value);
    if (startDate) {
        // Default to 12 months from start date
        const endDate = new Date(startDate);
        endDate.setFullYear(endDate.getFullYear() + 1);
        document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
    }
});
</script>
@endsection
