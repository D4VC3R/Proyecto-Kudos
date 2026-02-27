<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Vote;
use App\Models\Item;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mapear Strings a modelo para obtener las referencias
	    Relation::enforceMorphMap([
		    'vote' => Vote::class,
		    'item' => Item::class,
	    ]);
    }
}
