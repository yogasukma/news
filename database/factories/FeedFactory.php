<?php

namespace Database\Factories;

use App\Models\Feed;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feed>
 */
class FeedFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->company(),
            'url' => fake()->unique()->url(),
            'site_url' => fake()->url(),
            'description' => fake()->sentence(),
            'favicon_url' => null,
            'folder_id' => null,
            'last_fetched_at' => null,
        ];
    }

    public function inFolder(?Folder $folder = null): static
    {
        return $this->state(fn (array $attributes) => [
            'folder_id' => $folder?->id ?? Folder::factory(),
        ]);
    }
}
