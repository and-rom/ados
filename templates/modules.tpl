<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Шаблон для секции "Модули"
*/

/**
* Класс, содержащий функции
* для секции управления модулями.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class template_modules
{
	/**
	* Название неактивного модуля со
	* ссылкой для его активации
	* 
	* @param 	string	Название модуля
	* @param 	int		ID модуля
	*/
	
	function module_locked( $name="", $id=0 )
	{
		$return[] = <<<EOF
		<span style="text-decoration:line-through;">{$name}</span>&nbsp;<a href="javascript:ajax_module_enable('{$id}')"><img src='images/module_enable.png' alt='{$this->engine->lang['module_enable']}' title='{$this->engine->lang['module_enable']}' /></a>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Предупреждение о необходимости обновления
	* модуля
	* 
	* @param 	string	Минимальная необходимая версия
	*/
	
	function module_needs_update( $version="" )
	{
		$return[] = <<<EOF
		<div class="module_update_notifier">{$this->engine->lang['module_needs_update']} {$version}</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Текущая версия программы, обслуживаемой
	* модулем, со ссылкой на обновление
	* информации
	* 
	* @param 	string	Версия программы
	* @param 	int		ID модуля
	*/
	
	function module_version( $version="", $id=0 )
	{
		$version = $version ? $version : "-".$this->engine->lang['module_no_version_info']."-";
		
		$return[] = <<<EOF
		{$version}&nbsp;<a href="javascript:ajax_module_version('{$id}')"><img src='images/module_update.png' alt='{$this->engine->lang['module_update_version']}' title='{$this->engine->lang['module_update_version']}' /></a>
EOF;
		return implode( "\n", $return )."\n";
	}
	
}

?>