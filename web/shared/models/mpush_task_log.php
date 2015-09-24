<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class MPush_task_log
 * 推送任务记录
 */
class MPush_task_log extends MY_Model
{
	public $table = 't_push_task_log';
	public function __construct()
	{
		parent::__construct($this->table);
	}

}