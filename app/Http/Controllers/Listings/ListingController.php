<?php

declare(strict_types=1);

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listings\CreateListingRequest;
use App\Http\Requests\Listings\UpdateListingRequest;
use App\Http\Resources\Listings\ListingResource;
use App\Http\Resources\Listings\ListingResourceCollection;
use App\Services\Repositories\Listings\ListingRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class ListingController extends Controller
{
    public function __construct(
        private readonly ListingRepository $listingRepository
    )
    {
    }

    /**
     * Get all listings with filtering and search
     */
    public function getAllListingsWithFiltering(Request $request): ListingResourceCollection
    {
        $filters = [
            'search' => $request->get('search'),
            'category' => $request->get('category'),
            'condition' => $request->get('condition'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'location' => $request->get('location'),
            'can_deliver_globally' => $request->get('can_deliver_globally'),
            'requires_appointment' => $request->get('requires_appointment'),
            'sort_by' => $request->get('sort_by', 'newest'),
            'per_page' => $request->get('per_page', 20),
            'page' => $request->get('page', 1),
        ];

        $listings = $this->listingRepository->getListings($filters);

        return ListingResourceCollection::make($listings);
    }

    /**
     * Get a specific listing
     */
    public function getListingById(int $id): JsonResponse|ListingResource
    {
        $listing = $this->listingRepository->getListingById($id);

        if (!$listing) {
            return response()->json([
                'message' => 'Listing not found'
            ], 404);
        }

        $this->listingRepository->incrementViews($id);

        return new ListingResource($listing);
    }

    /**
     * Create a new listing
     * @throws UnknownProperties
     * @throws Exception
     */
    public function createListing(CreateListingRequest $request): ListingResource
    {
        $listing = $this->listingRepository->createListing(
            auth()->id(),
            $request->dto()
        );

        return new ListingResource($listing);
    }

    /**
     * Update an existing listing
     * @throws UnknownProperties
     */
    public function updateListing(int $id, UpdateListingRequest $request): ListingResource
    {
        $listing = $this->listingRepository->updateListing(
            $id,
            auth()->id(),
            $request->dto()
        );

        return new ListingResource($listing);
    }

    /**
     * Delete a listing
     */
    public function deleteListing(int $id): JsonResponse
    {
        $this->listingRepository->deleteListing($id, auth()->id());

        return response()->json([
            'message' => 'Listing deleted successfully'
        ]);
    }

    /**
     * Get listings by category
     */
    public function getListingsByCategory(string $category, Request $request): ListingResourceCollection
    {
        $filters = [
            'category' => $category,
            'search' => $request->get('search'),
            'condition' => $request->get('condition'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'location' => $request->get('location'),
            'sort_by' => $request->get('sort_by', 'newest'),
            'per_page' => $request->get('per_page', 20),
            'page' => $request->get('page', 1),
        ];

        $listings = $this->listingRepository->getListings($filters);

        return ListingResourceCollection::make($listings);
    }

    /**
     * Get current user's listings
     */
    public function getCurrentUserListings(Request $request): ListingResourceCollection
    {
        $filters = [
            'status' => $request->get('status'),
            'sort_by' => $request->get('sort_by', 'newest'),
            'per_page' => $request->get('per_page', 20),
            'page' => $request->get('page', 1),
        ];

        $listings = $this->listingRepository->getUserListings(auth()->id(), $filters);

        return ListingResourceCollection::make($listings);
    }

    /**
     * Publish a listing
     * @throws Exception
     */
    public function publishListing(int $id): ListingResource
    {
        $listing = $this->listingRepository->publishListing($id, auth()->id());

        return new ListingResource($listing);
    }

    /**
     * Unpublish a listing
     * @throws Exception
     */
    public function unpublishListing(int $id): ListingResource
    {
        $listing = $this->listingRepository->unpublishListing($id, auth()->id());

        return new ListingResource($listing);
    }

    /**
     * Mark listing as sold
     * @throws Exception
     */
    public function markAsSold(int $id): ListingResource
    {
        $listing = $this->listingRepository->markAsSold($id, auth()->id());

        return new ListingResource($listing);
    }

    /**
     * Get featured listings
     */
    public function getFeaturedListings(Request $request): ListingResourceCollection
    {
        $filters = [
            'featured' => true,
            'per_page' => $request->get('per_page', 12),
            'page' => $request->get('page', 1),
        ];

        $listings = $this->listingRepository->getFeaturedListings($filters);

        return ListingResourceCollection::make($listings->items());
    }

    /**
     * Search listings
     */
    public function searchListings(Request $request): JsonResponse|ListingResourceCollection
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([
                'data' => [],
                'message' => 'Search query is required'
            ], 400);
        }

        $filters = [
            'search' => $query,
            'category' => $request->get('category'),
            'condition' => $request->get('condition'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'location' => $request->get('location'),
            'sort_by' => $request->get('sort_by', 'relevance'),
            'per_page' => $request->get('per_page', 20),
            'page' => $request->get('page', 1),
        ];

        $listings = $this->listingRepository->searchListings($filters);

        return ListingResourceCollection::make($listings->items());
    }

    /**
     * Get listing statistics
     */
    public function getListingStats(): JsonResponse
    {
        $stats = $this->listingRepository->getListingStats();

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get categories with listing counts
     */
    public function getCategoriesWithListingCounts(): JsonResponse
    {
        $categories = $this->listingRepository->getCategoriesWithCounts();

        return response()->json([
            'data' => $categories
        ]);
    }
}