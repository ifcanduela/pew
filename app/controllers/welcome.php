<?php 

namespace app\controllers;

class Welcome extends \pew\Controller
{
	public function index($name)
	{
		return ['name'=> $name];
	}
}
