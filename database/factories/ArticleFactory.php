<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Feed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'feed_id' => Feed::factory(),
            'title' => fake()->sentence(),
            'url' => fake()->unique()->url(),
            'content' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'author' => fake()->name(),
            'published_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'cover_image' => null,
            'external_id' => fake()->unique()->uuid(),
        ];
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now(),
        ]);
    }

    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $date,
        ]);
    }
}
