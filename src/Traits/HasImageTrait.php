<?php

namespace Ariby\LaravelImageUpload\Traits;

use Illuminate\Support\Facades\Storage;

trait HasImageTrait
{
    /**
     * 更新時記錄舊圖片路徑資料
     *
     * @var array
     */
    protected $oldImageFields = [];

    /**
     * boot
     */
    public static function bootHasImageTrait()
    {
        /**
         * 儲存前事件
         */
        static::creating(function ($model) {

            // 針對 model 上所有使用到 images 的 field
            foreach ($model->imagable as $field) {
                // 取得欄位
                $contents = $model->getImagesField($field);

                // 判斷這個欄位是不是陣列，因為統一先轉成陣列，所以如果原始不是陣列，後面要轉回來
                $isArrayFlag = is_array($contents);

                // 若不是陣列，也放入陣列，以陣列一致性處理
                if (!$isArrayFlag) {
                    $contents = [$contents];
                }

                // 將陣列壓成一維陣列
                $contents = array_dot($contents);

                foreach ($contents as $key => $content) {
                    // 若內容為包含 html 的內容
                    if ($model->isHtml($content)) {
                        // 透過正規表達式抓取 html 中的 src
                        // [0] => 含 <img ...>, [1] => 只有 src 裡的內容
                        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $content, $imgArray);

                        // 將所有 img 內 src 的 link，修改 __temp__ 檔名，並更改其在欄位內的值
                        // 避免一個 html 裡有多個 img ， 所以必須另外宣告參數儲存，不可用 foreach 的 $content
                        $newHtml = $content;
                        foreach ($imgArray[1] as $index => $imgLink) {
                            // 若內容為連結
//                            $dirName = dirname($imgLink); // 現在只存檔名，避免更換 domain 問題
                            $fileName = basename($imgLink);
                            // 如果檔案不存在硬碟上，或是開頭不是__temp__，直接跳過
                            if (!Storage::disk('public')->exists($fileName) || !starts_with($fileName, '__temp__')) {
                                continue;
                            } else {
                                // 如果檔案存在
                                // 將檔名上的 __temp__ 移除
                                $newFileName = ltrim($fileName, '__temp__');
                                Storage::disk('public')->move($fileName, $newFileName);
                                // 將該欄位中，舊的 link 替換成新的 link
//                                $newLink = $dirName . DIRECTORY_SEPARATOR . $newFileName; // 現在只存檔名，避免更換 domain 問題
                                $newLink = $newFileName; // 現在只存檔名，避免更換 domain 問題
                                $newHtml = str_replace($imgLink, $newLink, $newHtml);
                            }
                        }
                        $contents[$key] = $newHtml;

                    } else {
                        // 若內容為連結
//                        $dirName = dirname($content);
                        $fileName = basename($content);
                        // 如果檔案不存在硬碟上，或是開頭不是__temp__，直接跳過
                        if (!Storage::disk('public')->exists($fileName) || !starts_with($fileName, '__temp__')) {
                            continue;
                        } else {
                            // 如果檔案存在
                            // 將檔名上的 __temp__ 移除
                            $newFileName = ltrim($fileName, '__temp__');
                            Storage::disk('public')->move($fileName, $newFileName);
                            // 將欄位的值更正成沒有 __temp__ 的值
//                            $contents[$key] = $dirName . DIRECTORY_SEPARATOR . $newFileName; // 現在只存檔名，避免更換 domain 問題
                            $contents[$key] = $newFileName;
                        }

                    }
                }

                // 將陣列轉回原始陣列
                $raw = [];
                foreach ($contents as $key => $value) {
                    array_set($raw, $key, $value);
                }

                // 如果原始欄位不是陣列，從陣列抽出
                if (!$isArrayFlag) {
                    $raw = array_pop($raw);
                }

                // 將資料存回 DB
                $model->saveImagesField($field, $raw);
            }

        });

        /**
         * 刪除後事件
         */
        static::deleted(function ($model) {

            // 針對 model 上所有使用到 images 的 field
            foreach ($model->imagable as $field) {

                // 取得欄位
                $contents = $model->getImagesField($field);

                // 若不是陣列，也放入陣列，以陣列一致性處理
                if (!is_array($contents)) {
                    $contents = [$contents];
                }

                // 將陣列壓成一維陣列
                $contents = array_dot($contents);

                foreach ($contents as $key => $content) {
                    // 若內容為包含 html 的內容
                    if ($model->isHtml($content)) {
                        // 透過正規表達式抓取 html 中的 src
                        // [0] => 含 <img ...>, [1] => 只有 src 裡的內容
                        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $content, $imgArray);

                        foreach ($imgArray[1] as $index => $imgLink) {
                            // 若內容為連結
                            $fileName = basename($imgLink);
                            // 如果檔案不存在硬碟上，或是開頭不是__temp__，直接跳過
                            if (!Storage::disk('public')->exists($fileName)) {
                                continue;
                            } else {
                                // 如果檔案存在
                                // 將檔名上的 __temp__ 移除
                                $newFileName = '__temp__' . $fileName;
                                Storage::disk('public')->move($fileName, $newFileName);
                            }
                        }
                    } else {
                        // 若內容為連結
                        $fileName = basename($content);
                        // 如果檔案不存在硬碟上，或是開頭不是__temp__，直接跳過
                        if (!Storage::disk('public')->exists($fileName)) {
                            continue;
                        } else {
                            // 如果檔案存在，將檔名上的 __temp__ 補回，由排程刪除
                            $newFileName = '__temp__' . $fileName;
                            Storage::disk('public')->move($fileName, $newFileName);
                        }
                    }
                }

            }

        });

        /**
         * 更新前事件
         */
        static::updating(function ($model) {
            // 更新前之前，記錄各圖徑欄位的舊資料
            // 目的是為了當舊資料的圖檔不再被使用時(與儲存後資料不同時)
            // 將檔名補回 __temp__ ，讓排程可以移除
            // 避免拿到修改後的資料，必須重新再從資料庫抓取一次舊資料
            $oldModel = call_user_func_array([__CLASS__, 'find'], [$model->getAttribute($model->getKeyName())]);

            foreach ($model->imagable as $field) {
                $model->oldImageFields[$field] = $oldModel->getImagesField($field);
            }
        });

        /**
         * 更新後事件
         */
        static::updated(function ($model) {

            // 記錄更改數量，有更動才需要 save
            $updateNum = 0;

            // 針對 model 上所有使用到 images 的 field
            foreach ($model->imagable as $field) {
                // 取得欄位
                $contents = $model->getImagesField($field);
                $old_contents = $model->oldImageFields[$field];

                // 判斷這個欄位是不是陣列，因為統一先轉成陣列，所以如果原始不是陣列，後面要轉回來
                $isArrayFlag = is_array($contents);

                // 若不是陣列，也放入陣列，以陣列一致性處理
                if (!$isArrayFlag) {
                    $contents = [$contents];
                    $old_contents = [$old_contents];
                }

                // create 觸發的 updating 事件，直接擋下
                if (empty($old_contents)) {
                    continue;
                }

                // 將陣列壓成一維陣列
                $contents = array_dot($contents);
                $old_contents = array_dot($old_contents);

                foreach ($contents as $key => $content) {
                    $old_content = $old_contents[$key];

                    // 若內容為包含 html 的內容
                    if ($model->isHtml($content)) {
                        // 透過正規表達式抓取 html 中的 src
                        // [0] => 含 <img ...>, [1] => 只有 src 裡的內容
                        // 新的和舊的的差集就是這次還有要保留的
                        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $content, $imgArray);
                        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $old_content, $oldImgArray);

                        // 檢查新的內容，若含有 __temp__ ，將其去掉 __temp__
                        $newHtml = $content;
                        foreach ($imgArray[1] as $index => $imgLink) {
                            $dirName = dirname($imgLink);
                            $fileName = basename($imgLink);
                            // 如果檔案不存在硬碟上，不處理
                            // 開頭不是__temp__，表示為已處理之連結
                            // $old_content == $content 表示雖然有修改，但沒有修改到 img link
                            if (!Storage::disk('public')->exists($fileName) || !starts_with($fileName, '__temp__')) {
                                continue;
                            } else {
                                // 將檔名上的 __temp__ 移除
                                $newFileName = ltrim($fileName, '__temp__');
                                Storage::disk('public')->move($fileName, $newFileName);
                                // 將該欄位中，舊的 link 替換成新的 link
                                $newLink = $dirName . DIRECTORY_SEPARATOR . $newFileName;
                                $newHtml = str_replace($imgLink, $newLink, $newHtml);
                                // 更動數量 + 1
                                $updateNum++;
                            }
                        }

                        // 取得原本有，但修改之後沒有的連結 (表示應該被移除)
                        $oldArray = array_diff($oldImgArray[1], $imgArray[1]);

                        foreach ($oldArray as $index => $imgLink) {
                            $fileName = basename($imgLink);
                            // 如果檔案不存在硬碟上，不處理
                            if (!Storage::disk('public')->exists($fileName)) {
                                continue;
                            } else {
                                // 將舊檔名加回 __temp__ 讓排程移除
                                Storage::disk('public')->move(basename($imgLink), '__temp__' . basename($imgLink));
                            }
                        }

                        // 覆寫 ORM 內容
                        $contents[$key] = $newHtml;

                    } else {
                        // 若內容為連結
                        $dirName = dirname($content);
                        $fileName = basename($content);
                        // 如果檔案不存在硬碟上，或是開頭不是__temp__，或是修改前後檔名相同 直接跳過
                        if (!Storage::disk('public')->exists($fileName) || !starts_with($fileName, '__temp__') || $old_content == $content) {
                            continue;
                        } else {
                            // 如果檔案存在，有做更動
                            // 將新檔名上的 __temp__ 移除
                            $newFileName = ltrim($fileName, '__temp__');
                            Storage::disk('public')->move($fileName, $newFileName);
                            // 將舊檔名加回 __temp__ 讓排程移除
                            Storage::disk('public')->move(basename($old_content), '__temp__' . basename($old_content));
                            // 將欄位的值更正成沒有 __temp__ 的值
                            $contents[$key] = $dirName . DIRECTORY_SEPARATOR . $newFileName;
                            // 更動數量 + 1
                            $updateNum++;
                        }
                    }
                }

                // 將陣列轉回原始陣列
                $raw = [];
                foreach ($contents as $key => $value) {
                    array_set($raw, $key, $value);
                }

                // 如果原始欄位不是陣列，從陣列抽出
                if (!$isArrayFlag) {
                    $raw = array_pop($raw);
                }

                // 將資料存回 DB
                $model->saveImagesField($field, $raw);
            }

            if ($updateNum > 0) {
                $model->save();
            }

        });

    }

    /**
     * 如果有需要，覆寫此 function ，回傳結果必須為
     *
     * @param $field
     * @return mixed
     */
    public function getImagesField($field)
    {
        return $this->$field;
    }

    /**
     * 如果有需要，覆寫此 function，修改存入欄位的方式
     *
     * @param $field
     * @param $value
     */
    protected function saveImagesField($field, $value)
    {
        $this->$field = $value;
    }

    protected function isHtml($string)
    {
        if ($string != strip_tags($string)) {
            return true;
        } else {
            return false;
        }
    }

}
