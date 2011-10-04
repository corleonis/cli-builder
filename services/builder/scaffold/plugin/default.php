<?php

// No direct access.
defined('_JEXEC') or die;

class plg{{extName}} extends JPlugin 
{
	public function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
	}

	public function onContentPrepare($context, &$row, &$params, $page = 0) {
	}

}
