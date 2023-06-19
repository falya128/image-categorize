<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageUploadRequest;
use App\Interfaces\RekognitionRepositoryInterface;
use App\Models\Image;
use Aws\Rekognition\Exception\RekognitionException;
use Intervention\Image\Facades\Image as InterventionImage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ImageController extends Controller
{
    private RekognitionRepositoryInterface $rekognitionRepository;

    private FilesystemAdapter $storage;

    // 本プロジェクトでは1つのコレクションに全データを登録（コレクションIDは任意の値）
    private string $collectionId = 'collectionId';

    public function __construct(RekognitionRepositoryInterface $rekognitionRepository)
    {
        $this->rekognitionRepository = $rekognitionRepository;
        $this->storage = Storage::disk('public');
    }

    /**
     * 写真一覧表示
     *
     * @return Response
     */
    public function index(): Response
    {
        $this->createCollection();

        $fileDetails = [];
        $filePaths = $this->storage->files('photos');
        foreach ($filePaths as $filePath) {
            $fileDetails[] = [
                'name' => basename($filePath),
                'url' => $this->storage->url($filePath),
            ];
        };
        return Inertia::render('Index', [
            'imagesByNumberOfPeople' => $this->groupByNumberOfPeople($fileDetails),
            'imagesByFace' => $this->groupByFace($fileDetails),
        ]);
    }

    /**
     * 写真を全削除
     *
     * @return RedirectResponse
     */
    public function reset(): RedirectResponse
    {
        $this->rekognitionRepository->deleteCollection($this->collectionId);
        $this->storage->deleteDirectory('photos');
        Image::query()->delete();
        return to_route('index');
    }

    /**
     * 写真のアップロード
     *
     * @param ImageUploadRequest $request
     * @return RedirectResponse
     */
    public function upload(ImageUploadRequest $request): RedirectResponse
    {
        $files = $request->file('files');
        foreach ($files as $file) {
            // 画像データをストレージに保存
            $fileName = $file->hashName();
            $file->storeAs('photos', $fileName, 'public');

            $result = $this->rekognitionRepository->indexFaces($this->collectionId, $file, $fileName);
            // 画像から人数を判別（認識不十分であった顔も人数に含める）
            $numberOfPeople = count($result['FaceRecords']) + count($result['UnindexedFaces']);
            Image::create([
                'file_name' => $fileName,
                'number_of_people' => $numberOfPeople,
            ]);
        }
        return to_route('index');
    }

    /**
     * 顔画像を表示
     *
     * @param Request $request
     * @param string $filename
     * @return HttpResponse
     */
    public function showFace(Request $request, string $filename): HttpResponse
    {
        $path = $this->storage->path("photos/{$filename}");
        $img = InterventionImage::make($path);
        $imgWidth = $img->width();
        $imgHeight = $img->height();

        // 顔の輪郭を表示するため、認識された顔の枠よりも少し大きく切り抜く
        $width = $imgWidth * $request->query('width');
        $height = $imgHeight * $request->query('height');
        $cropWidth = $width * 1.5;
        $cropHeight = $height * 1.5;
        $diffWidth = ($cropWidth - $width) / 2;
        $diffHeight = ($cropHeight - $height) / 2;
        $left = $imgWidth * $request->query('left') - $diffWidth;
        $top = $imgHeight * $request->query('top') - $diffHeight;
        $img->crop((int) $cropWidth, (int) $cropHeight, (int) $left, (int) $top);

        return $img->response();
    }

    /**
     * コレクションの新規作成
     *
     * @return void
     */
    private function createCollection(): void
    {
        try {
            $this->rekognitionRepository->createCollection($this->collectionId);
        } catch (RekognitionException $e) {
            if ($e->getAwsErrorCode() != 'ResourceAlreadyExistsException') {
                throw $e;
            }
        }
    }

    /**
     * 人数別にグループ分けした画像データを取得
     *
     * @param array $fileDetails
     * @return array
     */
    private function groupByNumberOfPeople(array $fileDetails): array
    {
        $fileNames = array_map(fn ($fileDetail) => $fileDetail['name'], $fileDetails);
        $images = Image::whereIn('file_name', $fileNames)->get();

        $groupedFiles = [];
        foreach ($fileDetails as $fileDetail) {
            $image = $images->where('file_name', $fileDetail['name'])->first();
            $numberOfPeople = $image['number_of_people'];
            if (!isset($groupedFiles[$numberOfPeople])) {
                $groupedFiles[$numberOfPeople] = [];
            }
            $groupedFiles[$numberOfPeople][] =  [
                'name' => $fileDetail['name'],
                'url' => $fileDetail['url'],
            ];
        }
        return $groupedFiles;
    }

    /**
     * 人物別にグループ分けした画像をデータを取得
     * 
     * @param array $fileDetails
     * @return array
     */
    private function groupByFace(array $fileDetails): array
    {
        if (count($fileDetails) <= 0) return [];

        $faces = $this->rekognitionRepository->listFaces($this->collectionId);
        $groupedFiles = [];
        foreach ($faces as $face) {
            // 既に似た顔としてグループ分けされた顔データ、または信頼性が低い顔データの場合はスキップ
            if ($this->isProcessedFace($face, $groupedFiles) || $face['Confidence'] < 99) {
                continue;
            } else {
                $groupedFiles[] = [
                    'face_id' => $face['FaceId'],
                    'url' => "/face/{$face['ExternalImageId']}",
                    'bounding_box' => [
                        'width' => $face['BoundingBox']['Width'],
                        'height' => $face['BoundingBox']['Height'],
                        'top' => $face['BoundingBox']['Top'],
                        'left' => $face['BoundingBox']['Left'],
                    ],
                    'files' => $this->searchSimilarFaceFiles($face),
                ];
            }
        }
        return $groupedFiles;
    }

    /**
     * 既に処理済みの顔データであるか判定
     *
     * @param array $face
     * @param array $groupedFiles
     * @return bool
     */
    private function isProcessedFace(array $face, array $groupedFiles): bool
    {
        foreach ($groupedFiles as $groupedFile) {
            foreach ($groupedFile['files'] as $file) {
                if (
                    $file['image_id'] == $face['ImageId']
                    && $file['face_id'] == $face['FaceId']
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 似た顔データを検索して取得
     *
     * @param array $face
     * @return array
     */
    private function searchSimilarFaceFiles(array $face): array
    {
        $similarFaceFiles = [];

        // 検索元データの顔を追加
        $similarFaceFiles[] = [
            'name' => $face['ExternalImageId'],
            'url' => $this->storage->url("photos/{$face['ExternalImageId']}"),
            'image_id' => $face['ImageId'],
            'face_id' => $face['FaceId'],
        ];

        $faceMatches = $this->rekognitionRepository->searchFaces($this->collectionId, $face['FaceId']);
        foreach ($faceMatches as $faceMatch) {
            $similarFaceFiles[] = [
                'name' => $faceMatch['Face']['ExternalImageId'],
                'url' => $this->storage->url("photos/{$faceMatch['Face']['ExternalImageId']}"),
                'image_id' => $faceMatch['Face']['ImageId'],
                'face_id' => $faceMatch['Face']['FaceId'],
            ];
        }
        array_multisort(array_column($similarFaceFiles, 'name'), SORT_ASC, $similarFaceFiles);
        return $similarFaceFiles;
    }
}
