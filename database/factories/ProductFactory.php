<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currentPrice = fake()->numberBetween(990, 9990);
        $lowestPrice = fake()->numberBetween(490, $currentPrice);
        $highestPrice = fake()->numberBetween($currentPrice, 14990);

        return [
            'product_id' => 'E' . fake()->unique()->numerify('######'),
            'price_group' => fake()->randomElement(['000', '001', '002']),
            'name' => fake()->words(fake()->numberBetween(3, 8), true),
            'brand' => fake()->randomElement(['uniqlo', 'gu']),
            'gender' => fake()->randomElement(['MEN', 'WOMEN', 'KIDS', 'BABY', 'UNISEX', null]),
            'image_url' => fake()->imageUrl(640, 640, 'fashion'),
            'current_price' => $currentPrice,
            'lowest_price' => $lowestPrice,
            'highest_price' => $highestPrice,
        ];
    }

    /**
     * Set the product as UNIQLO brand.
     */
    public function uniqlo(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => 'uniqlo',
        ]);
    }

    /**
     * Set the product as GU brand.
     */
    public function gu(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => 'gu',
        ]);
    }

    /**
     * Set a specific gender.
     */
    public function gender(string $gender): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => $gender,
        ]);
    }

    /**
     * Set product at lowest price.
     */
    public function atLowestPrice(): static
    {
        return $this->state(function (array $attributes) {
            $price = fake()->numberBetween(990, 4990);
            return [
                'current_price' => $price,
                'lowest_price' => $price,
                'highest_price' => $price + fake()->numberBetween(1000, 5000),
            ];
        });
    }
}
