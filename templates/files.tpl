<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Шаблон для файлов
*/

/**
* Класс, содержащий функции
* для отображения свойств файлов.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class template_files
{
	/**
	* Текущее состояние файла
	* 
	* @param 	string	Идентификатор состояния
	* @param 	bool	Добавлять строку с указанием состояния
	*/
	
	function file_state( $state="query", $string=TRUE )
	{
		$return[] = <<<EOF
<img src="images/file_{$state}.png" alt="{$this->engine->lang['state_'.$state ]}" title="{$this->engine->lang['state_'.$state ]}" /> 
EOF;

	if( $string )
	{
		$return[] = <<<EOF
<span class="file_state_{$state}">{$this->engine->lang['state_'.$state]}</span>
EOF;
	}
	
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Возможность докачки файла
	* 
	* @param 	bool	Возможность имеется
	*/
	
	function file_range( $supported=FALSE )
	{
		if( is_bool( $supported ) or is_numeric( $supported ) )
		{
			$state = $supported ? "done" : "error";
			$string = $supported ? $this->engine->lang['supported'] : $this->engine->lang['unsupported'];
		}		
		else
		{
			$state = "unknown";
			$string = $this->engine->lang['unknown'];
		}
		
		$return[] = <<<EOF
<span class="file_state_{$state}">{$string}</span>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Текущая информация о состоянии закачки
	* 
	* @param 	string	Время
	* @param 	string	Размер
	* @param 	string	Процент
	*/
	
	function progress_info( $time="--", $size="--", $percent="--" )
	{
		$time = is_numeric( $time ) ? $this->engine->convert_time_measure( $time ) : "--";
		$size = is_numeric( $size ) ? $this->engine->convert_file_size( $size ) : "--";
		$percent = is_numeric( $percent ) ? sprintf( "%0.2f", $percent )."%" : "--";
		
		$return[] = <<<EOF
			<div class="progress_info">{$time}</div>
			<div class="progress_info">{$size}</div>
			<div class="progress_info">{$percent}</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Элементы управления закачкой
	* 
	* @param 	int		Идентификатор файла
	* @param 	string	Тип события
	*/
	
	function download_controls( $fid=0, $type="" )
	{
		$return[] = <<<EOF
		<div class="download_control"><a href="javascript:ajax_apply_file( {$fid}, '{$type}', 'download', 'run' );" title='{$this->engine->lang['download_run_button']}'><img src='images/download_run_single.png' alt='{$this->engine->lang['download_run']}' /></a></div>
		<div class="download_control"><a href="javascript:ajax_apply_file( {$fid}, '{$type}', 'download', 'pause' );" title='{$this->engine->lang['download_pause_button']}'><img src='images/download_pause_single.png' alt='{$this->engine->lang['download_pause']}' /></a></div>
		<div class="download_control"><a href="javascript:ajax_apply_file( {$fid}, '{$type}', 'download', 'stop' );" title='{$this->engine->lang['download_stop_button']}'><img src='images/download_stop_single.png' alt='{$this->engine->lang['download_stop']}' /></a></div>
		<div class="download_control"><a href="javascript:ajax_apply_file( {$fid}, '{$type}', 'download', 'delete' );" title='{$this->engine->lang['download_delete_button']}'><img src='images/download_delete_single.png' alt='{$this->engine->lang['download_delete']}' /></a></div>
		<div class="download_control" style="padding-left:20px;"><a href="javascript:ajax_update_state( {$fid}, 'download' );" title='{$this->engine->lang['download_refresh_button']}'><img src='images/download_refresh.png' alt='{$this->engine->lang['download_refresh']}' /></a></div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
}

?>