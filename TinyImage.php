<?php

namespace moxuandi\TinyPNG;

use Tinify\Result;
use Tinify\Source;
use Tinify\Tinify;
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
class TinyImage
{
    /**
     * @var string API Keys for TinyPNG
     * 申请地址: https://tinypng.com/developers
     */
    public $apiKey;


    /**
     * TinyImage constructor.
     * @param string $apiKey
     * @throws InvalidConfigException
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->init();
    }

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->apiKey) {
            throw new InvalidConfigException("The property 'apiKey' must be in set in " . get_class($this) . ".");
        }
        Tinify::setKey($this->apiKey);
    }

    /**
     * 压缩指定图像.
     * @param string $input 输入图像.
     * @param string $output 输出图像. 未设置, 将覆盖源图.
     * @param string $type 资源类型.
     * @param array $resizeOptions 调整图像大小的配置. 默认将不调整图像大小. 数组参数有:
     *  - `method`: string, 调整方式, 必填项. 可用值有:
     *      - `scale`: 按比例缩小图像. 必须提供`width`或`height`之一, 但不能同时提供两者.
     *      - `fit`: 按比例缩小图像. 必须同时提供`width`或`height`. 一般结果的高度不超过`height`值.
     *      - `cover`: 按比例校正图像并在必要时对其进行裁剪, 以使结果具有给定的尺寸. 必须同时提供`width`或`height`. 图像的哪部分被裁剪掉将由智能算法自动确定.
     *      - `thumb`: 图像将按比例缩小到指定的`width`或`height`. 必须同时提供`width`或`height`.
     *  - `width`: int, 目标宽度.
     *  - `height`: int, 目标高度.
     * @param string $result 结果类型. 可选值: (`toFile`:保存到图片;`toBuffer`:输出图片数据;`result`:输出结果对象;).
     * @return false|int|Result
     * @throws Exception
     */
    public function compress($input, $output = '', $type = 'file', $resizeOptions = [], $result = 'toFile')
    {
        $source = $this->getSource($input, $type);
        if ($resizeOptions) {
            $source = $source->resize($resizeOptions);
        }
        $output = $output ?: $input;
        if (!is_dir(dirname($output))) {
            FileHelper::createDirectory(dirname($output), 777);
        }
        switch ($result) {
            case 'toBuffer':
                return $source->toBuffer();
                break;
            case 'result':
                return $source->result();
                break;
            case 'toFile':
            default:
                return $source->toFile($output);
                break;
        }
    }

    /**
     * 得到压缩图片后的响应结果.
     * @param string $input 源图
     * @param string $type 资源类型. 可用类型:
     *  - 'file': 本地图片的路径(默认值);
     *  - `buffer`: 图片信息字符串;
     *  - `url`: 远程图片路径;
     * @return Source Source 对象可用方法:
     *  - 'resize($options)': 调整图像大小;
     *  - `toFile($path)`: 保存图片到指定位置;
     *  - 'toBuffer()': 返回图片信息字符串;
     *  - 'result()': 返回`\Tinify\Result`对象;
     *  - 'preserve()': 保留图片元数据;
     *  - 'store($options)': 将图像保存到`Amazon S3`或`Google Cloud Storage`;
     */
    public function getSource($input, $type = 'file')
    {
        switch ($type) {
            case 'buffer':
                $source = Source::fromBuffer($input);
                break;
            case 'url':
                $source = Source::fromUrl($input);
                break;
            case 'file':
            default:
                $source = Source::fromFile($input);
                break;
        }
        return $source;
    }
}
