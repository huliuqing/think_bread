<?php
namespace app\index\controller;

use app\index\controller\Bread;

class Index extends Bread
{
	public function browser($model = "User", $tpl = "index")
	{
		// $this->setLimit(array("user_id" => 1));
        $this->setLikeFields(array("user_name"));
		return parent::browser($model, $tpl);
	}
	
	public function read($model = "User", $tpl = "read")
	{
		// $this->setLimit(array("user_id" => 2));
		return parent::read($model, $tpl);
	}
	
	public function edit($model = "User", $tpl = "edit")
	{
		return parent::edit($model, $tpl);
	}
	
	public function add($model = "User", $tpl = "add")
	{
		return parent::add($model, $tpl);
	}
	
	public function delete($model = "User", $jumpUrl = '')
	{
		return parent::delete($model, url("Index/browser"));
	}

	public function doAdd($model = "User")
	{
		return parent::doAdd($model);
	}

	public function doEdit($model = "User")
	{
		return parent::doEdit($model);
	}

	// public function browser()
	// {
	// 	dump($this->setModelName("User")->setTplName("index")->getModelName());
	// 	$this->setLikeFields(array("user_name"));
		
	// 	return parent::browser();
	// }
	
	// public function read($model = "User", $tpl = "read")
	// {
	// 	// $this->setLimit(array("user_id" => 2));
	// 	return parent::read($model, $tpl);
	// }
	
	// public function edit($model = "User", $tpl = "edit")
	// {
	// 	return parent::edit($model, $tpl);
	// }
	
	// public function delete($model = "User", $jumpUrl = '')
	// {
	// 	return parent::delete($model, url("Index/browser"));
	// }
}
