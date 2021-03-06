<?php

namespace GlaivePro\Hidevara;

use \Illuminate\Console\AppNamespaceDetectorTrait;
use App\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
	protected $superGlobies = ['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_SERVER', '_ENV'];
	
	protected function variableMatches($variable, $test)
	{	
		if (true === $test)
			return true;
		
		if (is_array($test) && in_array($variable, $test))
			return true;
		
		if (is_string($test) && preg_match($test, $variable))
			return true;
			
		return false;
	}
	
	protected function executeActionOnVariable($superG, $action, $field)
	{
		if ('expose' == $action)
			return;
		
		if ('hide' == $action)
		{
			$replaceWith = config('hidevara.replaceHiddenValueWith');
			if (null === $GLOBALS[$superG][$field] || '' === $GLOBALS[$superG][$field])
				$replaceWith = config('hidevara.replaceHiddenEmptyValueWith');
			
			$GLOBALS[$superG][$field] = $replaceWith;
			return;
		}
		
		// treat any unrecognized action as 'remove'
		unset($GLOBALS[$superG][$field]);
	}
	
	protected function dealWithField($superG, $field, $rules)
	{
		foreach ($rules as $action => $test)
			if ($this->variableMatches($field, $test))
			{
				$this->executeActionOnVariable($superG, $action, $field);
				return;
			}
		
		$this->executeActionOnVariable($superG, 'remove', $field);
	}
	
	protected function dealWithSuperG($superG)
	{
		if (!isset($GLOBALS[$superG]))
			return;
		
		$GLOBALS['hidevara'][$superG] = $GLOBALS[$superG];
		
		$rules = config('hidevara.'.$superG);
		
		foreach ($GLOBALS[$superG] as $field => $content)
			$this->dealWithField($superG, $field, $rules);
	}
	
    public function render($request, \Exception $exception)
    {
		foreach ($this->superGlobies as $superG)
			 $this->dealWithSuperG($superG);
		
        return parent::render($request, $exception);
    }
}
