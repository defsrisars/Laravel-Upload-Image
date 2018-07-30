<?php

namespace Ariby\tests\database\Models;

use Illuminate\Database\Eloquent\Model;
use Liaosankai\LaravelEloquentI18n\Models\TranslationTrait;
use Ariby\LaravelImageUpload\Traits\HasImageTrait;
use Ariby\Ulid\HasUlid;

/**
 * 輪播圖
 *
 * Class Slider
 *
 * @property char id
 * @property string title 輪播圖標題
 * @property string image_link_desktop 輪播圖大圖連結(for 電腦)
 * @property string image_link_mobile 輪播圖小圖連結(for 手機)
 * @property datetime created_at
 * @property datetime updated_at
 * @package App\Models
 */
class Slider extends Model
{
    use HasUlid;
    use HasImageTrait;
    use TranslationTrait;

    protected $fillable = [
        'title',
        'image_link_desktop',
        'image_link_mobile',
    ];

    protected $i18nable = [
        'title',
        'image_link_desktop',
    ];

    protected $imagable = [
        'image_link_desktop', // link + 多語系
        'image_link_mobile', // link 無 多語系
    ];

    /**
     * @var array
     */
    protected $casts = [
        'id' => 'string'
    ];

    protected $attributes = [
        'title' => '',
        'image_link_desktop' => '',
        'image_link_mobile' => '',
    ];

    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    /**
     * Laravel-Image-Upload，如果有需要，覆寫此 function
     *
     * @param $field
     * @return mixed
     */
    protected function getImagesField($field)
    {
        if(in_array($field, $this->i18nable)){
            return $this->i18n()->$field;
        }else{
            return $this->$field;
        }
    }

    /**
     * Laravel-Image-Upload，如果有需要，覆寫此 function，修改存入欄位的方式
     *
     * @param $field
     * @param $value
     */
    protected function saveImagesField($field, $value)
    {
        $this->$field = $value;
    }

}