## Laravelにおける自動テストについて

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

# 1. /projectsにPOSTアクセスするとプロジェクトを作成することができる

## ステップ1

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

## ステップ2

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

## 2. /projectsにアクセスするとプロジェクトの一覧を見ることができる

1. まずはFeatureにE2Eのテストを書いてしまいましょう。



```php
/**
 * /projectsにアクセスするとプロジェクトの一覧を見ることができる
 *
 * @test
 */
public function projects_displayed()
{
    $response = $this->get('/projects');

    $response->assertStatus(200);
}
```

2. 上記のテストを通したら次は要件を追加します。

```php