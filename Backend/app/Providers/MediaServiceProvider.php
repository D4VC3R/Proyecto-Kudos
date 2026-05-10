<?php

namespace App\Providers;

use App\Contracts\Media\ImageProcessorInterface;
use App\Contracts\Media\MediaStorageInterface;
use App\Services\Media\InterventionImageProcessor;
use App\Services\Media\LaravelMediaStorage;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ImageProcessorInterface::class, InterventionImageProcessor::class);
        $this->app->bind(MediaStorageInterface::class, LaravelMediaStorage::class);
    }
}

