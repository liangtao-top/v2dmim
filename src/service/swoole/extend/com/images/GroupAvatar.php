<?php
/**
 * 描述
 * Created by: PhpStorm
 * User: zmq <zmq3821@163.com>
 * Date: 2021/5/19
 */
namespace com\images;

class GroupAvatar
{
    // 是否保存
    private bool $isSave = true;

    // 临时资源
    private array $tempArr = [];

    // 保存路径
    private ?string $savePath;

    // 背景图片宽度
    private int $imgW = 100;

    // 背景图片高度
    private int $imgH = 100;

    protected mixed $error = '';

    protected mixed $result = '';

    /**
     * @throws \Exception
     */
    public function create($imageList)
    {
        if (empty($imageList)) {
            $this->error = '图片列表参数错误';
            return false;
        }
        if ($this->isSave && empty($this->savePath)) {
            $this->error = '未指定保存地址';
            return false;
        }
        // 获取待处理数组
        $pic_list = $this->dealImageList($imageList, 4);

        //设置背景图片宽高
        $bg_w = $this->imgW; // 背景图片宽度
        $bg_h = $this->imgH; // 背景图片高度

        //新建一个真彩色图像作为背景
        $background = imagecreatetruecolor($bg_w,$bg_h);
        //为真彩色画布创建白灰色背景，再设置为透明
        $color = imagecolorallocate($background, 202, 201, 201);
        imagefill($background, 0, 0, $color);
        imageColorTransparent($background, $color);
        //根据图片个数设置图片位置
        $pic_count = count($pic_list);
        $lineArr = [];//需要换行的位置
        $start_x = $start_y = 0;
        $pic_w = $pic_h = 0;
        $padding = 2;
        switch($pic_count) {
            case 1: // 正中间
                $pic_w   = intval($bg_w / 2) - intval($padding * 2);
                $pic_h   = $pic_w;
                $start_x = ($bg_w - $pic_w) / 2; // 开始位置X
                $start_y = ($bg_h - $pic_w) / 2; // 开始位置Y
                break;
            case 2: // 中间位置并排
                $pic_w   = intval($bg_w / 2) - intval($padding * 2);
                $pic_h   = $pic_w;
                $start_x = $padding;
                $start_y = ($bg_h - $pic_w) / 2;
                break;
            case 3:
                $pic_w   = intval($bg_w / 2) - intval($padding * 2);// 宽度
                $pic_h   = $pic_w;// 高度
                $start_x = ($bg_w - $pic_w) / 2; // 开始位置X
                $start_y = ($bg_h / 2 - $pic_h) / 2; // 开始位置Y
                $lineArr = [2];
                break;
            case 4:
                $pic_w   = intval($bg_w / 2) - intval($padding * 2);// 宽度
                $pic_h   = $pic_w;// 高度
                $start_x = $padding; // 开始位置X
                $start_y = $padding; // 开始位置Y
                $lineArr = [3];
                break;
        }
        foreach ($pic_list as $k => $pic_path) {
            $kk = $k + 1;
            if (in_array($kk, $lineArr)) {
                $start_x = $padding;
                $start_y = $start_y + $pic_h + $padding * 2;
            }
            //获取图片文件扩展类型和mime类型，判断是否是正常图片文件
            //非正常图片文件，相应位置空着，跳过处理
            $image_mime_info = @getimagesize($pic_path);
            if ($image_mime_info && !empty($image_mime_info['mime'])) {
                $mime_arr = explode('/', $image_mime_info['mime']);
                if (is_array($mime_arr) && $mime_arr[0] == 'image' && !empty($mime_arr[1])) {
                    switch ($mime_arr[1]) {
                        case 'jpg':
                        case 'jpeg':
                            $resource = imagecreatefromjpeg($pic_path);
                            break;
                        case 'png':
                            $resource = imagecreatefrompng($pic_path);
                            break;
                        case 'gif':
                        default:
                            $pic_path = file_get_contents($pic_path);
                            $resource = imagecreatefromstring($pic_path);
                            break;
                    }
                    //创建一个新图像
                    //$resource = $imagecreatefromjpeg($pic_path);
                    //将图像中的一块矩形区域拷贝到另一个背景图像中
                    // $start_x,$start_y 放置在背景中的起始位置
                    // 0,0 裁剪的源头像的起点位置
                    // $pic_w,$pic_h copy后的高度和宽度
                    imagecopyresized($background, $resource, $start_x, $start_y, 0, 0, $pic_w, $pic_h, imagesx($resource), imagesy($resource));
                }
            }
            // 最后两个参数为原始图片宽度和高度，倒数两个参数为copy时的图片宽度和高度
            $start_x = $start_x + $pic_w + $padding * 2;
        }
        if ($this->isSave) {
            if (!$this->saveFile($background, $this->savePath)) {
                $this->error = '图片写入失败';
                return false;
            }
            $this->clearTempArr();
            $this->result = $this->savePath;
            return true;
        } else {
            $this->clearTempArr();
            //直接输出
            header("Content-type: image/jpg");
            imagejpeg($background);
            imagedestroy($background);
        }
    }

    /**
     * 返回处理好的图片列表
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/20 13:32
     * @param array $imageList
     * @param int $max
     * @return array
     */
    public function dealImageList(array $imageList, int $max): array
    {
        //$pic_list = array_slice($imageList, 0, 4);
        $list= [];
        foreach ($imageList as $k => $item) {
            $item = trim($item);
            if ($k > $max-1) break;
            if (empty($item)) continue;
            $ext = $this->getExtension($item);
            if (in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) { // 是图片
                $list[] = $item;
            } else { // 是字符
                $list[] = $this->str2Img((string)$item);
            }
        }
        return $list;
    }

    /**
     * 文字转图片
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/20 12:20
     * @param string $str
     * @param int $bgW
     * @param int $bgH
     * @return void
     */
    public function str2Img(string $str, int $bgW = 46, int $bgH = 46): string
    {
        $text       = mb_substr($str, -1);
        $font       = dirname(__FILE__) . "/fonts/msyh.ttc"; //字体所放目录
        $background = imagecreate($bgW, $bgH);
        $fontSize   = 23;
        $width      = imagesx($background);
        $height     = imagesy($background);
        $fontBox    = imagettfbbox($fontSize, 0, $font, $text); //获取文字所需的尺寸大小
        $x          = ceil(($width - $fontBox[2]) / 2);
        $y          = ceil(($height - $fontBox[1] - $fontBox[7]) / 2);
        $dark       = imagecolorallocate($background, 0, 0, 255);
        $white      = imagecolorallocate($background, 255, 255, 255);
        imagefill($background, 0, 0, $dark);//填充背景色
        imagettftext($background, 20, 0, $x, $y, $white, $font, $text);
//        header("Content-type:image/png");
//        imagepng($background);
//        imagedestroy($background);
        //保存图片
        $savePath = "upload/temp/".uniqid().".jpg";
        if (!$this->saveFile($background, $savePath)) {
            return '';
        }
        $this->tempArr[] = $savePath;
        return $savePath;
    }

    /**
     * 清理临时文件
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/20 15:41
     */
    private function clearTempArr()
    {
        if (!empty($this->tempArr)) {
            foreach ($this->tempArr as $temp) {
                @unlink($temp);
            }
        }
    }

    /**
     * 图片写入本地
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/20 15:25
     * @param $background
     * @param string $savePath
     * @return bool
     */
    private function saveFile($background, string $savePath): bool
    {
        $dir = pathinfo($savePath, PATHINFO_DIRNAME);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                $this->error = '目录创建失败';
                return false;
            }
        }
        $res = imagejpeg($background, $savePath);
        imagedestroy($background);
        if (!$res) {
            $this->error = '图片写入失败';
            return false;
        }
        return true;
    }

    /**
     * 获取扩展名
     * @user zmq <zmq3821@163.com>
     * @date 2021/5/20 12:29
     * @param $file
     * @return bool|string
     */
    public function getExtension($file): bool|string
    {
        return substr(strrchr($file, '.'), 1);
    }

    /**
     * @param string|null $savePath
     */
    public function setSavePath(?string $savePath): void
    {
        $this->savePath = $savePath;
    }

    /**
     * @param bool $isSave
     */
    public function setIsSave(bool $isSave): void
    {
        $this->isSave = $isSave;
    }

    public function getError(): mixed
    {
        return $this->error;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

}