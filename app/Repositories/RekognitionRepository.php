<?php

namespace App\Repositories;

use App\Interfaces\RekognitionRepositoryInterface;
use Aws\Rekognition\RekognitionClient;
use Aws\Result;
use Illuminate\Http\UploadedFile;

class RekognitionRepository implements RekognitionRepositoryInterface
{
    private RekognitionClient $client;

    public function __construct()
    {
        $this->client = new RekognitionClient([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    /**
     * コレクションの新規作成
     *
     * @param string $collectionId
     * @return bool
     */
    public function createCollection(string $collectionId): bool
    {
        $result = $this->client->createCollection(['CollectionId' => $collectionId]);
        return $result['StatusCode'] === 200;
    }

    /**
     * コレクションの削除
     *
     * @param string $collectionId
     * @return bool
     */
    public function deleteCollection(string $collectionId): bool
    {
        $result = $this->client->deleteCollection(['CollectionId' => $collectionId]);
        return $result['StatusCode'] === 200;
    }

    /**
     * 顔を検出して指定したコレクションに追加
     *
     * @param string $collectionId
     * @param UploadedFile $file
     * @param string|null $imageId
     * @return Result
     */
    public function indexFaces(string $collectionId, UploadedFile $file, string|null $imageId = null): Result
    {
        $params = [
            'CollectionId' => $collectionId,
            'DetectionAttributes' => ['DEFAULT'],
            'Image' => ['Bytes' => $file->get()]
        ];
        if ($imageId != null) {
            $params['ExternalImageId'] = $imageId;
        }
        return $this->client->indexFaces($params);
    }

    /**
     * コレクション内の顔データを取得
     *
     * @param string $collectionId
     * @return array
     */
    public function listFaces(string $collectionId): array
    {
        $faces = [];
        $nextToken = '';
        do {
            $params = ['CollectionId' => $collectionId];
            if ($nextToken != '') {
                $params['NextToken'] = $nextToken;
            }
            $result = $this->client->listFaces($params);
            $nextToken = $result['NextToken'];
            $faces = [...$faces, ...$result['Faces']];
        } while ($nextToken != '');

        return $faces;
    }

    /**
     * コレクション内の顔データを検索
     *
     * @param string $collectionId
     * @param string $faceId
     * @return array
     */
    public function searchFaces(string $collectionId, string $faceId): array
    {
        $result = $this->client->searchFaces([
            'CollectionId' => $collectionId,
            'FaceId' => $faceId,
        ]);
        return $result['FaceMatches'];
    }
}
