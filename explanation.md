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

# step 4 -- リファクタリングする

- 現在、projectsテーブルのレコードを作成する処理を`ProjectsController`に書いてしまっています。ここではMVCSアーキテクチャを採用しているものと考え、リファクタリングを行っていきます。
`ProjectsController`から`ProjectsService`に定義されたレコード作成処理、`create()`を呼び出すように変更します。

```php
// ProjectsController

public function create(Request $request)
{
    $projectsService = new ProjectsService();

    $projectsService->create(
        $request->title,
        $request->description
    );
}

// ProjectsService

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
```

- さて、もう一度テストを回してみましょう。

```bash
$ php artisan test

   PASS  Tests\Feature\ProjectsTest
  ✓ projects created

  Tests:  1 passed
  Time:   0.08s
```

- 無事とおりましたね。

# step 5 -- テスト間の依存性に対処する

- mysqlのテーブルも見に行ってみましょう。

```bash
mysql> select * from php_test_tutorials.projects;
+----+---------------------+---------------------+----------------+----------------------------------------------------------------------------------+
| id | created_at          | updated_at          | title          | description                                                                      |
+----+---------------------+---------------------+----------------+----------------------------------------------------------------------------------+
|  1 | 2022-06-04 12:33:47 | 2022-06-04 12:33:47 | test project 1 | lorem ipsum kajslehnn kjshawljidj kslkawhklska jhkaksjek jlakwkdhhir gnzjdbuwja. |
|  2 | 2022-06-05 13:32:57 | 2022-06-05 13:32:57 | test project 1 | lorem ipsum kajslehnn kjshawljidj kslkawhklska jhkaksjek jlakwkdhhir gnzjdbuwja. |
+----+---------------------+---------------------+----------------+----------------------------------------------------------------------------------+
2 rows in set (0.00 sec)
```

- 同じレコードが二つあります。そうです。現在のテストの構造では、テストを回すたびに同じテストレコードがひたすら作成されていくことになります。実はここには自動テストを行う上で注意するべき大きな課題が隠れています。それがテスト間の依存性です。

例えば上記の例で、2回目のレコード作成が`ProjectsService`クラスで失敗した場合を考えてみてください。失敗自体はtry catchによってハンドリングされることで、処理が止まることはなく`false`が返ります。

一方、`ProjectsController`ではserviceクラスの返り値によって返却するレスポンスステータスを変更はしていません。すなわち200が返却されます。
すると、もともとテーブルに存在していた同じtitle、同じdescriptionのレコードのアサーションまですべて通ってしまい、本来は失敗であるべきテストが成功と評価されてしまいます。

このように前に実行されたテストや、テスト(やテスト対象となる環境)のもともとの状態によって、後続のテストが影響を受けてしまうことを防ぐ必要があります。
ここではデータベースの状態を毎回のテストで一緒にして、データベースに起因するテスト間の依存性に対処していきます。

- `ProjectsTest`クラスに`RefreshDatabase`トレイトを読み込みましょう。

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;
```

- この`RefreshDatabase`トレイトは、テストの始まりに一旦`php artisan migrate:fresh`コマンドを発行して、データベースを真っ新にしたのち、migrationファイルで定義されたテーブルを作成します。その後データベーストランザクションをbeginし、テストの終了と同時にrollbackすることでテスト実行によるレコード作成などをなかったことにしています。

```php
protected function refreshTestDatabase()
{
    if (! RefreshDatabaseState::$migrated) {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());

        $this->app[Kernel::class]->setArtisan(null);

        RefreshDatabaseState::$migrated = true;
    }

    $this->beginDatabaseTransaction();
}
```

- 注目してほしいのが`migrate:fresh`と`beginDatabaseTransaction()`の時系列です。`migrate:fresh`コマンドで現在接続中のデータベース内を更地にした後にトランザクションを開始しています。
すなわち、テストが終了し、残るのはmigration実行後の真っ新なテーブルたちであって、あなたが本番環境で運用していたテーブル達ではありません。私はこれで一度テスト環境のテーブルをすべて吹き飛ばしました。

**このトレイトは大変強力ですが、使用する際は、チーム内で認識を合わせ、また私のようにそそっかしいメンバーが大事なテーブルを吹き飛ばさないようあらかじめ対策を講じておくことをお勧めします。**

# step 6 -- テスト時のデータベース操作をメモリ内で完結させる

- この対策として有効なのが、テスト実行時にそもそも外部のDBサーバーに接続をせず、メモリ上のDBでデータの操作を行う、というものです。phpunit.xmlを開き、下記を編集しましょう。

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="DB_CONNECTION" value="sqlite"/> <!-- 追加 -->
    <env name="DB_DATABASE" value=":memory:"/> <!-- 追加 -->
    <env name="MAIL_MAILER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="TELESCOPE_ENABLED" value="false"/>
</php>
```

- それでは、念のためmysqlのprojectsテーブルのレコードを削除したのち、テストをまわしてみましょう。

```bash
mysql> delete from php_test_tutorials.projects;
Query OK, 2 rows affected (0.00 sec)
```

```bash
   PASS  Tests\Feature\ProjectsTest
  ✓ projects created

  Tests:  1 passed
  Time:   0.08s
```

- 無事通りました。mysqlのテーブルも汚れていないことが確認できます。

```bash
mysql> select * from php_test_tutorials.projects;
Empty set (0.00 sec)
```

- このように、sqliteを使用して通常使用しているDBに依存しないテストを作成する方法の他、`.env.testing`というテスト専用の.envファイルを作成し、テスト用のデータベースを定義してしまうことも可能です。いずれの場合も、もともとデータベースに入っている値を読み出すなどの処理が行えないなど一長一短はありますが、信頼性の高いテストを作成するうえで非常に強力なツールとなるため、ぜひ運用方法を確立したいものです


<!-- # 2. /projectsにGETでアクセスすると作成したプロジェクトが閲覧できる

- /projectsへPOSTアクセスでプロジェクトの作成ができるようになりました。続いて、プロジェクトの一覧を閲覧する機能を作成します。今回も前回同様、ざっくりとした仕様をテストに書き出していきます。 -->