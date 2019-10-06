<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller {

    //
    public function index(Request $request) {
        ini_set('max_execution_time', '0');
        $video_name = $request->get("video_name") ?? \Str::uuid();
        $m3u8CatalogUrl = $request->get("m3u8_url", "");
        if ($m3u8CatalogUrl == "") {
            return view('welcome');
        }
        $m3u8CatalogStr = file_get_contents($m3u8CatalogUrl);
        $m3u8CatalogArr = explode("\n", $m3u8CatalogStr);
        $tsCatalog = [];
        $m3u8CatalogUrlArr = explode("/", $m3u8CatalogUrl);
        foreach ($m3u8CatalogArr as $value) {
            if (strstr($value, ".ts") && !strstr($value, "#")) {
                $tmp = parse_url($value);
                if (isset($tmp["host"])) {
                    $tsCatalog[] = $value;
                } else {
                    $tsCatalog[] = str_replace(end($m3u8CatalogUrlArr), $value, $m3u8CatalogUrl);
                }
            }
        }
        if ($request->get("yes", "") == "") {
            return view('welcome')->with("tsCatalog", implode("\n", $tsCatalog));
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
        $copy_dir = storage_path('app\public') . "\\" . $video_name . "\\";
        if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
            $systemStr = "copy /b {$copy_dir}*.ts {$copy_dir}join.ts";
        } else {
            $systemStr = "cat {$tmp_dir}*.ts >> {$tmp_dir}join.ts";
        }
        try {
            system($systemStr);
        } catch (\Exception $exc) {
            echo "合并失败，请手动合并，命令如下：";
            echo PHP_EOL;
            echo $systemStr;
            echo PHP_EOL;
            \Log::error($exc->getTraceAsString());
        }
        $this->zip_files($video_name, $tmp_dir);
        echo "下载完成，目录：{$copy_dir}";
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
    }

}
