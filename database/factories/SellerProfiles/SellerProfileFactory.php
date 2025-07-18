<?php

declare(strict_types=1);

namespace Database\Factories\SellerProfiles;

use App\Models\SellerProfiles\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SellerProfileFactory extends Factory
{
    protected $model = SellerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => $this->faker->company(),
            'business_description' => $this->faker->paragraphs(2, true),
            'business_type' => $this->faker->randomElement(['individual', 'business', 'company']),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'listing_fee_balance' => $this->faker->randomFloat(2, 0, 100),
            'is_active' => true,
            'is_verified' => $this->faker->boolean(60),
            'verified_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_at' => now()->subDays(rand(1, 365)),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}