<?php
namespace moxuandi\tinypng;

/**
 * Class TinyImg
 * usage:
$img = new TinyImg('I9bcV60StWrf8cNPNPsyDpaT67H8BEWF', true);
$input = 'D:\wamp64\www\yii2advanced\uploads\image\201703\img_9.jpg';   //绝对路径
//$input = 'uploads/image/201703/img_9.jpg';  //相对路径，相对与当前站点
$out = 'D:\wamp64\www\yii2advanced\uploads\tiny\201703\img_9.jpg';
$m = $img->compress($input, $out);
 * @package moxuandi\tinypng
 * https://github.com/mike183/PHP-TinyPNG
 */
class TinyImg
{
    /**
     * API Key for TinyPNG
     * @var string
     */
    private $api_key;

    /**
     * Do we need to use the cacert.pem file?
     * @var boolean
     */
    private $use_pem;

    /**
     * @param string $api_key API Key for TinyPNG
     * @param boolean $use_pem If you are having issues connecting to the API endpoint, set this option to true
     */
    function __construct($api_key, $use_pem=true)
    {
        $this->api_key = $api_key;
        $this->use_pem = $use_pem;
    }

    /**
     * 文件夹中所有图像压缩
     * @param $indir: 压缩前的图像目录, eg:'D:\image' or 'image'
     * @param $outdir: 压缩后的图像目录, eg:'D:\tiny' or 'tiny'
     * @return string
     */
    public function compressFile($indir, $outdir)
    {
        $images = $this->getFiles($indir);
        if(!empty($images)){
            foreach($images as $image){
                $input = $indir . '\\' . $image;
                $output = $outdir . '\\' . $image;
                $this->compressImage($input, $output);
            }
            return true;
        }
        return '该目录下没有图像元素';
    }

    /**
     * 多图压缩
     * @param $images: 图像数据对, eg:
     * $images = [
     *   'image/img_7.jpg' => 'tiny/img_7.jpg',
     *   'D:\image\img_9.jpg' => 'D:\tiny\img_9.jpg',
     * ];
     * @return mixed Returns the response from TinyPNG
     */
    public function compressImages($images)
    {
        if(!empty($images)){
            foreach($images as $input=>$output){
                $response[$input] = $this->compressImage($input, $output);
            }
        }else{
            return '数据格式错误！';
        }
    }

    /**
     * 单图压缩
     * @param $input: 压缩前的图像, eg:'D:\image\img_9.jpg' or 'image/img_9.jpg'
     * @param $output: 压缩后的图像, eg:'D:\tiny\img_9.jpg' or 'tiny/img_9.jpg'
     * @return mixed Returns the response from TinyPNG
     */
    public function compressImage($input, $output)
    {
        //set_time_limit(0); // 不超时永久执行
        //初始化cURL会话
        $curl = curl_init();
        // 设置cURL请求选项
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.tinify.com/shrink',
            CURLOPT_USERPWD => 'api:' . $this->api_key,
            CURLOPT_POSTFIELDS => file_get_contents($input),
            CURLOPT_HEADER => true,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        // 检测是否需要PEM文件
        if($this->use_pem){
            curl_setopt($curl, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
        }
        // 执行cURL会话
        $response = curl_exec($curl);
        // 提取响应结果
        $headsize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $body = json_decode(substr($response, $headsize), true);
        // 查看请求是否成功
        if(curl_getinfo($curl, CURLINFO_HTTP_CODE) === 201){
            $file = file_get_contents($body['output']['url']);  //下载图像
            file_put_contents($output, $file);  //保存图像
            curl_close($curl);
            return $body;
        }else{
            return $body;
        }
    }

    /**
     * @param $filedir: 目录
     * @return array: 返回目录下的文件
     */
    public function getFiles($filedir)
    {
        $files = [];
        $dir = dir($filedir);
        while(($file = $dir->read()) != false){
            if($file != '.' && $file != '..'){
                $files[] = $file;
            }
        }
        $dir->close();
        return $files;
    }
}