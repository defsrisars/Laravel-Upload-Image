<?php

return [
    /**
     * 使用的硬碟空間
     */
    'disk' => 'public',
    /**
     * 需補上修改 'filesystems.disks.public.root' 時，storage_path('app/public') 後方的自訂路徑
     * 比方說若修改 filesystems.disks.public.root => storage_path('app/public/uploads')
     * 需補上 uploads
     */
    'image_dir'  => 'uploads',
    /**
     * 圖片 size 限制
     */
    'image_max_size' => 4096,
    /**
     * 圖片高度限制
     */
    'image_max_height' => 2048,
    /**
     * 圖片寬度限制
     */
    'image_max_width' => 2048,
];