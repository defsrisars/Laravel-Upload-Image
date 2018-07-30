<?php

namespace Ariby\tests\database\Models;

use Ariby\LaravelImageUpload\Traits\HasImageTrait;
use Liaosankai\LaravelEloquentI18n\Models\TranslationTrait;
use Illuminate\Database\Eloquent\Model;
use Ariby\Ulid\HasUlid;

/**
 * 文章
 *
 * Class Article
 *
 * @property char id
 * @property longText title 問題
 * @property longText content 答案
 * @property datetime created_at
 * @property datetime updated_at
 * @package App\Models
 */
class Article extends Model
{
    use HasUlid;
    use HasImageTrait;
    use TranslationTrait;

    public $fillable = [
        'title',
        'content',
    ];

    public $i18nable = ['content'];

    public $imagable = [
        'title',
        'content',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'id' => 'string'
    ];

    protected $attributes = [
        'title' => '',
        'content' => '',
    ];

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