<?php
if (!function_exists('html_img_src_to_basename')) {
    /**
     * 將 html 的 「<img ...>」 字串 src 只留下 basename
     *
     * @param string $str
     * @return string
     */
    function html_img_src_to_basename($str)
    {
        // 透過正規表達式抓取 html 中的 src
        // [0] => 含 <img ...>, [1] => 只有 src 裡的內容
        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $str, $imgArray, PREG_OFFSET_CAPTURE);

        $dataArray = $imgArray[1];

        foreach ($dataArray as $data) {
            $str = str_replace($data[0], basename($data[0]), $str);
        }

        return $str;
    }
}

if (!function_exists('html_img_src_to_url')) {
    /**
     * 將 html 的 「<img ...>」 字串 src 從只有 basename 補上 domain 和存放圖片的資料夾路徑
     *
     * @param string $str
     * @return string
     */
    function html_img_src_to_url($str)
    {
        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $str, $imgArray, PREG_OFFSET_CAPTURE);

        $dataArray = $imgArray[1];

        foreach ($dataArray as $data) {
            $str = str_replace($data[0], asset('imgfly/' . $data[0]), $str);
        }

        return $str;
    }
}