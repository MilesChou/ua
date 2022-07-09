<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        File::makeDirectory('docs', 0755, true, true);
    }

    public function register()
    {
        //
    }
}
