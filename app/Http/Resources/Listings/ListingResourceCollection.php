<?php

declare(strict_types=1);

namespace App\Http\Resources\Listings;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ListingResourceCollection extends ResourceCollection
{
    public $collects = ListingResource::class;
}