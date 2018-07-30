<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Ariby\LaravelImageUpload\Facades\LaravelImageUpload;
use Ariby\tests\database\Models\Article;
use Ariby\tests\database\Models\Slider;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\File\File;
use Ariby\LaravelImageUpload\Jobs\ClearUselessImagesJob;
use Carbon\Carbon;

class ImageUploadTest extends BaseTestCase
{

    protected function setUp()
    {
        parent::setUp();

        // 初始化 User Table，測試需要的假 User 寫入此 Table
        $this->initTestTable();

        // 注入此套件會使用到的 database 相關東西
        $this->injectDatabase();

        // 注入 Router
        LaravelImageUpload::routes();

        // 建立連結符號
        Artisan::call('storage:link');
    }

    public function testBasicTest()
    {
        $this->assertTrue(true);
    }

    /**
     * !!! 此測試 model 資料為 單一 Link 與使用多語系的 Array Link !!!
     * 測試儲存 Model 時，確認硬碟有該 __temp__ 圖檔時，應將檔名 __temp__ 移除
     * 並將資料庫連結修正為正確的連結
     */
    public function testCreatingLinkModelTrait()
    {
        // 模擬按照流程上傳四張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-1.jpg'), '__temp__slider2.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-3.jpg'), '__temp__slider3.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-2.jpg'), '__temp__slider4.jpeg');

        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $slider = new Slider;
        $slider->title = [
            'en' => 'english',
            'zh-Hant' => '繁體中文',
            'zh-Hans' => '简体中文',
        ];
        $slider->image_link_desktop = [
            'en' => 'http://localhost/storage/__temp__slider1.jpeg',
            'zh-Hant' => 'http://localhost/storage/__temp__slider2.jpeg',
            'zh-Hans' => 'http://localhost/storage/__temp__slider3.jpeg',
        ];
        $slider->image_link_mobile = 'http://localhost/storage/__temp__slider4.jpeg';
        $slider->save();

        // 檔名應被正確的修改，移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider3.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider4.jpeg'));

        // 將被修改的檔名改回來，並刪除測試用的檔案
        Storage::disk('public')->move('slider1.jpeg', '__temp__slider1.jpeg');
        Storage::disk('public')->move('slider2.jpeg', '__temp__slider2.jpeg');
        Storage::disk('public')->move('slider3.jpeg', '__temp__slider3.jpeg');
        Storage::disk('public')->move('slider4.jpeg', '__temp__slider4.jpeg');
        Storage::disk('public')->delete('__temp__slider1.jpeg');
        Storage::disk('public')->delete('__temp__slider2.jpeg');
        Storage::disk('public')->delete('__temp__slider3.jpeg');
        Storage::disk('public')->delete('__temp__slider4.jpeg');

        // 確認資料庫的資料應該是修改後無 __temp__ 的路徑
        $this->assertEquals([
            "en" => "http://localhost/storage/slider1.jpeg",
            "zh-Hant" => "http://localhost/storage/slider2.jpeg",
            "zh-Hans" => "http://localhost/storage/slider3.jpeg"
        ], Slider::first()->i18n()->image_link_desktop);
        $this->assertEquals('http://localhost/storage/slider4.jpeg', Slider::first()->image_link_mobile);
    }

    /**
     * !!! 此測試 model 資料為 單一 Html 與使用多語系的 Array Html !!!
     * 測試儲存 Model 時，確認硬碟有該 __temp__ 圖檔時，應將檔名 __temp__ 移除
     * 並將資料庫連結修正為正確的連結
     */
    public function testCreatingHtmlModelTrait()
    {
        // 模擬按照流程上傳四張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-1.jpg'), '__temp__slider2.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-3.jpg'), '__temp__slider3.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-2.jpg'), '__temp__slider4.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hans-0.jpg'), '__temp__slider5.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/en-1.jpg'), '__temp__slider6.jpeg');

        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $article = new Article;
        $article->title = '<html><img alt="Hello" src="http://localhost/storage/__temp__slider4.jpeg"></html>';
        $article->content = [
            'en' => '<html><div><img alt="HelloA1" src="http://localhost/storage/__temp__slider1.jpeg"></div>
                            <div><img alt="HelloA2" src="http://localhost/storage/__temp__slider5.jpeg"></div>
                            <div><img alt="HelloA3" src="http://localhost/storage/__temp__slider6.jpeg"></div>
                            </html>',
            'zh-Hant' => '<p><img alt="HelloB" src="http://localhost/storage/__temp__slider2.jpeg"></p>',
            'zh-Hans' => '<span><img src="http://localhost/storage/__temp__slider3.jpeg" alt="HelloC"></span>',
        ];
        $article->save();

        // 檔名應被正確的修改，移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider3.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider4.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider5.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider6.jpeg'));

        // 將被修改的檔名改回來，並刪除測試用的檔案
        Storage::disk('public')->move('slider1.jpeg', '__temp__slider1.jpeg');
        Storage::disk('public')->move('slider2.jpeg', '__temp__slider2.jpeg');
        Storage::disk('public')->move('slider3.jpeg', '__temp__slider3.jpeg');
        Storage::disk('public')->move('slider4.jpeg', '__temp__slider4.jpeg');
        Storage::disk('public')->move('slider5.jpeg', '__temp__slider5.jpeg');
        Storage::disk('public')->move('slider6.jpeg', '__temp__slider6.jpeg');
        Storage::disk('public')->delete('__temp__slider1.jpeg');
        Storage::disk('public')->delete('__temp__slider2.jpeg');
        Storage::disk('public')->delete('__temp__slider3.jpeg');
        Storage::disk('public')->delete('__temp__slider4.jpeg');
        Storage::disk('public')->delete('__temp__slider5.jpeg');
        Storage::disk('public')->delete('__temp__slider6.jpeg');

        // 確認資料庫的資料應該是修改後無 __temp__ 的路徑
        $this->assertEquals('<html><img alt="Hello" src="http://localhost/storage/slider4.jpeg"></html>', Article::first()->title);
        $this->assertEquals([
            'en' => '<html><div><img alt="HelloA1" src="http://localhost/storage/slider1.jpeg"></div>
                            <div><img alt="HelloA2" src="http://localhost/storage/slider5.jpeg"></div>
                            <div><img alt="HelloA3" src="http://localhost/storage/slider6.jpeg"></div>
                            </html>',
            'zh-Hant' => '<p><img alt="HelloB" src="http://localhost/storage/slider2.jpeg"></p>',
            'zh-Hans' => '<span><img src="http://localhost/storage/slider3.jpeg" alt="HelloC"></span>',
        ], Article::first()->i18n()->content);
    }

    /**
     * !!! 此測試 model 資料為 單一 Link 與使用多語系的 Array Link !!!
     * 測試刪除 Model 時，確認硬碟有該圖檔時，應將檔名加回 __temp__ 由排程移除
     */
    public function testDeletedLinkModelTrait()
    {
        // 模擬按照流程上傳四張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-1.jpg'), '__temp__slider2.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-3.jpg'), '__temp__slider3.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-2.jpg'), '__temp__slider4.jpeg');

        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $slider = new Slider;
        $slider->title = [
            'en' => 'english',
            'zh-Hant' => '繁體中文',
            'zh-Hans' => '简体中文',
        ];
        $slider->image_link_desktop = [
            'en' => 'http://localhost/storage/__temp__slider1.jpeg',
            'zh-Hant' => 'http://localhost/storage/__temp__slider2.jpeg',
            'zh-Hans' => 'http://localhost/storage/__temp__slider3.jpeg',
        ];
        $slider->image_link_mobile = 'http://localhost/storage/__temp__slider4.jpeg';
        $slider->save();

        // 檔名應在新增後被正確的修改，移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider3.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider4.jpeg'));

        // 將 model 移除
        $slider->delete();

        // 檔名應被正確的修改，補回 __temp__
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider3.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider4.jpeg'));

        // 刪除測試用的檔案
        Storage::disk('public')->delete('__temp__slider1.jpeg');
        Storage::disk('public')->delete('__temp__slider2.jpeg');
        Storage::disk('public')->delete('__temp__slider3.jpeg');
        Storage::disk('public')->delete('__temp__slider4.jpeg');

        // 確認資料庫的資料已被刪除
        $this->assertEmpty(Slider::all());
    }

    /**
     * !!! 此測試 model 資料為 單一 Html 與使用多語系的 Array Html !!!
     * 測試刪除 Model 時，確認硬碟有該圖檔時，應將檔名加回 __temp__ 由排程移除
     */
    public function testDeletedHtmlModelTrait()
    {
        // 模擬按照流程上傳四張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-1.jpg'), '__temp__slider2.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-3.jpg'), '__temp__slider3.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-2.jpg'), '__temp__slider4.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hans-0.jpg'), '__temp__slider5.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/en-1.jpg'), '__temp__slider6.jpeg');

        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $article = new Article;
        $article->title = '<html><img alt="Hello" src="http://localhost/storage/__temp__slider4.jpeg"></html>';
        $article->content = [
            'en' => '<html><div><img alt="HelloA1" src="http://localhost/storage/__temp__slider1.jpeg"></div>
                            <div><img alt="HelloA2" src="http://localhost/storage/__temp__slider5.jpeg"></div>
                            <div><img alt="HelloA3" src="http://localhost/storage/__temp__slider6.jpeg"></div>
                            </html>',
            'zh-Hant' => '<p><img alt="HelloB" src="http://localhost/storage/__temp__slider2.jpeg"></p>',
            'zh-Hans' => '<span><img src="http://localhost/storage/__temp__slider3.jpeg" alt="HelloC"></span>',
        ];
        $article->save();

        // 檔名應在新增後被正確的修改，移除 __temp__
        // 檔名應被正確的修改，移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider3.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider4.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider5.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider6.jpeg'));

        // 將 model 移除
        $article->delete();

        // 檔名應被正確的修改，補回 __temp__
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider3.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider4.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider5.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider6.jpeg'));

        // 刪除測試用的檔案
        Storage::disk('public')->delete('__temp__slider1.jpeg');
        Storage::disk('public')->delete('__temp__slider2.jpeg');
        Storage::disk('public')->delete('__temp__slider3.jpeg');
        Storage::disk('public')->delete('__temp__slider4.jpeg');
        Storage::disk('public')->delete('__temp__slider5.jpeg');
        Storage::disk('public')->delete('__temp__slider6.jpeg');

        // 確認資料庫的資料已被刪除
        $this->assertEmpty(Slider::all());
    }

    /**
     * !!! 此測試 model 資料為 單一 Link 與使用多語系的 Array Link !!!
     * 測試修改 Model 時，若連結有 __temp__ 則將檔名修改，並修改資料欄位為新路徑
     */
    public function testUpdatedLinkModelTrait()
    {
        // 模擬按照流程上傳四張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-1.jpg'), '__temp__slider2.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-3.jpg'), '__temp__slider3.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-2.jpg'), '__temp__slider4.jpeg');

        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $slider = new Slider;
        $slider->title = [
            'en' => 'english',
            'zh-Hant' => '繁體中文',
            'zh-Hans' => '简体中文',
        ];
        $slider->image_link_desktop = [
            'en' => 'http://localhost/storage/__temp__slider1.jpeg',
            'zh-Hant' => 'http://localhost/storage/__temp__slider2.jpeg',
            'zh-Hans' => 'http://localhost/storage/__temp__slider3.jpeg',
        ];
        $slider->image_link_mobile = 'http://localhost/storage/__temp__slider4.jpeg';
        $slider->save();

        // 檔名應在新增後被正確的修改，移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider3.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider4.jpeg'));

        // 將 model 修改，假設改用別的圖片
        // 模擬按照流程上傳兩張新圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hans-0.jpg'), '__temp__slider5.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/en-1.jpg'), '__temp__slider6.jpeg');
        $slider->i18n('zh-Hant')->image_link_desktop = 'http://localhost/storage/__temp__slider5.jpeg';
        $slider->image_link_mobile = 'http://localhost/storage/__temp__slider6.jpeg';
        $slider->save();

        // 檔名應被正確的修改，被移除的舊圖片被補回 __temp__，新圖片移除 __temp__，其餘不變
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg')); // 不變
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider2.jpeg')); // 被移除的舊圖片
        $this->assertTrue(Storage::disk('public')->exists('slider3.jpeg')); // 不變
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider4.jpeg')); // 被移除的舊圖片
        $this->assertTrue(Storage::disk('public')->exists('slider5.jpeg')); // 新增的圖片
        $this->assertTrue(Storage::disk('public')->exists('slider6.jpeg')); // 新增的圖片

        // 刪除測試用的檔案
        Storage::disk('public')->delete('slider1.jpeg');
        Storage::disk('public')->delete('__temp__slider2.jpeg');
        Storage::disk('public')->delete('slider3.jpeg');
        Storage::disk('public')->delete('__temp__slider4.jpeg');
        Storage::disk('public')->delete('slider5.jpeg');
        Storage::disk('public')->delete('slider6.jpeg');

        // 確認資料庫的資料應該是修改後的正確路徑
        $this->assertEquals([
            "en" => "http://localhost/storage/slider1.jpeg",
            "zh-Hant" => "http://localhost/storage/slider5.jpeg",
            "zh-Hans" => "http://localhost/storage/slider3.jpeg"
        ], Slider::first()->i18n()->image_link_desktop);
        $this->assertEquals('http://localhost/storage/slider6.jpeg', Slider::first()->image_link_mobile);
    }

    /**
     * !!! 此測試 model 資料為 單一 Html 與使用多語系的 Array Html !!!
     *
     * 測試修改 Model 時，若連結有 __temp__ 則將檔名修改，並修改資料欄位為新路徑
     * 三張替換
     */
    public function testUpdatedHtmlModelTrait()
    {
        // 模擬按照流程上傳六張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-1.jpg'), '__temp__slider2.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-3.jpg'), '__temp__slider3.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-2.jpg'), '__temp__slider4.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hans-0.jpg'), '__temp__slider5.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/en-1.jpg'), '__temp__slider6.jpeg');

        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $article = new Article;
        $article->title = '<html><img alt="Hello" src="http://localhost/storage/__temp__slider4.jpeg"></html>';
        $article->content = [
            'en' => '<html><div><img alt="HelloA1" src="http://localhost/storage/__temp__slider1.jpeg"></div>
                            <div><img alt="HelloA2" src="http://localhost/storage/__temp__slider5.jpeg"></div>
                            <div><img alt="HelloA3" src="http://localhost/storage/__temp__slider6.jpeg"></div>
                            </html>',
            'zh-Hant' => '<p><img alt="HelloB" src="http://localhost/storage/__temp__slider2.jpeg"></p>',
            'zh-Hans' => '<span><img src="http://localhost/storage/__temp__slider3.jpeg" alt="HelloC"></span>',
        ];
        $article->save();

        // 檔名應被正確的修改，移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider3.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider4.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider5.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider6.jpeg'));

        // 將 model 修改，假設改用別的圖片
        // 模擬按照流程上傳三張新圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hans-3.jpg'), '__temp__slider7.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/en-3.jpg'), '__temp__slider8.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/en-2.jpg'), '__temp__slider9.jpeg');
        $article->title = '<html><img alt="Hello" src="http://localhost/storage/__temp__slider7.jpeg"></html>';
        $article->i18n('en')->content = '<html><div><img alt="HelloA1" src="http://localhost/storage/slider1.jpeg"></div>
                            <div><img alt="HelloA2" src="http://localhost/storage/__temp__slider8.jpeg"></div>
                            <div><img alt="HelloA3" src="http://localhost/storage/slider6.jpeg"></div>
                            </html>';
        $article->i18n('zh-Hans')->content = '<span><img src="http://localhost/storage/__temp__slider9.jpeg" alt="HelloC"></span>';
        $article->save();

        // 檔名應被正確的修改，被移除的舊圖片被補回 __temp__，新圖片移除 __temp__，其餘不變
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg')); // 不變
        $this->assertTrue(Storage::disk('public')->exists('slider2.jpeg')); // 不變
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider3.jpeg')); // 被移除的舊圖片
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider4.jpeg')); // 被移除的舊圖片
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider5.jpeg')); // 被移除的舊圖片
        $this->assertTrue(Storage::disk('public')->exists('slider6.jpeg')); // 不變
        $this->assertTrue(Storage::disk('public')->exists('slider7.jpeg')); // 新增的圖片
        $this->assertTrue(Storage::disk('public')->exists('slider8.jpeg')); // 新增的圖片
        $this->assertTrue(Storage::disk('public')->exists('slider9.jpeg')); // 新增的圖片

        // 刪除測試用的檔案
        Storage::disk('public')->delete('slider1.jpeg');
        Storage::disk('public')->delete('slider2.jpeg');
        Storage::disk('public')->delete('__temp__slider3.jpeg');
        Storage::disk('public')->delete('__temp__slider4.jpeg');
        Storage::disk('public')->delete('__temp__slider5.jpeg');
        Storage::disk('public')->delete('slider6.jpeg');
        Storage::disk('public')->delete('slider7.jpeg');
        Storage::disk('public')->delete('slider8.jpeg');
        Storage::disk('public')->delete('slider9.jpeg');

        // 確認資料庫的資料應該是修改後的正確路徑
        $this->assertEquals('<html><img alt="Hello" src="http://localhost/storage/slider7.jpeg"></html>', Article::first()->title);
        $this->assertEquals([
            'en' => '<html><div><img alt="HelloA1" src="http://localhost/storage/slider1.jpeg"></div>
                            <div><img alt="HelloA2" src="http://localhost/storage/slider8.jpeg"></div>
                            <div><img alt="HelloA3" src="http://localhost/storage/slider6.jpeg"></div>
                            </html>',
            'zh-Hant' => '<p><img alt="HelloB" src="http://localhost/storage/slider2.jpeg"></p>',
            'zh-Hans' => '<span><img src="http://localhost/storage/slider9.jpeg" alt="HelloC"></span>',
        ], Article::first()->i18n()->content);
    }

    /**
     * !!! 此測試 model 資料為 單一 Html 與使用多語系的 Array Html !!!
     *
     * 測試修改 Model 時，若連結有 __temp__ 則將檔名修改，並修改資料欄位為新路徑
     * 三張新增
     */
    public function testUpdatedHtmlModelTrait2()
    {
        // 模擬按照流程上傳六張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-1.jpg'), '__temp__slider2.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-3.jpg'), '__temp__slider3.jpeg');

        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $article = new Article;
        $article->title = '<html></html>';
        $article->content = [
            'en' => '<html><div></div>
                            <div><img alt="HelloA2" src="http://localhost/storage/__temp__slider1.jpeg"></div>
                            <div><img alt="HelloA3" src="http://localhost/storage/__temp__slider2.jpeg"></div>
                            </html>',
            'zh-Hant' => '<p><img alt="HelloB" src="http://localhost/storage/__temp__slider3.jpeg"></p>',
            'zh-Hans' => '<span></span>',
        ];
        $article->save();

        // 檔名應被正確的修改，移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider3.jpeg'));

        // 將 model 修改，假設改用別的圖片
        // 模擬按照流程上傳三張新圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-2.jpg'), '__temp__slider4.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hans-0.jpg'), '__temp__slider5.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/en-1.jpg'), '__temp__slider6.jpeg');
        $article->title = '<html><img alt="Hello" src="http://localhost/storage/__temp__slider4.jpeg"></html>';
        $article->i18n('en')->content = '<html><div><img alt="HelloA1" src="http://localhost/storage/__temp__slider5.jpeg"></div>
                            <div><img alt="HelloA2" src="http://localhost/storage/slider1.jpeg"></div>
                            <div><img alt="HelloA3" src="http://localhost/storage/slider2.jpeg"></div>
                            </html>';
        $article->i18n('zh-Hant')->content = '<p><img alt="HelloB" src="http://localhost/storage/__temp__slider6.jpeg"></p>';
        $article->save();

        // 檔名應被正確的修改，被移除的舊圖片被補回 __temp__，新圖片移除 __temp__，其餘不變
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg')); // 不變
        $this->assertTrue(Storage::disk('public')->exists('slider2.jpeg')); // 不變
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider3.jpeg')); // 刪除
        $this->assertTrue(Storage::disk('public')->exists('slider4.jpeg')); // 新增
        $this->assertTrue(Storage::disk('public')->exists('slider5.jpeg')); // 新增
        $this->assertTrue(Storage::disk('public')->exists('slider6.jpeg')); // 新增

        // 刪除測試用的檔案
        Storage::disk('public')->delete('slider1.jpeg');
        Storage::disk('public')->delete('slider2.jpeg');
        Storage::disk('public')->delete('__temp__slider3.jpeg');
        Storage::disk('public')->delete('slider4.jpeg');
        Storage::disk('public')->delete('slider5.jpeg');
        Storage::disk('public')->delete('slider6.jpeg');

        // 確認資料庫的資料應該是修改後的正確路徑
        $this->assertEquals('<html><img alt="Hello" src="http://localhost/storage/slider4.jpeg"></html>', Article::first()->title);
        $this->assertEquals([
            'en' => '<html><div><img alt="HelloA1" src="http://localhost/storage/slider5.jpeg"></div>
                            <div><img alt="HelloA2" src="http://localhost/storage/slider1.jpeg"></div>
                            <div><img alt="HelloA3" src="http://localhost/storage/slider2.jpeg"></div>
                            </html>',
            'zh-Hant' => '<p><img alt="HelloB" src="http://localhost/storage/slider6.jpeg"></p>',
            'zh-Hans' => '<span></span>',
        ], Article::first()->i18n()->content);
    }

    /**
     * !!! 此測試 model 資料為 單一 Html 與使用多語系的 Array Html !!!
     *
     * 測試修改 Model 時，若連結有 __temp__ 則將檔名修改，並修改資料欄位為新路徑
     * 一張新增在多語系
     */
    public function testUpdatedHtmlModelTrait3()
    {
        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $article = new Article;
        $article->title = '<html></html>';
        $article->content = [
            'en' => '<html><div></div>
                            <div></div>
                            <div></div>
                            </html>',
            'zh-Hant' => '<p></p>',
            'zh-Hans' => '<span></span>',
        ];
        $article->save();

        // 模擬按照流程上傳一張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');

        // 檔案應存在
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider1.jpeg'));

        // 將 model 修改，假設修改後多一張圖片
        $article->i18n('en')->content = '<html><div></div>
                            <div><img alt="HelloA" src="http://localhost/storage/__temp__slider1.jpeg"></div>
                            <div></div>
                            </html>';
        $article->save();

        // 檔名應被正確的修改，新圖片移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));

        // 刪除測試用的檔案
        Storage::disk('public')->delete('slider1.jpeg');

        // 確認資料庫的資料應該是修改後的正確路徑
        $this->assertEquals([
            'en' => '<html><div></div>
                            <div><img alt="HelloA" src="http://localhost/storage/slider1.jpeg"></div>
                            <div></div>
                            </html>',
            'zh-Hant' => '<p></p>',
            'zh-Hans' => '<span></span>',
        ], Article::first()->i18n()->content);
    }

    /**
     * !!! 此測試 model 資料為 單一 Html 與使用多語系的 Array Html !!!
     *
     * 測試修改 Model 時，若連結有 __temp__ 則將檔名修改，並修改資料欄位為新路徑
     * 一張新增在無多語系
     */
    public function testUpdatedHtmlModelTrait4()
    {
        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $article = new Article;
        $article->title = '<p></p>';
        $article->content = [
            'en' => '<html><div></div>
                            <div></div>
                            <div></div>
                            </html>',
            'zh-Hant' => '<p></p>',
            'zh-Hans' => '<span></span>',
        ];
        $article->save();

        // 模擬按照流程上傳一張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');

        // 檔案應存在
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider1.jpeg'));

        // 將 model 修改，假設修改後多一張圖片
        $article->title = '<p><img alt="HelloA" src="http://localhost/storage/__temp__slider1.jpeg"></p>';
        $article->i18n('en')->content = '<html><div></div>
                            <div></div>
                            <div></div>
                            </html>';
        $article->save();

        // 檔名應被正確的修改，新圖片移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));

        // 刪除測試用的檔案
        Storage::disk('public')->delete('slider1.jpeg');

        // 確認資料庫的資料應該是修改後的正確路徑
        $this->assertEquals('<p><img alt="HelloA" src="http://localhost/storage/slider1.jpeg"></p>', Article::first()->title);
    }

    /**
     * !!! 此測試 model 資料為 單一 Html 與使用多語系的 Array Html !!!
     *
     * 測試修改 Model 時，若連結有 __temp__ 則將檔名修改，並修改資料欄位為新路徑
     * 一張刪除在無多語系
     */
    public function testUpdatedHtmlModelTrait5()
    {
        // 模擬按照流程上傳一張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), 'slider1.jpeg');

        // 檔案應存在
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));

        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $article = new Article;
        $article->title = '<p><img alt="HelloA" src="http://localhost/storage/slider1.jpeg"></p>';
        $article->content = [
            'en' => '<html><div></div>
                            <div></div>
                            <div></div>
                            </html>',
            'zh-Hant' => '<p></p>',
            'zh-Hans' => '<span></span>',
        ];
        $article->save();

        // 將 model 修改，假設修改後多一張圖片
        $article->title = '<p></p>';
        $article->i18n('en')->content = '<html><div></div>
                            <div></div>
                            <div></div>
                            </html>';
        $article->save();

        // 檔名應被正確的修改，新圖片移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider1.jpeg'));

        // 刪除測試用的檔案
        Storage::disk('public')->delete('__temp__slider1.jpeg');

        // 確認資料庫的資料應該是修改後的正確路徑
        $this->assertEquals('<p></p>', Article::first()->title);
    }

    /**
     * !!! 此測試 model 資料為 單一 Html 與使用多語系的 Array Html !!!
     *
     * 測試修改 Model 時，若連結有 __temp__ 則將檔名修改，並修改資料欄位為新路徑
     * 一張刪除在有多語系
     */
    public function testUpdatedHtmlModelTrait6()
    {
        // 模擬按照流程上傳一張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), 'slider1.jpeg');

        // 檔案應存在
        $this->assertTrue(Storage::disk('public')->exists('slider1.jpeg'));

        // 模擬建立輪播圖使用到該圖片 __temp__ 路徑
        $article = new Article;
        $article->title = '<p></p>';
        $article->content = [
            'en' => '<html><div></div>
                            <div><img alt="HelloA" src="http://localhost/storage/slider1.jpeg"></div>
                            <div></div>
                            </html>',
            'zh-Hant' => '<p></p>',
            'zh-Hans' => '<span></span>',
        ];
        $article->save();

        // 將 model 修改，假設修改後多一張圖片
        $article->title = '<p></p>';
        $article->i18n('en')->content = '<html><div></div>
                            <div></div>
                            <div></div>
                            </html>';
        $article->save();

        // 檔名應被正確的修改，新圖片移除 __temp__
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider1.jpeg'));

        // 刪除測試用的檔案
        Storage::disk('public')->delete('__temp__slider1.jpeg');

        // 確認資料庫的資料應該是修改後的正確路徑
        $this->assertEquals([
            'en' => '<html><div></div>
                            <div></div>
                            <div></div>
                            </html>',
            'zh-Hant' => '<p></p>',
            'zh-Hans' => '<span></span>',
        ], Article::first()->i18n()->content);
    }

    // 測試 http 使用 ajax 檔案上傳
    public function testHttpImageUpload()
    {
        // 建立測試用的資料夾
        Storage::fake('uploads');

        // 發出模擬的 http 請求
        $response = $this->json('POST', '/laravel/image-upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 2047, 2049)->size(4095)
        ]);

        // 假設收到正確的回應
        $response->assertStatus(200);
        $resultArray = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('url', $resultArray); // $url = $resultArray['url'];

        $url = $resultArray['url'];

        $this->console->writeln($url);

        // !!! Storage::delete 的基本路徑為 storage/app
        // 刪除測試用的檔案
        Storage::disk('public')->delete($resultArray['basename']);
    }

    /**
     * @todo 測試上傳的檔案還不會真的被刪，因為上傳時間沒有指定到一天前
     * 測試使用 Queue Job 排程去刪除沒有用到的 temp 檔案事件
     */
    public function testQueueDelete()
    {
        // 模擬按照流程上傳四張圖片
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-0.jpg'), '__temp__slider1.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-1.jpg'), '__temp__slider2.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-3.jpg'), 'slider3.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hant-2.jpg'), 'slider4.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/zh-Hans-0.jpg'), '__temp__slider5.jpeg');
        Storage::disk('public')->putFileAs('', new File(__DIR__.'/public/slider/en-1.jpg'), '__temp__slider6.jpeg');

        // 檔案應存在
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider1.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider2.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider3.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('slider4.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider5.jpeg'));
        $this->assertTrue(Storage::disk('public')->exists('__temp__slider6.jpeg'));

        // 呼叫 Queue 執行
        ClearUselessImagesJob::dispatch();

        // 刪除測試檔案
        Storage::disk('public')->delete('__temp__slider1.jpeg');
        Storage::disk('public')->delete('__temp__slider2.jpeg');
        Storage::disk('public')->delete('slider3.jpeg');
        Storage::disk('public')->delete('slider4.jpeg');
        Storage::disk('public')->delete('__temp__slider5.jpeg');
        Storage::disk('public')->delete('__temp__slider6.jpeg');
    }
}
