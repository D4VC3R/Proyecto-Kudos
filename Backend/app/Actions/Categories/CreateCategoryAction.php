<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Services\CategoryService;

class CreateCategoryAction
{
    public function __construct(protected CategoryService $categoryService)
    {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function execute(array $payload): Category
    {
        return $this->categoryService->createCategory($payload);
    }
}

