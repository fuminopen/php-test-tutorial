<?php

namespace App\Services;

use App\Models\Project;
use Exception;

class ProjectsService
{
    /**
     * projectsテーブルに新規レコードを作成する
     *
     * @param  string $title
     * @param  string $description
     * @return bool
     *
     * @throws Exception
     */
    public function create(string $title, string $description): bool
    {
        try {
            Project::create([
                'title' => $title,
                'description' => $description,
            ]);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}