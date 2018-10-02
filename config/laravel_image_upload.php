<?php

return [
    /**
     * 圖片 size 限制
     */
    'image_max_size' => 4096,

    /**
     * 圖片高度限制 (目前 Request 沒有使用)
     */
    'image_max_height' => 2048,

    /**
     * 圖片寬度限制 (目前 Request 沒有使用)
     */
    'image_max_width' => 2048,

    /**
     * 指令-檢查圖片距離幾天都沒有使用會刪除
     */
    'delete_check_days' => 1,
];