<?php

$tmpPath = __DIR__ . '/tmp';
if (!file_exists($tmpPath)) {
    mkdir($tmpPath, 0777);
}

$targetPath = __DIR__ . '/files';
if (!file_exists($targetPath)) {
    mkdir($targetPath, 0777);
}

$fh = fopen(__DIR__ . '/list.csv', 'w');
fputcsv($fh, array(
    '會議序號',
    '會議日期',
    '計畫名稱',
    '摘要說明',
    '文件檔案',
    '原始網址',
));

$urls = array();

//copied from http://bud.tncg.gov.tw/A94032P/PlanMeeting/PM_Query.aspx
$listPage = mb_convert_encoding(file_get_contents(__DIR__ . '/original.html'), 'utf-8', 'big5');
$listPage = str_replace('&nbsp;', ' ', $listPage);
$lines = explode('</tr>', $listPage);
$lines[0] = substr($lines[0], strpos($lines[0], '<tr'));

foreach ($lines AS $line) {
    $cols = explode('</td>', $line);
    if (isset($cols[4]) && false !== strpos($cols[4], 'MGDocFiles')) {
        $parts = explode('MGDocFiles/', $cols[4]);
        $parts[1] = substr($parts[1], 0, strpos($parts[1], '"'));
        foreach ($cols AS $k => $v) {
            $cols[$k] = trim(strip_tags($v));
        }
        /*
         * http://bud.tncg.gov.tw/A94032P/MGDocFiles/ -> Files/00030071/0000000039/9301229.htm
         */
        $parts = explode('/', $parts[1]);
        foreach ($parts AS $pK => $pV) {
            $parts[$pK] = urlencode($pV);
        }
        $cols[5] = 'http://bud.tncg.gov.tw/A94032P/MGDocFiles/' . implode('/', $parts);

        if (!isset($urls[$cols[5]])) {
            $p = pathinfo($cols[5]);
            $urls[$cols[5]] = sha1($cols[5]);
            $cols[4] = $urls[$cols[5]] . '.' . $p['extension'];

            $targetFile = $targetPath . '/' . $cols[4];
            if (!file_exists($targetFile) || filesize($targetFile) < 10) {
                file_put_contents($targetFile, file_get_contents($cols[5]));
            }

            fputcsv($fh, $cols);

            if ($p['extension'] === 'htm') {
                $thePage = file_get_contents($targetFile);
            }
        }
    }
}