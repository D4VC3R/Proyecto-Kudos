<?php

namespace App\Providers;

use App\Contracts\Media\ImageProcessorInterface;
use App\Contracts\Media\MediaStorageInterface;
use App\Services\Media\InterventionImageProcessor;
use App\Services\Media\LaravelMediaStorage;
use Illuminate\Support\ServiceProvider;

/**
 * MediaServiceProvider se encarga de registrar los servicios relacionados con el manejo de imágenes en la aplicación.
 * Aquí se vinculan las interfaces a sus implementaciones concretas.
 */
class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Uso singleton para asegurar que solo haya una instancia de cada servicio durante el ciclo de vida de la aplicación.
        $this->app->singleton(ImageProcessorInterface::class, InterventionImageProcessor::class);
        $this->app->singleton(MediaStorageInterface::class, LaravelMediaStorage::class);
    }
}

