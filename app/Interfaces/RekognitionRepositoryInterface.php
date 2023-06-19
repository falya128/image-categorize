<?php

namespace App\Interfaces;

use Aws\Result;
use Illuminate\Http\UploadedFile;

interface RekognitionRepositoryInterface
{
    // コレクションの新規作成
    public function createCollection(string $collectionId): bool;

    // コレクションの削除
    public function deleteCollection(string $collectionId): bool;

    // 顔を検出して指定したコレクションに追加
    public function indexFaces(string $collectionId, UploadedFile $file, string $imageId = null): Result;

    // コレクション内の顔データを取得
    public function listFaces(string $collectionId): array;

    // コレクション内の顔データを検索
    public function searchFaces(string $collectionId, string $faceId): array;
}
