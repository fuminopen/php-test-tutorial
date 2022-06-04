<?php

namespace App\Http\Controllers;

use App\Models\Project;
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
        Project::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);
    }
}
