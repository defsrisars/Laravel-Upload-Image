# Laravel 圖片上傳

## 介紹

此套件可用來管理圖片上傳，其中包括圖片上傳路由與控制器，使用 Laravel 內建的 Storage 來做檔案上傳與管理，並且可在上傳時檢查圖片大小，並設置圖片長寬上限做等比例縮圖。

此套件適合使用在所有資料庫欄位會儲存「包含 \<\img src="....">」或「http://.....abc.jpg」之資料結構，檔案在上傳後，會被加上前綴「\_\_temp\_\_」，而且 ORM 新增、修改時，會自動偵側檔案在硬碟中是否存在，並將檔名之前綴「\_\_temp\_\_」移除，表示檔案使用中，而在檔案刪除時，會將前綴「\_\_temp\_\_」補回，表示該檔案目前沒有被使用。

套件會自動去檢查您的欄位儲存的是 html 結構，還是單純的 img url link，並且使檔案與 ORM 狀態同步，並且提供一個刪除所有「\_\_temp\_\_」檔的 Job 和 Command。

##  安裝

以 composer 安裝

    composer require ariby/laravel-image-upload
    
然後必須在專案目錄下

    php artisan storage:link
    
建立軟連結，可參考 [Laravel 官方文件](https://docs.laravel-dojo.com/laravel/5.5/filesystem#the-public-disk)
    
## 發佈設定檔

在安裝套件後，下 `php artisan vendor:publish` ，並選擇發佈 `Ariby\LaravelImageUpload\LaravelImageUploadServiceProvider`
    
## 路由設定

您必須使用套件提供的路由來為您做圖片上傳的處理，其中包括檢查、縮圖與檔名的統一格式處理，因此必須在 `RouteServiceProvider` 中註冊路由

    use Ariby\LaravelImageUpload\Facades\LaravelImageUpload;

    class RouteServiceProvider extends ServiceProvider
    {
        ...
    
        public function boot()
        {
            parent::boot();
    
            ...
    
            // 圖片上傳套件路由註冊
            LaravelImageUpload::routes();
            
            ...
        }
        
        ...
        
    }
    
路由設定之 url 為 `/laravel/image-upload` ， method 必須為 `post` ， route name 為 `imgUpload`，每次接受一張圖檔(若是多圖檔請以 ajax 戳多次)，欄位名稱為 `file` ，回傳格式為 `json` 格式如下：

    {
        url: "http://domain.com/img.jpg" // 在專案公司目錄(如 public 轉換後的 url),
        basename: "img.jpg" // 只含檔名與副檔名
    }

## Model 使用

使用者必須在會儲存圖片資訊的 model 加上 Trait：

    use HasImageTrait;
    
並寫入會存入「包含 <img src="....">」或「http://.....abc.jpg」的資料欄位如下：

    protected $imagable = [
        'image_link_desktop',
        'image_link_mobile',
    ];
    
若是您有使用多語系之類的套件，改變了屬性的存取，您必須覆寫指定的 set 與 get 函式，完整的範例 model 如下：

    <?php
    
    ...
    use Ariby\LaravelImageUpload\Traits\HasImageTrait;
    ...
    
    /**
     * 輪播圖
     *
     * Class Slider
     *
     * @property char id
     * @property string title 輪播圖標題
     * @property string image_link_desktop 輪播圖大圖連結(for 電腦) 有使用多語系
     * @property string image_link_mobile 輪播圖小圖連結(for 手機) 沒有使用多語系
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
            'image_link_desktop', // 直接存放 img link 並有使用多語系
            'image_link_mobile', // 單純直接存放 img link 無多語系
        ];
        
        /**
         * 如果有需要，覆寫此 function ，回傳結果必須為
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
         * 如果有需要，覆寫此 function，修改存入欄位的方式
         *
         * @param $field
         * @param $value
         */
        protected function saveImagesField($field, $value)
        {
            $this->$field = $value;
        }
    
    }
    
    
## 縮圖路由

一旦存入 DB 後，可透過縮圖路由取得縮圖後的圖片，路由規則為`/imgfly/{img}/{size?}`，img 為圖片在專案中 storage 資料夾中的檔名，size 組成方式為 w數字&h數字

比方說如下連結，會取得寬為 200 ， 高為 300 的縮圖，**若只傳入寬或高，另一屬性將一原圖等比例縮放**

`http://domain/imgfly/I2k9ypuMhMS2WexVoN4nuDeVzOeYHTOZY7JLufLy.jpeg/w200&h300`
    
## 排程使用

以下為排程範例

    <?php
    
    // app\Console\Kernel
    
    namespace App\Console;
    
    ...
    use Ariby\LaravelImageUpload\Commands\RoutineClearUselessImages;
    ...
    
    class Kernel extends ConsoleKernel
    {
        protected $commands = [
            ...
            RoutineClearUselessImages::class,
            ...
        ];
    
        protected function schedule(Schedule $schedule)
        {
            ...
            
            // 檢查刪除沒有用到的圖片
            $schedule->command('RoutineClearUselessImages:check-and-clear')->days(2);
            
            ...
        }
        
        ...
    }

或者是可以 Job 來執行

    use Ariby\LaravelImageUpload\Jobs\ClearUselessImagesJob;
    
    ...
    ClearUselessImagesJob::dispatch()
    ...
    
呼叫後會刪除所有檔名前綴為  \_\_temp\_\_ 的檔案(表示該檔名沒有被任何資料庫中的欄位所使用)

可在發佈 config 檔後，在 delete_check_days 設定天數，假設 = 1 ，則會刪除 1 天以上沒有使用的__temp__檔名圖片