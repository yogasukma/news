<?php

namespace App\Console\Commands;

use App\Models\Folder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FolderCreateCommand extends Command
{
    protected $signature = 'rss:folder:create {name : The folder name}';

    protected $description = 'Create a new feed folder';

    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = Str::slug($name);

        if (Folder::where('slug', $slug)->exists()) {
            $this->error("Folder '{$name}' already exists.");

            return self::FAILURE;
        }

        $folder = Folder::create([
            'name' => $name,
            'slug' => $slug,
        ]);

        $this->info("Folder '{$folder->name}' created (ID: {$folder->id}, slug: {$folder->slug}).");

        return self::SUCCESS;
    }
}
