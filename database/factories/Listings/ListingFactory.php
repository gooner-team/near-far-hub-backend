<?php

declare(strict_types=1);

namespace Database\Factories\Listings;

use App\Enums\Listings\ListingCategoryEnum;
use App\Enums\Listings\ListingConditionEnum;
use App\Enums\Listings\ListingStatusEnum;
use App\Models\Listings\Listing;
use App\Models\SellerProfiles\SellerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ListingFactory extends Factory
{
    protected $model = Listing::class;

    public function definition(): array
    {
        $categories = ListingCategoryEnum::getValues();
        $conditions = ListingConditionEnum::getValues();
        $statuses = ListingStatusEnum::getValues();

        $category = $this->faker->randomElement($categories);
        $requiresCondition = ListingCategoryEnum::from($category)->requiresCondition();

        return [
            'seller_profile_id' => SellerProfile::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraphs(3, true),
            'price' => $this->faker->randomFloat(2, 10, 999),
            'category' => $category,
            'condition' => $requiresCondition ? $this->faker->randomElement($conditions) : null,
            'images' => $this->faker->randomElements([
                'https://via.placeholder.com/600x400/FF6B6B/FFFFFF?text=Product+1',
                'https://via.placeholder.com/600x400/4ECDC4/FFFFFF?text=Product+2',
                'https://via.placeholder.com/600x400/45B7D1/FFFFFF?text=Product+3',
                'https://via.placeholder.com/600x400/96CEB4/FFFFFF?text=Product+4',
            ], rand(1, 3)),
            'location' => [
                'city' => $this->faker->city(),
                'country' => $this->faker->country(),
                'coordinates' => [
                    'lat' => $this->faker->latitude(),
                    'lng' => $this->faker->longitude(),
                ]
            ],
            'can_deliver_globally' => $this->faker->boolean(30),
            'requires_appointment' => $this->faker->boolean(40),
            'status' => $this->faker->randomElement($statuses),
            'published_at' => $this->faker->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('+7 days', '+60 days'),
            'views_count' => $this->faker->numberBetween(0, 500),
            'favorites_count' => $this->faker->numberBetween(0, 50),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatusEnum::STATUS_ACTIVE->value,
            'published_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatusEnum::STATUS_DRAFT->value,
            'published_at' => null,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatusEnum::STATUS_SOLD->value,
            'published_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatusEnum::STATUS_EXPIRED->value,
            'published_at' => now()->subDays(rand(30, 60)),
            'expires_at' => now()->subDays(rand(1, 7)),
        ]);
    }

    public function electronics(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ListingCategoryEnum::ELECTRONICS->value,
            'condition' => $this->faker->randomElement(ListingConditionEnum::getValues()),
        ]);
    }

    public function vehicles(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ListingCategoryEnum::VEHICLES->value,
            'condition' => $this->faker->randomElement(ListingConditionEnum::getValues()),
            'price' => $this->faker->randomFloat(2, 5000, 50000),
        ]);
    }

    public function services(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ListingCategoryEnum::SERVICES->value,
            'condition' => null,
            'requires_appointment' => true,
        ]);
    }
}