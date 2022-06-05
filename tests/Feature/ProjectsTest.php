<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * /projectsにPOSTアクセスするとプロジェクトを作成することができる
     *
     * @test
     */
    public function projects_created()
    {
        $this->withoutExceptionHandling();

        $response = $this->post('/projects', [
            'title' => 'test project 1',
            'description' => 'lorem ipsum kajslehnn kjshawljidj kslkawhklska jhkaksjek jlakwkdhhir gnzjdbuwja.'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('projects', [
            'title' => 'test project 1',
            'description' => 'lorem ipsum kajslehnn kjshawljidj kslkawhklska jhkaksjek jlakwkdhhir gnzjdbuwja.'
        ]);
    }
}
