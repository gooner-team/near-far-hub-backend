<?php

declare(strict_types=1);

namespace App\Services\Repositories\Listings;

use App\Enums\Listings\ListingStatusEnum;
use App\Http\DataTransferObjects\Listings\CreateListingData;
use App\Http\DataTransferObjects\Listings\UpdateListingData;
use App\Models\Listings\Listing;
use App\Models\SellerProfiles\SellerProfile;
use App\Services\Repositories\Seller\SellerProfileRepository;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ListingRepository
{
    public function __construct(
        private readonly Listing $listing,
        private readonly SellerProfileRepository $sellerProfileRepository
    ) {
    }

    /**
     * Get listings with filters and pagination
     */
    public function getListings(array $filters): LengthAwarePaginator
    {
        $query = $this->listing->query()
            ->with([
                Listing::SELLER_PROFILE_RELATION . '.' . SellerProfile::USER_RELATION,
                Listing::IMAGES_RELATION,
                Listing::PRIMARY_IMAGE_RELATION,
            ])
            ->active()
            ->published()
            ->notExpired();

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['condition'])) {
            $query->byCondition($filters['condition']);
        }

        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $query->priceRange($filters['min_price'], $filters['max_price']);
        }

        if (!empty($filters['location'])) {
            $query->whereJsonContains(Listing::LOCATION . '->city', $filters['location']);
        }

        if (isset($filters['can_deliver_globally']) && $filters['can_deliver_globally']) {
            $query->where(Listing::CAN_DELIVER_GLOBALLY, true);
        }

        if (isset($filters['requires_appointment']) && $filters['requires_appointment']) {
            $query->where(Listing::REQUIRES_APPOINTMENT, true);
        }

        if (isset($filters['featured']) && $filters['featured']) {
            $query->orderByDesc(Listing::VIEWS_COUNT)
                ->orderByDesc(Listing::FAVORITES_COUNT);
        }

        $this->applySorting($query, $filters['sort_by'] ?? 'newest');

        $perPage = min($filters['per_page'] ?? 20, 100);
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get listing by ID
     */
    public function getListingById(int $id): ?Listing
    {
        return $this->listing->query()
            ->with([Listing::SELLER_PROFILE_RELATION . '.' . SellerProfile::USER_RELATION])
            ->find($id);
    }

    /**
     * Create a new listing
     * @throws Exception
     */
    public function createListing(int $userId, CreateListingData $data): Listing
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        if (!$sellerProfile->getIsActive()) {
            throw new Exception('Seller account is not active');
        }

        $payload = [
            Listing::SELLER_PROFILE_ID => $sellerProfile->getId(),
            Listing::TITLE => $data->title,
            Listing::DESCRIPTION => $data->description,
            Listing::PRICE => $data->price,
            Listing::CATEGORY => $data->category,
            Listing::CONDITION => $data->condition,
            Listing::IMAGES => $data->images,
            Listing::LOCATION => $data->location,
            Listing::CAN_DELIVER_GLOBALLY => $data->canDeliverGlobally,
            Listing::REQUIRES_APPOINTMENT => $data->requiresAppointment,
            Listing::STATUS => $data->status,
            Listing::EXPIRES_AT => $data->expiresAt ? now()->parse($data->expiresAt) : null,
        ];

        if ($data->status === ListingStatusEnum::STATUS_ACTIVE->value) {
            $payload[Listing::PUBLISHED_AT] = now();
        }

        $listing = $this->listing->create($payload);

        return $listing->load([Listing::SELLER_PROFILE_RELATION . '.' . SellerProfile::USER_RELATION]);
    }

    /**
     * Update an existing listing
     */
    public function updateListing(int $id, int $userId, UpdateListingData $data): Listing
    {
        $listing = $this->findListingForUser($id, $userId);

        $updateData = [];

        if ($data->title !== null) {
            $updateData[Listing::TITLE] = $data->title;
        }
        if ($data->description !== null) {
            $updateData[Listing::DESCRIPTION] = $data->description;
        }
        if ($data->price !== null) {
            $updateData[Listing::PRICE] = $data->price;
        }
        if ($data->category !== null) {
            $updateData[Listing::CATEGORY] = $data->category;
        }
        if ($data->condition !== null) {
            $updateData[Listing::CONDITION] = $data->condition;
        }
        if ($data->images !== null) {
            $updateData[Listing::IMAGES] = $data->images;
        }
        if ($data->location !== null) {
            $updateData[Listing::LOCATION] = $data->location;
        }
        if ($data->canDeliverGlobally !== null) {
            $updateData[Listing::CAN_DELIVER_GLOBALLY] = $data->canDeliverGlobally;
        }
        if ($data->requiresAppointment !== null) {
            $updateData[Listing::REQUIRES_APPOINTMENT] = $data->requiresAppointment;
        }
        if ($data->status !== null) {
            $updateData[Listing::STATUS] = $data->status;

            if ($data->status === ListingStatusEnum::STATUS_ACTIVE->value && !$listing->isPublished()) {
                $updateData[Listing::PUBLISHED_AT] = now();
            }
        }
        if ($data->expiresAt !== null) {
            $updateData[Listing::EXPIRES_AT] = now()->parse($data->expiresAt);
        }

        $listing->update($updateData);

        return $listing->load([Listing::SELLER_PROFILE_RELATION . '.' . SellerProfile::USER_RELATION]);
    }

    /**
     * Delete a listing
     */
    public function deleteListing(int $id, int $userId): void
    {
        $listing = $this->findListingForUser($id, $userId);
        $listing->delete();
    }

    /**
     * Get user's listings
     */
    public function getUserListings(int $userId, array $filters): LengthAwarePaginator
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $query = $this->listing->query()
            ->with([Listing::SELLER_PROFILE_RELATION . '.userRelation'])
            ->where(Listing::SELLER_PROFILE_ID, $sellerProfile->getId());

        if (!empty($filters['status'])) {
            $query->where(Listing::STATUS, $filters['status']);
        }

        $this->applySorting($query, $filters['sort_by'] ?? 'newest');

        $perPage = min($filters['per_page'] ?? 20, 100);
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Publish a listing
     * @throws Exception
     */
    public function publishListing(int $id, int $userId): Listing
    {
        $listing = $this->findListingForUser($id, $userId);

        if (!$listing->isPublishable()) {
            throw new Exception('Listing cannot be published in current status');
        }

        $listing->update([
            Listing::STATUS => ListingStatusEnum::STATUS_ACTIVE->value,
            Listing::PUBLISHED_AT => now(),
        ]);

        return $listing->load([Listing::SELLER_PROFILE_RELATION . '.' . SellerProfile::USER_RELATION]);
    }

    /**
     * Unpublish a listing
     * @throws Exception
     */
    public function unpublishListing(int $id, int $userId): Listing
    {
        $listing = $this->findListingForUser($id, $userId);

        if (!$listing->isActive()) {
            throw new Exception('Listing is not active');
        }

        $listing->update([
            Listing::STATUS => ListingStatusEnum::STATUS_SUSPENDED->value,
        ]);

        return $listing->load([Listing::SELLER_PROFILE_RELATION . '.' . SellerProfile::USER_RELATION]);
    }

    /**
     * Mark listing as sold
     * @throws Exception
     */
    public function markAsSold(int $id, int $userId): Listing
    {
        $listing = $this->findListingForUser($id, $userId);

        if (!$listing->isActive()) {
            throw new Exception('Only active listings can be marked as sold');
        }

        $listing->update([
            Listing::STATUS => ListingStatusEnum::STATUS_SOLD->value,
        ]);

        return $listing->load([Listing::SELLER_PROFILE_RELATION . '.' . SellerProfile::USER_RELATION]);
    }

    /**
     * Get featured listings
     */
    public function getFeaturedListings(array $filters): Collection
    {
        $query = $this->listing->query()
            ->with([Listing::SELLER_PROFILE_RELATION . '.' . SellerProfile::USER_RELATION])
            ->active()
            ->published()
            ->notExpired()
            ->orderByDesc(Listing::VIEWS_COUNT)
            ->orderByDesc(Listing::FAVORITES_COUNT)
            ->orderByDesc(Listing::PUBLISHED_AT);

        $perPage = min($filters['per_page'] ?? 12, 50);

        return $query->limit($perPage)->get();
    }

    /**
     * Search listings
     */
    public function searchListings(array $filters): Collection
    {
        $query = $this->listing->query()
            ->with([Listing::SELLER_PROFILE_RELATION . '.userRelation'])
            ->active()
            ->published()
            ->notExpired()
            ->search($filters['search']);

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['condition'])) {
            $query->byCondition($filters['condition']);
        }

        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $query->priceRange($filters['min_price'], $filters['max_price']);
        }

        if (!empty($filters['location'])) {
            $query->whereJsonContains(Listing::LOCATION . '->city', $filters['location']);
        }

        $this->applySorting($query, $filters['sort_by'] ?? 'relevance');

        $perPage = min($filters['per_page'] ?? 20, 100);

        return $query->limit($perPage)->get();
    }

    /**
     * Increment listing views
     */
    public function incrementViews(int $id): void
    {
        $listing = $this->listing->find($id);

        if ($listing) {
            $listing->incrementViews();
        }
    }

    /**
     * Get listing statistics
     */
    public function getListingStats(): array
    {
        return [
            'total_listings' => $this->listing->count(),
            'active_listings' => $this->listing->active()->count(),
            'published_listings' => $this->listing->published()->count(),
            'expired_listings' => $this->listing->where(Listing::EXPIRES_AT, '<', now())->count(),
            'sold_listings' => $this->listing->where(Listing::STATUS, ListingStatusEnum::STATUS_SOLD->value)->count(),
            'total_views' => $this->listing->sum(Listing::VIEWS_COUNT),
            'total_favorites' => $this->listing->sum(Listing::FAVORITES_COUNT),
            'average_price' => $this->listing->active()->avg(Listing::PRICE),
        ];
    }

    /**
     * Get categories with listing counts
     */
    public function getCategoriesWithCounts(): array
    {
        return $this->listing->query()
            ->active()
            ->published()
            ->notExpired()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'category')
            ->toArray();
    }

    /**
     * Find listing for user with permission check
     */
    private function findListingForUser(int $listingId, int $userId): Listing
    {
        $sellerProfile = $this->sellerProfileRepository->getByUserId($userId);

        if (!$sellerProfile) {
            throw new ModelNotFoundException('Seller profile not found');
        }

        $listing = $this->listing->query()
            ->with([Listing::SELLER_PROFILE_RELATION . '.userRelation'])
            ->where(Listing::ID, $listingId)
            ->where(Listing::SELLER_PROFILE_ID, $sellerProfile->getId())
            ->first();

        if (!$listing) {
            throw new ModelNotFoundException('Listing not found or you do not have permission to access it');
        }

        return $listing;
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, string $sortBy): void
    {
        switch ($sortBy) {
            case 'newest':
                $query->orderByDesc(Listing::CREATED_AT);
                break;
            case 'oldest':
                $query->orderBy(Listing::CREATED_AT);
                break;
            case 'price_low':
                $query->orderBy(Listing::PRICE);
                break;
            case 'price_high':
                $query->orderByDesc(Listing::PRICE);
                break;
            case 'most_viewed':
                $query->orderByDesc(Listing::VIEWS_COUNT);
                break;
            case 'most_favorited':
                $query->orderByDesc(Listing::FAVORITES_COUNT);
                break;
            case 'relevance':
                $query->orderByDesc(Listing::VIEWS_COUNT)
                    ->orderByDesc(Listing::FAVORITES_COUNT)
                    ->orderByDesc(Listing::PUBLISHED_AT);
                break;
            default:
                $query->orderByDesc(Listing::CREATED_AT);
        }
    }

    /**
     * Check if listing is publishable
     */
    private function isPublishable(Listing $listing): bool
    {
        $status = ListingStatusEnum::from($listing->getStatus());
        return $status->isPublishable();
    }
}