<?php

namespace Illuminate\Contracts\Foundation;

use Illuminate\Contracts\Container\Container;

interface Application extends Container
{
    public function version();
    public function basePath($path = '');
    public function bootstrapPath($path = '');
    public function configPath($path = '');
    public function databasePath($path = '');
    public function environmentPath();
    public function resourcePath($path = '');
    public function storagePath($path = '');
    public function environment(...$environments);
    public function runningInConsole();
    public function runningUnitTests();
    public function maintenanceMode();
    public function isDownForMaintenance();
    public function registerConfiguredProviders();
    public function register($provider, $force = false);
    public function boot();
    public function booting($callback);
    public function booted($callback);
}
