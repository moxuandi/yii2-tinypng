<?php

namespace moxuandi\TinyPNG;

use Tinify\Tinify;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

/**
 * Class TinyImage Tinify API client for Yii2
 *
 * @author zhangmoxuan <1104984259@qq.com>
 * @link http://www.zhangmoxuan.com
 * @QQ 1104984259
 * @Date 2020-3-10
 */
class TinyFiles
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
     * TinyFiles constructor.
     * @param array $apiKeys
     * @throws InvalidConfigException
     */
    public function __construct($apiKeys = [])
    {
        $this->apiKeys = $apiKeys;
        if (Yii::$app->cache && ($num = Yii::$app->cache->get($this->_key))) {
            $this->num = $num;
        }
        $this->init();
    }

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->apiKeys) {
            throw new InvalidConfigException("The property 'apiKeys' must be in set in " . get_class($this) . ".");
        }
    }

    /**
     * 压缩目录中的所有图像.
     * @param string $inputDir 输入目录.
     * @param string $outputDir 输出目录. 设置为空字符串时, 将使用输入目录, 这将覆盖原图.
     * @param array $resizeOptions 调整图像大小的配置.
     * @param array $findOptions 文件搜索选项. 参考`FileHelper::findFiles()`方法的`$options`属性.
     * @return array
     * @throws Exception
     */
    public function compressFile($inputDir, $outputDir = '', $resizeOptions = [], $findOptions = [])
    {
        $outputDir = $outputDir ?: $inputDir;
        $files = FileHelper::findFiles($inputDir, $findOptions);
        $success = $error = 0;
        foreach ($files as $file) {
            $input = FileHelper::normalizePath($file);
            $output = FileHelper::normalizePath(str_replace($inputDir, $outputDir, $file));
            if ($this->compressImage($input, $output, $resizeOptions)) {
                $success += 1;
            } else {
                $error += 1;
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
     * @param array $resizeOptions 调整图像大小的配置.
     * @return array
     * @throws Exception
     */
    public function compressImages($images, $resizeOptions = [])
    {
        $success = $error = 0;
        foreach ($images as $input => $output) {
            if ($this->compressImage($input, $output, $resizeOptions)) {
                $success += 1;
            } else {
                $error += 1;
            }
        }
        return [
            'success' => $success,
            'error' => $error,
        ];
    }

    /**
     * 压缩指定图像.
     * @param string $input
     * @param string $output
     * @param array $resizeOptions
     * @return false|int
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function compressImage($input, $output = '', $resizeOptions = [])
    {
        return (new TinyImage($this->apiKeys[$this->num]))->compress($input, $output, 'file', $resizeOptions);
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
