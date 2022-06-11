## 前提条件

最新番のDocker Engineがインストールされている必要があります。
(実際には全くの最新である必要はありませんが、version 2?あたりで止まっている場合、buildが失敗します)。

## 環境構築

1. Laravelのインストール

任意のディレクトリで下記のコマンドを実行します。
プロジェクト (Laravelをインストールするディレクトリの名前) を変えたい場合は下記のコマンドの`example-app`部分を任意の文字列に変更してください。

下記のままたたいた場合は、コマンドを実行したディレクトリに`example-app`というディレクトリが作成され、その中にLaravelがインストールされることになります。


今回は`php_test_tutorials`という名前で作成しました。

```bash
curl -s "https://laravel.build/example-app" | bash
```

.envに下記を追加

```
WWWGROUP=1000
WWWUSER=1000
```

phpunit.xmlの下記部分も書き換えます。

```xml
<php>
    <env name="DB_DATABASE" value="php_test_tutorials"/> <!-- testingをプロジェクト名に書き換える -->
</php>
```

docker imageのbuildをおこないます。

```
docker-compose build
```

立ち上げます。

```
./vendor/bin/sail up
```

立ち上がったらmigrationを実行します。

```bash
php artisan migrate
```

## このチュートリアルで作成するアプリケーション

タスク管理アプリ。

タスクを作成し、プロジェクトごとにそのタスクをまとめて管理することができる。

タスクには発行者 (owner) と担当者 (assignee) を設定することができる。

## License

This tutorial is licensed under the [MIT license](https://opensource.org/licenses/MIT).
