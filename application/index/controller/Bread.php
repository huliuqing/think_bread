<?php
namespace app\index\controller;

use think\Controller;

/**
 * @package Bread 执行 Browser, read, edit, add, delete 操作
 * 
 * Browser:  获取列表数据
 * read:     读取一条数据记录
 * edit:     读取一条数据记录，完成编辑工作
 * add:      创建一条数据记录
 * delete:   删除一条数据记录
 * 
 * @author huliuqing <liuqing_hu@126.com>
 */
class Bread extends Controller
{
    private $module = null;
    private $controller = null;
    private $action = null;

    private $limits = array();
    private $timeFields = array();
    private $likeFields = array();

    private $modelName = null;
    private $successRedirectUrl = "";
    private $errorRedirectUrl = "";
    private $tplName = '';

    public function _initialize()
    {
        parent::_initialize();

        $this->setModule($this->getRequest()->module());
        $this->setController($this->getRequest()->controller());
        $this->setAction($this->getRequest()->action());
    }

    public function register()
    {

    }

    /***********************************************/
    /*                  BREAD 操作                 */
    /***********************************************/

    /**
     * browser 浏览列表
     * 
     * @param mixed $model 模型名称或模型实例
     * @param string $tpl 显示模版名称
     * @return void
     */
    public function browser($model, $tpl = "index")
    {
        $this->list($model);

        return $this->fetch($tpl);
    }

    /**
     * read 读取记录页面
     *
     * @param mixed $model 模型名称或模型实例
     * @param string $tpl 显示模版名称
     * @return void
     */
    public function read($model, $tpl = "read")
    {
        $this->find($model);

        return $this->fetch($tpl);
    }

    /**
     * edit 编辑记录页面
     *
     * @param mixed $model 模型名称或模型实例
     * @param string $tpl 显示模版名称
     * @return void
     */
    public function edit($model, $tpl = "edit")
    {
        $this->find($model);

        return $this->fetch($tpl);

    }

    /**
     * add 创建记录页面
     *
     * @param mixed $model 模型名称或模型实例
     * @param string $tpl 显示模版名称
     * @return void
     */
    public function add($model, $tpl = "add")
    {
        $this->find($model);

        return $this->fetch($tpl);
    }

    /**
     * delete 删除记录
     *
     * @param mixed $model 模型名称或模型实例
     * @param string $jumpUrl 成功跳转地址
     * @return void
     */
    public function delete($model, $jumpUrl = "")
    {
        $model = $this->getModel($model);

        $map = [
            $model->getPk() => $this->getRequest()->request("id")
        ];

        //@todo support 关联删除
        $result = $model->where($map)->delete();

        if ($result) {
            $this->success("删除成功", $jumpUrl);
        } else {
            $this->error("删除失败");
        }
    }

    /**
     * doAdd 执行创建记录操作
     * 
     * @param mixed $model 模型名称或模型实例
     */
    public function doAdd($model)
    {
        $model = $this->getModel($model);

        //@todo support 关联模型
        $id = $model->data($this->getRequest()->param())->save();

        if ($id) {
            // 触发器
            $trigger = $this->getAction() . "_trigger";
            if (method_exists($this, $trigger)) {
                $this->$trigger($id);
            }

            $this->success("创建成功");
        }

        $this->error("创建失败");
    }

    /**
     * doEdit 执行编辑操作
     *
     * @param mixed $model 模型名称或模型实例
     * @return void
     */
    public function doEdit($model)
    {
        $model = $this->getModel($model);

        $primaryKey = $model->getPk();
        $updateId = $this->getRequest()->param($primaryKey);
        $item = $model->get($updateId);

        if (!$item) {
            $this->error("记录未找到");
        }

        $id = $item->update($this->getRequest()->param());

        if ($id) {
            // 触发器
            $trigger = $this->getAction() . "_trigger";
            if (method_exists($this, $trigger)) {
                $this->$trigger($id, $item);
            }

            $this->success("更新成功");
        }

        $this->error("更新失败");
    }

    public function getModel($model = null)
    {
        if (is_null($model) || empty($model)) {
            if (class_exists($this->getController())) {
                return model(\ucfirst($this->getController()));
            }
        }
        
        if (is_string($model)) {
            return model($model);
        }

        return $model;
    }

    public function buildSearchCondition($model)
    {
        $model = $this->getModel($model);
        
        $likes = $this->getLikeFields();
        $limits = $this->getLimits();
        $timeFields = $this->getLimits();

        $map = array();
        
        $request = $this->getRequest();
        foreach ($model->getTableFields() as $field) {
            if(!$request->has($field)){
                continue;
            }
            
            $search = trim($request->param($field));

            if (isset($search)) {
                if (!empty($likes) && in_array($field, $likes)) {
                    $map[$field] = array(
                        "like", 
                        "%" .$search. "%",
                    );
                } else {
                    $map[$field] = $search;
                }
            }

            if(!empty($this->getTimeFields()) && in_array($field, $timeFields)) {
                $timeBeginField = $field . '_begin';
                $timeEndField = $field . '_end';

                $_REQUEST[$timeBeginField] = (isset($_REQUEST[$timeBeginField]) && !empty($_REQUEST [$timeBeginField])) ? $_REQUEST [$timeBeginField] : date('Y-m-d', strtotime('-2 days'));
                $_REQUEST[$timeEndField]   = (isset($_REQUEST[$timeEndField]) && !empty($_REQUEST [$timeEndField])) ? $_REQUEST [$timeEndField] : date('Y-m-d', strtotime('-1 day'));
                
                $timeBegin = $_REQUEST[$timeBeginField];
                $timeEnd = $_REQUEST[$timeEndField];
                
                $map [$field] = array (
                    'BETWEEN', 
                    $timeBegin . ',' . ($timeEnd -1)
                );// [timeBegin, timeEnd)
            }
        }

        $map = $this->addLimitsToMap($limits, $map);

        return $map;
    }

    public function list($model)
    {
        $model = $this->getModel($model);
        $map = $this->buildSearchCondition($model);

        //@todo support group 分组
        //@todo support order by 排序
        $list = $model->where($map)->paginate();
        
        $this->assign("list", $list);
        $this->assign("pagination", $list->render());
    }

    /**
     * find 查询信息
     *
     * @param [type] $model
     * 
     * @return void
     */
    public function find($model)
    {
        $model = $this->getModel($model);

        $id = $this->getRequest()->request("id");

        $map = $this->addLimitsToMap( $this->getLimits(), [$model->getPk() => $id]);

        //@todo support relation model
        $item = $model->where($map)->find();
        
        $this->assign("item", $item);

        return $item;
    }
    
    /**
     * addLimitsToMap 将查询条件限定 $limit 解析至 where 查询数组
     *
     * @param [array] $limits
     * @param [array] $map
     * 
     * @return array
     */
    protected function addLimitsToMap($limits, $map = [])
    {
        if (!empty($limits)) {
            foreach ($limits as $key => $value) {
                $map[$key] = $value;
            }
        }

        return $map;
    }

    private function setDefaultModelName()
    {
        $this->setModelName($this->getController());
    }

    /**********************************************/
    /*             getter / setter                */
    /**********************************************/
    public function setModule($module)
    {
        $this->module = $module ?: "Index";
        return $this;
    }

    public function setController($controller)
    {
        $this->controller = $controller ?: "index";
        return $this;
    }

    public function setAction($action)
    {
        $this->action = $action ?: "index";
        return $this;
    }


    public function setLimit($limits)
    {
        $this->limits = $limits;

        return $this;
    }

    public function setTimeFields($timeFields = [])
    {
        $this->timeFields = $timeFields;

        return $this;
    }

    public function setLikeFields($likeFields)
    {
        if (is_string($likeFields)) {
            $this->likeFields = explode(",", $likeFields);
        } else {
            $this->likeFields = $likeFields;
        }

        return $this;
    }

    public function setModelName($modelName)
    {
        $modelName = \ucfirst($modelName) ?: \ucfirst($this->getController());

        return $this;
    }

    public function setSuccessRedirectUrl($url)
    {
        $this->successRedirectUrl = $url;

        return $this;
    }

    public function setErrorRedirectUrl($url)
    {
        $this->errorRedirectUrl = $url;

        return $this;
    }

    public function setTplName($tpl = '')
    {
        $this->tplName = $tpl;
        return $this;
    }

    public function getLikeFields()
    {
        return $this->likeFields;
    }

    public function getLimits()
    {
        return $this->limits;
    }
    
    public function getTimeFields()
    {
        return $this->timeFields;
    }    

    public function getRequest()
    {
        return $this->request;
    }    

    public function getModule()
    {
        return $this->module;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getAction()
    {
        return $this->action;
    }
    
    public function getModelName()
    {
        return $this->modelName;
    }

    public function getSuccessRedirectUrl()
    {
        return $this->successRedirectUrl;
    }

    public function getErrorRedirectUrl()
    {
        return $this->errorRedirectUrl;
    }

    public function getTplName()
    {        
        return $this->tplName;
    }

}
