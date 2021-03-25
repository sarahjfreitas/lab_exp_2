<?php

class FileUtils
{
    public static function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        self::rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }

    public static function readFullCsv($filePath, $divisor = ';')
    {
        $headerDone = false;
        $header = [];
        $results = [];

        if (($handle = fopen($filePath, "r")) !== false) {
            while (($data = fgetcsv($handle, 0, $divisor)) !== false) {
                if (!$headerDone) {
                    $header = $data;
                    $headerDone = true;
                } else {
                    $line = [];
                    $columnCount = 0;
                    foreach ($data as $value) {
                        $line[$header[$columnCount]] = $value;
                        $columnCount++;
                    }

                    $results[] = $line;
                }
            }
            fclose($handle);

            return $results;
        }
    }

    public static function addLineToCsv($filePath,$line,$csvDelimiter=';'){
        if (($handle = fopen($filePath, "a")) !== false) {
            fputcsv($handle, $line, $csvDelimiter);
            fclose($handle);
        }
    }
}
