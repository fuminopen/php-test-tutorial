<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectsService;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    /**
     * プロジェクト作成
     *
     * @param \Illuminate\Http\Request
     */
    public function create(Request $request)
    {
        $projectsService = new ProjectsService();

        $projectsService->create(
            $request->title,
            $request->description
        );
    }
}
