<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProjectsTest extends TestCase
{
    // /**
    //  * /projectsにアクセスするとプロジェクトの一覧を見ることができる
    //  *
    //  * @test
    //  */
    // public function projects_displayed()
    // {
    //     $response = $this->get('/projects');

    //     $response->assertStatus(200);
    // }

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
