<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller {

    //
    public function index(Request $request) {
        ini_set('max_execution_time', '0');
        $video_name = $request->get("video_name");
        $m3u8CatalogUrl = $request->get("m3u8_url");
        $m3u8CatalogStr = file_get_contents($m3u8CatalogUrl);
        $m3u8CatalogArr = explode("\n", $m3u8CatalogStr);
        $tsCatalog = [];
        $m3u8CatalogUrlArr = explode("/", $m3u8CatalogUrl);
        foreach ($m3u8CatalogArr as $value) {
            if (strstr($value, ".ts") && !strstr($value, "#")) {
                $tsCatalog[] = str_replace(end($m3u8CatalogUrlArr), $value, $m3u8CatalogUrl);
            }
        }
        $dir = storage_path('app/public');
        $path = sprintf('/%s/', $video_name);
        $tmp_dir = $dir . $path;
        if (!file_exists($tmp_dir)) {
            @mkdir($tmp_dir, 0755, true);
        }
        foreach ($tsCatalog as $v) {
            $arr = parse_url($v);
            $fileName = basename($arr['path']);
            try {
                $file = file_get_contents($v);
                file_put_contents($tmp_dir . $fileName, $file);
            } catch (\Exception $exc) {
                \Log::error($exc->getTraceAsString());
            }
        }
        $zip_file = $this->zip_files($video_name, $tmp_dir);
        return response()->download($zip_file);
    }

    private function zip_files($video_name, $tmp_dir) {
        $zip_file = storage_path('app/public') . "/{$video_name}.zip";
        $zip = new \ZipArchive();
        $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmp_dir));
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = "{$video_name}/" . substr($filePath, strlen($tmp_dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        return $zip_file;
    }

}
