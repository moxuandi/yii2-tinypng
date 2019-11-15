<?php
namespace moxuandi\tinypng;

use Tinify\AccountException;
use Tinify\ClientException;
use Tinify\ConnectionException;
use Tinify\Exception;
use Tinify\ServerException;
use Tinify\Source;
use Tinify\Tinify;
use Yii;
use yii\helpers\FileHelper;

/**
 * Class ArrayHelper 拓展数组助手类
 *
 * @author zhangmoxuan <1104984259@qq.com>
 * @link http://www.zhangmoxuan.com
 * @QQ 1104984259
 * @Date 2019-8-4
 */

/**
 * Class TinyImage Tinify API client for Yii2
 *
 * @author zhangmoxuan <1104984259@qq.com>
 * @link http://www.zhangmoxuan.com
 * @QQ 1104984259
 * @Date 2019-10-20
 */
class TinyImage
{
    /**
     * @var array API Keys for TinyPNG
     * 申请地址: https://tinypng.com/developers
     */
    private $apiKeys = [];
    /**
     * @var int key 的序号
     */
    private $num = 0;
    /**
     * @var string 存储 key 序号的缓存键.
     */
    private $_key = 'tiny_num';

    /**
     * TinyImage constructor.
     */
    public function __construct()
    {
        $this->apiKeys = Yii::$app->params['tinyPngApiKeys'];
        if(Yii::$app->cache && ($num = Yii::$app->cache->get($this->_key))){
            $this->num = $num;
        }
    }

    /**
     * 压缩目录中的所有图像.
     * @param string $inputDir 输入目录.
     * @param string $outputDir 输出目录. 设置为空字符串时, 将使用输入目录, 这将覆盖原图.
     * @param array|bool $resize 调整图像大小的配置. 参考`compressImage()`方法的`$resize`属性.
     * @param array $findOptions 文件搜索选项. 参考`FileHelper::findFiles()`方法的`$options`属性.
     * @return array
     * @throws yii\base\Exception
     */
    public function compressFile($inputDir, $outputDir = '', $resize = false, $findOptions = [])
    {
        $outputDir = $outputDir ? $outputDir : $inputDir;
        $files = FileHelper::findFiles($inputDir, $findOptions);
        $success = $error = 0;
        foreach($files as $file){
            $input = FileHelper::normalizePath($file);
            $output = FileHelper::normalizePath(str_replace($inputDir, $outputDir, $file));
            if($this->compressImage($input, $output, $resize) === false){
                $error += 1;
            }else{
                $success += 1;
            }
        }
        return [
            'success' => $success,
            'error' => $error,
        ];
    }

    /**
     * 批量压缩图像.
     * @param array $images 图像列表, 键是输入图像, 值是输出图像.
     * @param array|bool $resize 调整图像大小的配置. 参考`compressImage()`方法的`$resize`属性.
     * @return array
     * @throws yii\base\Exception
     */
    public function compressImages($images, $resize = false)
    {
        $success = $error = 0;
        foreach($images as $input => $output){
            if($this->compressImage($input, $output, $resize) === false){
                $error += 1;
            }else{
                $success += 1;
            }
        }
        return [
            'success' => $success,
            'error' => $error,
        ];
    }

    /**
     * 压缩单个图像.
     * @param string $input 输入图像
     * @param string $output 输出图像
     * @param array|bool $resize 调整图像大小的配置. 设置为`false`或空数组将不调整图像大小. 数组参数有:
     *  - `method`: string, 调整方式, 必填项. 可用值有:
     *      - `scale`: 按比例缩小图像. 必须提供`width`或`height`之一, 但不能同时提供两者.
     *      - `fit`: 按比例缩小图像. 必须同时提供`width`或`height`. 一般结果的高度不超过`height`值.
     *      - `cover`: 按比例校正图像并在必要时对其进行裁剪, 以使结果具有给定的尺寸. 必须同时提供`width`或`height`. 图像的哪部分被裁剪掉将由智能算法自动确定.
     *      - `thumb`: 图像将按比例缩小到指定的`width`或`height`. 必须同时提供`width`或`height`.
     *  - `width`: int, 目标宽度.
     *  - `height`: int, 目标高度.
     * @param string $type 资源类型. 参考`getSource()`方法的`$type`属性.
     * @return bool|int
     * @throws yii\base\Exception
     */
    public function compressImage($input, $output, $resize = false, $type = 'file')
    {
        $source = $this->getSource($input, $type);
        if($resize){
            $source = $source->resize($resize);
        }
        if(!is_dir($output)){
            FileHelper::createDirectory(dirname($output), 777);
        }
        return $source->toFile($output);
    }

    /**
     * 得到压缩图片后的响应结果.
     * @param string $input 源图.
     * @param string $type 资源类型. 可用类型:
     *  - 'file': 本地图片的路径(默认值);
     *  - `buffer`: 图片信息字符串;
     *  - `url`: 远程图片路径;
     * @return Source Source 对象可用方法:
     *  - 'result()': 返回`Tinify\Result`对象
     *  - 'toBuffer()': 返回图片信息字符串
     *  - 'preserve()': 保留图片元数据
     *  - `toFile($path)`: 保存图片到指定位置
     *  - 'store($options)': 将图像保存到`Amazon S3`或`Google Cloud Storage`.
     *  - 'resize($options)': 调整图像大小. 参考`compressImage()`方法的`$resize`属性.
     * @throws ClientException
     * @throws ConnectionException
     * @throws Exception
     * @throws ServerException
     */
    public function getSource($input, $type = 'file')
    {
        try{
            $key = $this->apiKeys[$this->num];
            Tinify::setKey($key);
            switch($type){
                case 'buffer': $source = Source::fromBuffer($input); break;
                case 'url': $source = Source::fromUrl($input); break;
                case 'file':
                default: $source = Source::fromFile($input); break;
            }
            file_put_contents(Yii::getAlias('@runtime/tiny-logs.txt'), date('Y-m-d H:i:s') . " 使用`{$key}`压缩文件`{$input}`结果: " . var_export($source, true) . "\n\n", FILE_APPEND);
            return $source;
        }catch(AccountException $e){
            file_put_contents(Yii::getAlias('@runtime/tiny-count.txt'), date('Y-m-d H:i:s') . " The key `{$key}` monthly limit has been exceeded.\n", FILE_APPEND);
            if($e->status == 429){
                $this->num += 1;
                if($this->num < count($this->apiKeys)){
                    Yii::$app->cache->set($this->_key, $this->num);
                    return $this->getSource($input, $type);
                }else{
                    throw new AccountException("All keys are limit has been exceeded, No key available.");
                }
            }
        }catch(ClientException $e){
            throw new ClientException($e->getMessage(), null, $e->status);
        }catch(ServerException $e){
            throw new ServerException($e->getMessage(), null, $e->status);
        }catch(ConnectionException $e){
            throw new ConnectionException($e->getMessage(), null, $e->status);
        }catch(Exception $e){
            throw new Exception($e->getMessage(), null, $e->status);
        }
    }

    /**
     * 析构函数.
     */
    public function __destruct()
    {
        $key = $this->apiKeys[$this->num];
        file_put_contents(Yii::getAlias('@runtime/tiny-count.txt'), date('Y-m-d H:i:s') . " key`{$key}`使用次数: " . Tinify::getCompressionCount() . "\n", FILE_APPEND);
    }
}
