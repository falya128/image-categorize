# Image Categorize

## 概要

AWS Rekognition の機能を理解するために作成したプロジェクトです。  
ブラウザの画面から画像をアップロードすると、人数および人物別に画像を分類して一覧表示します。

## 環境

-   PHP 8.2
-   Laravel 10
-   Inertia
-   Vue 3
-   Vuetify
-   SQLite

迅速な動作確認を行うために SQLite を使用しました。  
別のデータベースでも動作すると思います。（動作未確認）

## 開始手順

### 各種ライブラリのインストール

```powershell
cd image-categorize
composer install
npm install
```

### 環境設定

```powershell
cp .env.example .env
php artisan key:generate
php artisan storage:link
```

以下の箇所を変更  
※ AWS の IAM でアクセスキーを発行する

```
DB_DATABASE=

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

### データベース準備

```powershell
php artisan migrate
```

### 起動

```powershell
npm run build
php artisan serve
```
