<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class FileController extends Controller
{
//    public $uploadPath = '/uploads';
    public $uploadPath = '/public/storage/uploads';

    public function files()
    {
        $files = [];
        if (file_exists(base_path() . $this->uploadPath)) {
            $files = scandir(base_path() . $this->uploadPath, SCANDIR_SORT_DESCENDING);
        }

        array_splice($files, -2);

        return view('files', get_defined_vars());
    }

    /**
     * @throws UploadMissingFileException
     * @throws UploadFailedException
     */
    public function upload(Request $request): JsonResponse
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {
            return $this->saveFile($save->getFile());
        }

        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            'status' => true
        ]);
    }

    protected function saveFile(UploadedFile $file)
    {
        $fileName = $file->getClientOriginalName();
        $file->move(base_path() . $this->uploadPath, $fileName);

        $filePath = $this->uploadPath . $fileName;

        return response()->json([
            'path' => $filePath,
            'name' => $fileName
        ]);
    }
}
