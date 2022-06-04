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