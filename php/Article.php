<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 内容管理
 *
 * @icon fa fa-circle-o
 */
class Article extends Backend
{

    /**
     * Article模型对象
     * @var \app\admin\model\Article
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Article;

    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $result = false;
        Db::startTrans();
        try {
            //处理封面图
            $imgs = getImgs($params['content']);
            if($imgs){
                if(strpos($imgs[0], 'uploads')!==false){
                    $image = substr($imgs[0], strpos($imgs[0], 'uploads')-1);
                    $params['image'] = $image;
                }
            }
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);


            //处理关键词
            $article_id = $this->model->getLastInsID();
            $params['keys'] = str_replace('，',',',$params['keys']);
            $klist = explode(',', $params['keys']);
            foreach ($klist as $key){
                $key = trim($key);
                if($key){
                    $hask = Db::name('keywords')->where(['kname'=>$key])->find();
                    if($hask){
                        $hasbind = Db::name('keycontent')->where(['keyword_id'=>$hask['id'], 'article_id'=>$article_id])->find();
                        if(!$hasbind){
                            Db::name('keycontent')->insertGetId(['keyword_id'=>$hask['id'], 'article_id'=>$article_id]);
                        }
                    }else{
                        $keyid = Db::name('keywords')->insertGetId(['kname'=>$key]);
                        Db::name('keycontent')->insertGetId(['keyword_id'=>$keyid, 'article_id'=>$article_id]);
                    }
                }
            }
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //处理关键词
            $params['keys'] = str_replace('，',',',$params['keys']);
            $klist = explode(',', $params['keys']);
            foreach ($klist as $key){
                $key = trim($key);
                if($key){
                    $hask = Db::name('keywords')->where(['kname'=>$key])->find();
                    if($hask){
                        $hasbind = Db::name('keycontent')->where(['keyword_id'=>$hask['id'], 'article_id'=>$row['id']])->find();
                        if(!$hasbind){
                            Db::name('keycontent')->insertGetId(['keyword_id'=>$hask['id'], 'article_id'=>$row['id']]);
                        }
                    }else{
                        $keyid = Db::name('keywords')->insertGetId(['kname'=>$key]);
                        Db::name('keycontent')->insertGetId(['keyword_id'=>$keyid, 'article_id'=>$row['id']]);
                    }
                }
            }
            //处理封面图
            $imgs = getImgs($params['content']);
            if($imgs){
                if(strpos($imgs[0], 'uploads')!==false){
                    $image = substr($imgs[0], strpos($imgs[0], 'uploads')-1);
                    $params['image'] = $image;
                }
            }
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                Db::name('keycontent')->where(['article_id'=>$item->id])->delete();
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }


}
