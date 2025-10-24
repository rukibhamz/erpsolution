<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Exceptions\ValidationException as CustomValidationException;

class ValidationService
{
    /**
     * Validate business rules for property creation
     */
    public function validatePropertyCreation(array $data): array
    {
        $rules = [
            'name' => 'required|string|max:255|unique:properties,name',
            'property_type_id' => 'required|exists:property_types,id',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'purchase_price' => 'required|numeric|min:0',
            'current_value' => 'required|numeric|min:0',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new CustomValidationException($validator);
        }

        // Additional business rule validations
        $this->validatePropertyBusinessRules($data);

        return $validator->validated();
    }

    /**
     * Validate business rules for lease creation
     */
    public function validateLeaseCreation(array $data): array
    {
        $rules = [
            'property_id' => 'required|exists:properties,id',
            'tenant_name' => 'required|string|max:255',
            'tenant_email' => 'required|email|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new CustomValidationException($validator);
        }

        // Additional business rule validations
        $this->validateLeaseBusinessRules($data);

        return $validator->validated();
    }

    /**
     * Validate business rules for transaction creation
     */
    public function validateTransactionCreation(array $data): array
    {
        $rules = [
            'account_id' => 'required|exists:accounts,id',
            'transaction_type' => 'required|in:income,expense,transfer',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:500',
            'transaction_date' => 'required|date|before_or_equal:today',
            'category' => 'required|string|max:100',
            'payment_method' => 'required|in:cash,bank_transfer,check,card,other',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new CustomValidationException($validator);
        }

        // Additional business rule validations
        $this->validateTransactionBusinessRules($data);

        return $validator->validated();
    }

    /**
     * Validate business rules for event creation
     */
    public function validateEventCreation(array $data): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'venue' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:10000',
            'price' => 'required|numeric|min:0',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new CustomValidationException($validator);
        }

        // Additional business rule validations
        $this->validateEventBusinessRules($data);

        return $validator->validated();
    }

    /**
     * Validate property business rules
     */
    private function validatePropertyBusinessRules(array $data): void
    {
        // Check if current value is reasonable compared to purchase price
        if (isset($data['purchase_price']) && isset($data['current_value'])) {
            $purchasePrice = (float) $data['purchase_price'];
            $currentValue = (float) $data['current_value'];
            
            // Current value should not be more than 10x purchase price
            if ($currentValue > ($purchasePrice * 10)) {
                throw new CustomValidationException(
                    Validator::make([], [])->errors()->add(
                        'current_value',
                        'Current value seems unreasonably high compared to purchase price.'
                    )
                );
            }
            
            // Current value should not be less than 50% of purchase price
            if ($currentValue < ($purchasePrice * 0.5)) {
                throw new CustomValidationException(
                    Validator::make([], [])->errors()->add(
                        'current_value',
                        'Current value seems unreasonably low compared to purchase price.'
                    )
                );
            }
        }
    }

    /**
     * Validate lease business rules
     */
    private function validateLeaseBusinessRules(array $data): void
    {
        // Check for overlapping leases
        if (isset($data['property_id']) && isset($data['start_date']) && isset($data['end_date'])) {
            $overlappingLease = \App\Models\Lease::where('property_id', $data['property_id'])
                ->where('status', 'active')
                ->where(function ($query) use ($data) {
                    $query->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                          ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                          ->orWhere(function ($q) use ($data) {
                              $q->where('start_date', '<=', $data['start_date'])
                                ->where('end_date', '>=', $data['end_date']);
                          });
                })
                ->exists();

            if ($overlappingLease) {
                throw new CustomValidationException(
                    Validator::make([], [])->errors()->add(
                        'property_id',
                        'This property already has an active lease during the specified period.'
                    )
                );
            }
        }

        // Validate lease duration
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $startDate = \Carbon\Carbon::parse($data['start_date']);
            $endDate = \Carbon\Carbon::parse($data['end_date']);
            $duration = $startDate->diffInDays($endDate);

            if ($duration < 30) {
                throw new CustomValidationException(
                    Validator::make([], [])->errors()->add(
                        'end_date',
                        'Lease duration must be at least 30 days.'
                    )
                );
            }

            if ($duration > 3650) { // 10 years
                throw new CustomValidationException(
                    Validator::make([], [])->errors()->add(
                        'end_date',
                        'Lease duration cannot exceed 10 years.'
                    )
                );
            }
        }
    }

    /**
     * Validate transaction business rules
     */
    private function validateTransactionBusinessRules(array $data): void
    {
        // Check account balance for expense transactions
        if (isset($data['transaction_type']) && $data['transaction_type'] === 'expense') {
            if (isset($data['account_id']) && isset($data['amount'])) {
                $account = \App\Models\Account::find($data['account_id']);
                if ($account && $account->balance < $data['amount']) {
                    throw new CustomValidationException(
                        Validator::make([], [])->errors()->add(
                            'amount',
                            'Insufficient account balance for this transaction.'
                        )
                    );
                }
            }
        }

        // Validate transaction amount limits
        if (isset($data['amount'])) {
            $amount = (float) $data['amount'];
            
            if ($amount > 10000000) { // 10 million
                throw new CustomValidationException(
                    Validator::make([], [])->errors()->add(
                        'amount',
                        'Transaction amount cannot exceed ₦10,000,000.'
                    )
                );
            }
        }
    }

    /**
     * Validate event business rules
     */
    private function validateEventBusinessRules(array $data): void
    {
        // Validate event duration
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $startDate = \Carbon\Carbon::parse($data['start_date']);
            $endDate = \Carbon\Carbon::parse($data['end_date']);
            $duration = $startDate->diffInDays($endDate);

            if ($duration > 30) {
                throw new CustomValidationException(
                    Validator::make([], [])->errors()->add(
                        'end_date',
                        'Event duration cannot exceed 30 days.'
                    )
                );
            }
        }

        // Validate event pricing
        if (isset($data['price'])) {
            $price = (float) $data['price'];
            
            if ($price > 1000000) { // 1 million
                throw new CustomValidationException(
                    Validator::make([], [])->errors()->add(
                        'price',
                        'Event price cannot exceed ₦1,000,000.'
                    )
                );
            }
        }
    }

    /**
     * Validate file uploads
     */
    public function validateFileUploads(array $files, array $rules = []): array
    {
        $defaultRules = [
            'max_size' => 2048, // 2MB
            'allowed_types' => ['jpeg', 'jpg', 'png', 'gif'],
            'max_files' => 5,
        ];

        $rules = array_merge($defaultRules, $rules);

        $errors = [];

        // Check number of files
        if (count($files) > $rules['max_files']) {
            $errors[] = "Maximum {$rules['max_files']} files allowed.";
        }

        foreach ($files as $index => $file) {
            // Check file size
            if ($file->getSize() > ($rules['max_size'] * 1024)) {
                $errors[] = "File " . ($index + 1) . " exceeds maximum size of {$rules['max_size']}KB.";
            }

            // Check file type
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $rules['allowed_types'])) {
                $errors[] = "File " . ($index + 1) . " has invalid type. Allowed types: " . implode(', ', $rules['allowed_types']);
            }
        }

        if (!empty($errors)) {
            throw new CustomValidationException(
                Validator::make([], [])->errors()->add('files', implode(' ', $errors))
            );
        }

        return $files;
    }

    /**
     * Validate Nigerian-specific data
     */
    public function validateNigerianData(array $data): array
    {
        $rules = [
            'phone' => 'required|regex:/^(\+234|234|0)?[789][01]\d{8}$/',
            'email' => 'required|email',
            'state' => 'required|in:Abia,Adamawa,Akwa Ibom,Anambra,Bauchi,Bayelsa,Benue,Borno,Cross River,Delta,Ebonyi,Edo,Ekiti,Enugu,FCT,Gombe,Imo,Jigawa,Kaduna,Kano,Katsina,Kebbi,Kogi,Kwara,Lagos,Nasarawa,Niger,Ogun,Ondo,Osun,Oyo,Plateau,Rivers,Sokoto,Taraba,Yobe,Zamfara',
            'city' => 'required|string|max:100',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new CustomValidationException($validator);
        }

        return $validator->validated();
    }
}
