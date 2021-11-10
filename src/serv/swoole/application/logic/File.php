<?php /** @noinspection PhpUnusedParameterInspection PhpParameterByRefIsNotUsedAsReferenceInspection PhpFullyQualifiedNameUsageInspection PhpUndefinedMethodInspection DuplicatedCode PhpDynamicAsStaticMethodCallInspection */
// +----------------------------------------------------------------------
// | CodeEngine
// +----------------------------------------------------------------------
// | Copyright 艾邦
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: TaoGe <liangtao.gz@foxmail.com>
// +----------------------------------------------------------------------
// | Version: 2.0 2020/9/14 14:49
// +----------------------------------------------------------------------

namespace app\logic;

use app\model_bak\Picture as PictureModel;
use app\model_bak\File as FileModel;


/**
 * 文件业务类
 * Class File
 * @package app\logic
 */
class File extends Logic
{

    //上传问价类型 0文件,1图片
    const TYPE_FILE = 0;
    const TYPE_IMG  = 1;


    /**
     * 保存上传图片
     * @user zmq <zmq3821@163.com>
     * @date 2021/4/13 15:58
     * @param \think\File $files
     * @param string $path
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function uploadImg($files, string $path = 'picture'): bool
    {
        if (!is_array($files)) {
            $files = [$files];
        }
        $uuid = '';
        $model            = new PictureModel();
        $user_id          = $uuid;
        $data             = [];
        $where['user_id'] = $user_id;
        $url              = config('swoole.file_system')['url'];
        foreach ($files as $file) {
            $md5           = $file->md5();
            $sha1          = $file->sha1();
            $where['md5']  = $md5;
            $where['sha1'] = $sha1;
            $count         = $model->where($where)->count();
            if ($count) {
                $info         = $model->where($where)->find()->toArray();
                $info['path'] = $url . DS . $info['path'];
                $data[]       = $info;
            } else {
                if (!$info = $file->validate(['size'=>1048576*20,'ext'=>'jpg,png,bmp,jpeg,gif'])->rule('date')->move(ROOT_PATH . 'public' . $url . DS . $path)) {
                    $this->error = $file->getError();
                    return false;
                }
                $savePath = $path . DS .$info->getSaveName();
                $info = [
                    'user_id'       => $user_id,
                    'name'          => (string)$file->getInfo('name'),
                    'path'          => $savePath,
                    'md5'           => $md5,
                    'sha1'          => $sha1,
                    'status'        => 1,
                    'create_time'   => time()
                ];
                $model->data($info);
                if (!$model->save()) {
                    $this->error = '文件保存失败';
                    return false;
                }
                $info['id']          = $model->id;
                $info['path']        = $url . DS . $info['path'];
                $info['create_time'] = date('Y-m-d H:i:s', $info['create_time']);
                $data[]              = $info;
            }
        }
        $this->result = $data;
        return true;
    }

    /**
     * 保存上传文件
     * @user zmq <zmq3821@163.com>
     * @date 2021/4/13 16:51
     * @param \think\File $files
     * @param string $path
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function uploadFile($files, string $path = 'file'): bool
    {
        if (!is_array($files)) {
            $files = [$files];
        }
        $uuid = '';
        $model            = new FileModel();
        $user_id          = $uuid;
        $data             = [];
        $where['user_id'] = $user_id;
        $url              = config('swoole.file_system')['url'];
        foreach ($files as $file) {
            $md5           = $file->md5();
            $sha1          = $file->sha1();
            $where['md5']  = $md5;
            $where['sha1'] = $sha1;
            $count         = $model->where($where)->count();
            if ($count) {
                $info         = $model->where($where)->find()->toArray();
                $info['path'] = $url . DS . $info['savepath'];
                $data[]       = $info;
            } else {
                if (!$info = $file->validate(['size'=>1048576*300])->rule('date')->move(ROOT_PATH . 'public' . $url . DS . $path)) {
                    $this->error = $file->getError();
                    return false;
                }
                $savePath = $path . DS .$info->getSaveName();

                $info = [
                    'user_id'       => $user_id,
                    'name'          => (string)$file->getInfo('name'),
                    'savename'      => $info->getSaveName(),
                    'savepath'      => $savePath,
                    'ext'           => (string)pathinfo($info->getInfo('name'), PATHINFO_EXTENSION),
                    'mime'          => (string)$info->getMime(),
                    'size'          => (string)$info->getSize(),
                    'md5'           => $md5,
                    'sha1'          => $sha1,
                    'create_time'   => time()
                ];
                $model->data($info);
                if (!$model->save()) {
                    $this->error = '文件保存失败';
                    return false;
                }
                $info['id']          = $model->id;
                $info['path']        = $url . DS . $info['savepath'];
                $info['create_time'] = date('Y-m-d H:i:s', $info['create_time']);
                $data[]              = $info;
            }
        }
        $this->result = $data;
        return true;
    }

}
