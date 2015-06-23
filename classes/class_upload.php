<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Страница управления модулями
*/

/**
* Класс, содержащий функции для загрузки файла
* и его дальнейшей обработки.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class upload
{
	/**
	* Ошибка (процесс загрузки прерывается)
	* 
	* @var string
	*/
	
	var $error				= FALSE;

	/**
	* Конечное имя файла
	* 
	* @var string
	*/
	
	var $parsed_file_name	= "";
	
	/**
	* Полный путь до конечного файла
	* 
	* @var string
	*/
	
	var $save_path			= ".";
	
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Загружает языковые строки.
	* 
	* @return	bool	TRUE
	*/
	
	function __class_construct()
	{
		$this->engine->load_lang( "upload" );
		
		$this->save_path = preg_replace( "#/$#", "", $this->save_path );
		
		return TRUE;
	}
	
	/**
	* Загрузка файла
	* 
	* Выполняет процесс загрузки файла, обрабатывая
	* его в соответствии с заданными для класса параметрами.
	* В случае возникновения ошибки прерывает процесс
	* загрузки и записывает текст ошибки в переменную
	* $this->error.
	* 
	* @return	void
	*/

	function upload_process()
	{
		//-------------------------------------------------
		// Получаем основные данные о файле
		//-------------------------------------------------

		$file_name =& $_FILES['upload']['name'];
		$file_type =& $_FILES['upload']['type'];
		$file_size =& $_FILES['upload']['size'];
		
		$file_size = intval( $file_size );

		//-------------------------------------------------
		// Проверям, есть ли содержимое
		//-------------------------------------------------

		$file_type = preg_replace( "/^(.+?);.*$/", "\\1", $file_type );

		if(	!$file_name or $file_name == "none" or !$file_size )
		{
			$this->error = $this->engine->lang['err_upload_is_empty'];
			return FALSE;
		}

		//-------------------------------------------------
		// Разрешенное расширение?
		//-------------------------------------------------

        if( !preg_match( "#\.tar\.gz$#", $file_name ) )
        {
			$this->error = $this->engine->lang['err_upload_not_allowed_ext'];
			return FALSE;
        }

		//-------------------------------------------------
		// Делаем имя файла безопасным
		//-------------------------------------------------

		$this->parsed_file_name = preg_replace( "#[^\w\.]#", "_", preg_replace( "#\.tar\.gz$#", "", $file_name ) ).'.tar.gz';

		//-------------------------------------------------
		// Копируем файл в указанную директорию
		//-------------------------------------------------

		$this->save_path .= '/'.$this->parsed_file_name;

		if ( !@move_uploaded_file( $_FILES['upload']['tmp_name'], $this->save_path ) )
		{
			$this->error = $this->engine->lang['err_upload_move_failed'];
			return FALSE;
		}
		else
		{
			@chmod( $this->save_path, 0777 );
		}
		
		return TRUE;
	}

}

?>