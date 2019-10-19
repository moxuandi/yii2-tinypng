Tinify API client for PHP Framework Yii2
==================
Tinify API 的PHP客户端, 用于 TinyPNG 和 TinyJPG. Tinify 智能压缩你的图像. [阅读更多内容请看官网](https://tinypng.com).


## 安装:
使用 [composer](http://getcomposer.org/download/) 下载:
```
# 2.2.x(yii >= 2.0.24):
composer require moxuandi/yii2-tinypng:"~2.2.0"

# 开发版:
composer require moxuandi/yii2-tinypng:"dev-master"
```

## 使用方法:

#### 1. 在`params`中添加 api key:
```php
'tinyPngApiKeys' => [
    'vMClPPpGgT1WQ5nX7kmdrV5nWW7r6q9V',
    'Qg24qhk1xcxGsHvkfWZn4mMNkq64QqX8',
    'vk07xRY61h3LThY4BXyYLpHmHdvCvnvZ',
    'Y2bLBFbGwJ5w1JvPCj7BY68Jt0GzXTvz',
    '687t8JHGJBTq5rYC7tJ34jxxs5swWxCt',
    // ……
],
```

#### 2. 调用方法:
```php
$tiny = new TinyImage();
$resize = [
    'method' => 'thumb',
    'width' => 150,
    'height' => 100,
];

// 压缩单个文件:
$tiny->compressImage('example.png', 'thumb.png');

// 压缩多个文件:
$images = [
    'example1.png' => 'thumb1.png',
    'example2.png' => 'thumb2.png',
    'example3.png' => 'thumb3.png',
    // ……
];
$tiny->compressImages($images);

# 压缩整个目录(相对目录或绝对目录):
$source = Yii::getAlias('@webroot/uploads/image');
$target = Yii::getAlias('@webroot/upload/image');
$tiny->compressFile($source, $target, [], $resize);
```
