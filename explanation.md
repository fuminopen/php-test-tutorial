# Laravelにおける自動テストについて

- PHPUnitというPHPのテスティングフレームワークを使用する。Laravel固有の機能ではない。
- 基本的にはプロジェクトのルートディレクトリ直下の`tests`ディレクトリにテストは作成していく。
- Unit、Featureとあるが、Unitは単体テストと呼ばれ、一つのクラスのテストを作成していくディレクトリ。Featureには複数のクラスをまたがるテストを書いていく。

## テストの実行

- プロジェクトルートで下記のコマンドを使用する。

```php
php artisan test // すべてのテストが走る
php artisan test {{ PATH_TO_A_TEST_FILE }} // 任意の一つのテストクラスのみ走る
php artisan test --filter {{ YOUR_TEST_NAME }} // 指定した名前のテストが走る
```

## テストスイートの定義

- テストスイートという、一緒に回したいテストの塊を定義することが可能。

```php
php artisan test --testsuite Unit // 単体テストのみ
php artisan test --testsuite Feature // 機能テストのみ
```

- phpunit.xmlを編集することで、Unit / Feature以外にもテストスイートを追加することが可能

```xml
<testsuites>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
</testsuites>
```

## おすすめの設定

- `php artisan test`を毎回打つのは大変。aliasコマンドで登録をしておくことをおすすめ。
私の場合は下記のコマンドを`~/.bashrc`に登録してあります(Macの方は`~/.zshrc`)。

```bash
# in ~/.bashrc

alias pt="php artisan test"
alias psuite="php artisan test --testsuite"
alias pf="php artisan test --filter"
```

___

# 1. /projectsにPOSTアクセスするとプロジェクトを作成することができる

## ステップ1 -- テストのスクラッチを書く

1. まずはE2Eのテストをかいてしまいます。

Doc comment部分に@testというアノテーションがついているのが見えるかと思います。
このアノテーションがついていない場合、その関数はテストとして認識されず、実行されません。ただし例外として、関数名がtestから始まる場合、アノテーションは不要です。

```php
/**
 * /projectsにPOSTアクセスするとプロジェクトを作成することができる
 *
 * @test
 */
public function projects_created()
{
    $response = $this->post('/projects', [
        'title' => 'test project 1',
        'description' => 'lorem ipsum kajslehnn kjshawljidj kslkawhklska jhkaksjek jlakwkdhhir gnzjdbuwja.'
    ]);

    $response->assertStatus(200);
}
```

- まわしてみましたがなんで405だったのかよくわかりません。

```bash
  • Tests\Feature\ProjectsTest > projects created
  Expected response status code [200] but received 405.
  Failed asserting that 200 is identical to 405.
```

- withoutExceptionHandling()メソッドを使用してコード実行時の例外を握りつぶさないようにして再度回します。

```php
public function projects_created()
{
    $this->withoutExceptionHandling();
```

- NotFoundHttpとありますね。そもそもルーティングを定義していないので当然ですね。このようにテストの詳細な失敗理由を知りたいときはwithoutExceptionHandling()をつけてあげるとよいとおもいます。

```bash
  • Tests\Feature\ProjectsTest > projects created
   Symfony\Component\HttpKernel\Exception\NotFoundHttpException

  POST http://php-test-tutorials.test/projects
```

- エラーメッセージに従ってroutingとコントローラーを追加しました。

```php
// in web.php
Route::post('/projects', [\App\Http\Controllers\ProjectsController::class, 'create']);

// in ProjectsController
public function create(Request $request)
{
}
```

- 再度回してみます。無事とおりました。

```bash
   PASS  Tests\Feature\ProjectsTest
  ✓ projects created

  Tests:  1 passed
  Time:   0.06s
```

## ステップ2 -- 仕様をテストに反映する

ですが、このテスト、このままでは圧倒的に不十分です。
なぜならtitleとdescriptionをリクエストとして送信しましたが、その値をもとに本当にレコードが作成されたのかがわかりません。

- テストにチェック (これをアサーションと呼ぶ) を追加しましょう。assertDatabaseHasはTestCaseクラスの持つメソッドで、指定したテーブルに、指定した属性をもつレコードが存在するかをチェックするメソッドです。
回しましょう。

```php
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
```

- 失敗したようです。projectsテーブルなんて存在しないよと言われてしまいました。またまた当然ですね。作っていないので。

```bash
  • Tests\Feature\ProjectsTest > projects created
   Illuminate\Database\QueryException

  SQLSTATE[42S02]: Base table or view not found: 1146 Table 'testing.projects' doesn't exist (SQL: select count(*) as aggregate from `projects` where (`title` = test project 1 and `description` = lorem ipsum kajslehnn kjshawljidj kslkawhklska jhkaksjek jlakwkdhhir gnzjdbuwja.))
```

- migrationファイルを追加して作りましょう。

```bash
$ php artisan make:migration CreateProjectsTable
Created Migration: 2022_06_04_093619_create_projects_table
```

```php
public function up()
{
    Schema::create('projects', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
        $table->string('title');
        $table->string('description');
    });
}
```

- 作成したマイグレーションファイルを実行します。

```bash
$ php artisan migrate
Migrating: 2022_06_04_093619_create_projects_table
Migrated:  2022_06_04_093619_create_projects_table (20.90ms)
```

- 再度回すとテストの失敗理由が変わりました。指定したタイトルおよび詳細に合致するレコードは見つかりませんでしたよと出ていますね。すなわち次の作業は、`/projects`へPOSTリクエストがあったら、実際にDBへの保存を行いこのアサーションが通るようにすることですね。

```bash
  • Tests\Feature\ProjectsTest > projects created
  Failed asserting that a row in the table [projects] matches the attributes {
      "title": "test project 1",
      "description": "lorem ipsum kajslehnn kjshawljidj kslkawhklska jhkaksjek jlakwkdhhir gnzjdbuwja."
  }.

  The table is empty.

  at tests/Feature/ProjectsTest.php:38
     34▕
     35▕         $response->assertStatus(200);
     36▕
     37▕         $this->assertDatabaseHas('projects', [
  ➜  38▕             'title' => 'test project 1',
     39▕             'description' => 'lorem ipsum kajslehnn kjshawljidj kslkawhklska jhkaksjek jlakwkdhhir gnzjdbuwja.'
     40▕         ]);
     41▕     }
     42▕ }
```

## step 3 -- 仕様通りに動くようにする

- ProjectsControllerで、受け取ったリクエストからProjectのレコードを作成するようにしました。
ただ当然ながらテストは通りません。Projectモデルを作成する必要がありますね。

```php
public function create(Request $request)
{
    Project::create([
        'title' => $request->title,
        'description' => $request->description,
    ]);
}
```

```php
  • Tests\Feature\ProjectsTest > projects created
   Error

  Class "App\Http\Controllers\Project" not found
```

- モデルクラスが作成されましたね。ProjectsControllerにimport文を追加して再度テストをまわしてみましょう。

```bash
php artisan make:model Project
```

```php
// in ProjectsController
use App\Models\Project;
```

```php
class Project extends Model
{
    use HasFactory;
}
```

- 次は別のエラーが出ました。デフォルトではtitle / descriptionともにmass assignmentのブロックがかかっていますね。

```bash
  • Tests\Feature\ProjectsTest > projects created
   Illuminate\Database\Eloquent\MassAssignmentException

  Add [title] to fillable property to allow mass assignment on [App\Models\Project].
```

- Laravelではmass assignmentの受け入れはModelsクラスのクラス変数である`$fillable`、あるいは`$guarded`で定義します。

```php
class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
    ];
}
```

- テストをまわしてみると・・・・・通りました！！

```bash
   PASS  Tests\Feature\ProjectsTest
  ✓ projects created

  Tests:  1 passed
  Time:   0.07s
```

- 実際にDBを見に行ってみましょう。しっかりとレコードが出来上がっていることがわかりますね。

```bash
$ docker ps
20cb73cec85c   mysql/mysql-server:8.0        "/entrypoint.sh mysq…"   39 minutes ago   Up 30 minutes (healthy)   0.0.0.0:3306->3306/tcp, :::3306->3306/tcp, 33060-33061/tcp phptesttutorials_mysql_1 # phptesttutorials_mysql_1をコピーする

$ docker exec -it phptesttutorials_mysql_1 bash

# 以下mysqlコンテナ内
bash-4.4# mysql -u sail -p
Enter password: # .envで定義してあるパスワードを打つ

mysql> select * from php_test_tutorials.projects;
+----+---------------------+---------------------+----------------+----------------------------------------------------------------------------------+
| id | created_at          | updated_at          | title          | description                                                                      |
+----+---------------------+---------------------+----------------+----------------------------------------------------------------------------------+
|  1 | 2022-06-04 12:33:47 | 2022-06-04 12:33:47 | test project 1 | lorem ipsum kajslehnn kjshawljidj kslkawhklska jhkaksjek jlakwkdhhir gnzjdbuwja. |
+----+---------------------+---------------------+----------------+----------------------------------------------------------------------------------+
1 row in set (0.00 sec)
```