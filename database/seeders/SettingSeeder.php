<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Company Information
            ['key' => 'company_name', 'value' => 'Your Company Name', 'type' => 'string', 'group' => 'company'],
            ['key' => 'company_email', 'value' => 'info@yourcompany.com', 'type' => 'string', 'group' => 'company'],
            ['key' => 'company_phone', 'value' => '+234-xxx-xxx-xxxx', 'type' => 'string', 'group' => 'company'],
            ['key' => 'company_address', 'value' => 'Your Company Address', 'type' => 'string', 'group' => 'company'],
            ['key' => 'company_logo', 'value' => '/images/logo.png', 'type' => 'string', 'group' => 'company'],
            
            // System Settings
            ['key' => 'default_currency', 'value' => 'NGN', 'type' => 'string', 'group' => 'system'],
            ['key' => 'currency_symbol', 'value' => '₦', 'type' => 'string', 'group' => 'system'],
            ['key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string', 'group' => 'system'],
            ['key' => 'timezone', 'value' => 'Africa/Lagos', 'type' => 'string', 'group' => 'system'],
            
            // Property Settings
            ['key' => 'default_lease_duration', 'value' => '12', 'type' => 'number', 'group' => 'property'],
            ['key' => 'late_fee_percentage', 'value' => '5', 'type' => 'number', 'group' => 'property'],
            ['key' => 'late_fee_days', 'value' => '5', 'type' => 'number', 'group' => 'property'],
            ['key' => 'renewal_notice_days', 'value' => '30', 'type' => 'number', 'group' => 'property'],
            
            // Tax Settings
            ['key' => 'vat_rate', 'value' => '7.5', 'type' => 'number', 'group' => 'tax'],
            ['key' => 'amac_rate', 'value' => '1', 'type' => 'number', 'group' => 'tax'],
            
            // Payment Settings
            ['key' => 'payment_gateway', 'value' => 'paystack', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'payment_currency', 'value' => 'NGN', 'type' => 'string', 'group' => 'payment'],
            
            // Notification Settings
            ['key' => 'email_notifications', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'sms_notifications', 'value' => '0', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'lease_expiry_reminder_days', 'value' => '30', 'type' => 'number', 'group' => 'notifications'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
