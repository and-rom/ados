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
* Класс, содержащий функции для
* страницы управления модулями.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class modules
{
	/**
	* HTML код для вывода на экран
	*
	* @var string
	*/

	var $html 			= "";
	
	/**
	* Информация о текущей странице
	*
	* @var array
	*/

	var $page_info		= array(	'title'	=> "",
									'desc'	=> "",
									);
									
	/**
	* Системное сообщение
	*
	* @var array
	*/

	var $message 		= array(	"text"	=> "",
									"type"	=> "",
									);
									
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Загружает языковые строки.
	* В зависимости от переданных параметров запускает
	* необходимую функцию.
	* 
	* @return	bool	TRUE
	*/
	
	function __class_construct()
	{
		$this->engine->load_lang( "modules" );
		
		//-----------------------------------------------
		// AJAX запрос
		//-----------------------------------------------
			
		if( $this->engine->input['ajax'] == 'yes' ) switch( $this->engine->input['type'] )
		{
			case 'info':
				$this->ajax_show_info();
				break;
					
			case 'settings':
			case 'settings_apply':
				$this->ajax_show_settings();
				break;
				
			case 'default':
				$this->ajax_default_module();
				break;
					
			case 'install':
				$this->ajax_install_module();
				break;
					
			case 'delete':
				$this->ajax_delete_module();
				break;
					
			case 'enable':
				$this->ajax_enable_module();
				break;
					
			case 'version':
				$this->ajax_update_module_version();
				break;
		}
			
		//-----------------------------------------------
		// Обычный запрос
		//-----------------------------------------------
			
		$this->engine->classes['output']->java_scripts['link'][] = "modules";
			
		$this->show_modules();
		
		return TRUE;
	}
	
	/**
    * Список модулей
    * 
    * Выводит список найденных модулей.
    *
    * @return	void
    */
	
	function show_modules()
	{
		//-----------------------------------------------
		// Название страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['page_title'];
		$this->page_info['desc']	= $this->engine->lang['page_desc'];
		
		//-----------------------------------------------
		// Форма со списком модулей
		//-----------------------------------------------
		
		$this->html = $this->engine->classes['output']->form_start( array(	'tab'		=> 'modules',
																			'action'	=> 'default',
																			), "id='ajax_form' onsubmit='ajax_module_choose(); return false;'" );
																			
		$this->html .= "<div id='list'>\n";
		
		$this->_get_modules_list();
		
		$this->html .= "</div>\n";
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
	
	/**
    * Вывод информации
    * 
    * Загружает информацию о модуле, формирует
    * на ее основе XHTML код и передает его на
    * вывод в XML класс.
    *
    * @return	void
    */
	
	function ajax_show_info()
	{
		//-----------------------------------------------
		// Загружаем информацию
		//-----------------------------------------------
		
		if( !is_numeric( $this->engine->input['id'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_module_id'] ) );
		}
		
		$module = $this->engine->DB->simple_exec_query( array(	'select'	=> '*',
																'from'		=> 'modules_list',
																'where'		=> "module_id='{$this->engine->input['id']}'"
																)	);
																
		if( !$module['module_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_wrong_module_id'] ) );
		}
		
		//-----------------------------------------------
		// Загружаем языковые строки и шаблон
		//-----------------------------------------------
		
		$this->engine->load_lang( "module_{$module['module_key']}" );
		
		$this->engine->lang['module_desc'] = $this->engine->lang['module_desc'] ? $this->engine->lang['module_desc'] : "--";
		
		$this->engine->load_skin( "modules" );
		
		//-----------------------------------------------
		// Помещаем полученную информацию в таблицу
		//-----------------------------------------------
		
		$module['module_author'] 		= $module['module_url']
										? "<a href='{$module['module_url']}' target='_blank'>{$module['module_author']}</a>"
										: $module['module_author'];
										
		$module['module_engine_author'] = $module['module_engine_url']
										? "<a href='{$module['module_engine_url']}' target='_blank'>{$module['module_engine_author']}</a>"
										: $module['module_engine_author'];
										
		$module['module_engine_version_support'] = str_replace( "[plus]", $this->engine->lang['engine_version_plus'], $module['module_engine_version_support'] );
		
		$module['module_engine_version_current'] = $this->engine->skin['modules']->module_version( $module['module_engine_version_current'], $module['module_id'] );
		
		$table  = $this->engine->classes['output']->form_start( array(	'tab'		=> 'modules',
																		), "onsubmit=\"my_getbyid('ajax_window').style.display='none'; ajax_window_loaded=null; return false;\"" );
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$table .= $this->engine->classes['output']->table_start( "", "100%", "", "", "id='ajax_table' style='border:0'" );
		
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_module_name']						, "row1" ),
								array(	$module['module_name']										, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_module_desc']						, "row1" ),
								array(	$this->engine->lang['module_desc']							, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_module_author']					, "row1" ),
								array(	$module['module_author']									, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_module_version']					, "row1" ),
								array(	$module['module_version']									, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_module_engine_author']			, "row1" ),
								array(	$module['module_engine_author']								, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_module_engine_version_support']	, "row1" ),
								array(	$module['module_engine_version_support']					, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_module_engine_version_current']	, "row1" ),
								array(	$module['module_engine_version_current']					, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_end();
								
		$table .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['ok'], "", "style='border:0;'" );
		
		//-----------------------------------------------
		// Формируем и возвращаем XML
		//-----------------------------------------------
		
		$html = $this->engine->skin['global']->ajax_window( $this->engine->lang['module_info']." ".$module['module_name'], &$table );
		
		$this->engine->classes['output']->generate_xml_output( array( 'HTML' => &$html ) );
	}
	
	/**
    * Вывод настроек
    * 
    * Загружает настройки модуля, формирует
    * на XHTML код со списком полученных настроек
    * и передает его на вывод в XML класс.
    *
    * @return	void
    */
	
	function ajax_show_settings()
	{
		//-----------------------------------------------
		// Загружаем информацию
		//-----------------------------------------------
		
		if( !is_numeric( $this->engine->input['id'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_module_id'] ) );
		}
		
		$module = $this->engine->DB->simple_exec_query( array(	'select'	=> 'module_id, module_key',
																'from'		=> 'modules_list',
																'where'		=> "module_id='{$this->engine->input['id']}'"
																)	);
																
		if( !$module['module_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_wrong_module_id'] ) );
		}
		
		//-----------------------------------------------
		// Загружаем языковые строки
		//-----------------------------------------------
		
		$this->engine->load_lang( "module_{$module['module_key']}" );
		
		//-----------------------------------------------
		// Обновляем настройки
		//-----------------------------------------------
		
		if( $this->engine->input['type'] == 'settings_apply' )
		{
			if( $this->engine->input['save'] == 'settings' )
			{
				$this->_apply_module_settings();
			
				$array['Message'] =& $this->engine->lang['settings_applied'];
			}
			else 
			{
				$this->_apply_module_defaults();
			
				$array['Message'] =& $this->engine->lang['defaults_applied'];
			}
		}
		
		//-----------------------------------------------
		// Загружаем настройки
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'modules_settings',
													'where'		=> "setting_module='{$this->engine->input['id']}'",
													'order'		=> 'setting_position',
													)	);
		$this->engine->DB->simple_exec();
																
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_module_settings'] ) );
		}
		
		//-----------------------------------------------
		// Помещаем полученную информацию в таблицу
		//-----------------------------------------------
		
		$form = $this->engine->classes['output']->form_start( array(	'tab'	=> 'modules',
																		'save'	=> 'settings',
																		), "id='settings_form' onsubmit='ajax_apply_settings( {$module['module_id']} ); return false;'" );
		
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$form .= $this->engine->classes['output']->table_start( "", "100%", "", "", "id='ajax_table' style='border:0'" );
		
		while( $setting = $this->engine->DB->fetch_row() )
		{
			$form .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang[ "setting_{$setting['setting_key']}" ]						, "row1" ),
								array(	$this->engine->classes['output']->parse_setting( &$setting, FALSE, 'medium' )	, "row2" ),
								)	);
		}
		
		$form .= $this->engine->classes['output']->table_add_submit_multi( array(	array( "submit", $this->engine->lang['apply_settings'], "", "onmousedown=\"my_getbyid('hidden_save').value='settings';\"" ),
																					array( "submit", $this->engine->lang['apply_defaults'], "", "onmousedown=\"my_getbyid('hidden_save').value='defaults';\"" )
																					)	);
		
		$form .= $this->engine->classes['output']->table_end();
		
		$form .= $this->engine->classes['output']->form_end();
		
		//-----------------------------------------------
		// Формируем и возвращаем XML
		//-----------------------------------------------
		
		$array['HTML'] = $this->engine->skin['global']->ajax_window( $this->engine->lang['module_settings']." ".$module['module_name'], &$form );
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Установка модуля по умолчанию
    * 
    * Проверяет, включен ли указанный модуль.
    * Если да, то устанавливает его как модуль по умолчанию.
    *
    * @return	void
    */
	
	function ajax_default_module()
	{
		//-----------------------------------------------
		// Загружаем информацию
		//-----------------------------------------------
		
		$module = $this->engine->DB->simple_exec_query( array(	'select'	=> 'module_id, module_enabled, module_default',
																'from'		=> 'modules_list',
																'where'		=> "module_id='{$this->engine->input['module_default']}'"
																)	);
																
		if( !$module['module_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_wrong_module_id'] ) );
		}
		
		if( !$module['module_enabled'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_module_disabled'] ) );
		}
		
		//-----------------------------------------------
		// Делаем модуль по умолчанию
		//-----------------------------------------------
		
		if( !$module['module_default'] )
		{
			$this->engine->DB->do_update( "modules_list", array( 'module_default' => 0 ), 1 );
			
			$this->engine->DB->do_update( "modules_list", array( 'module_default' => 1 ), "module_id='{$module['module_id']}'" );
		}
		
		//-----------------------------------------------
		// Формируем список модулей и возвращаем XML
		//-----------------------------------------------
		
		$this->_get_modules_list();
		
		$array = array(	'Message'	=> &$this->engine->lang['module_default_done'],
						'List' 		=> &$this->html,
						);
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Удаление модуля
    * 
    * Удаляет информацию и настройки из БД,
    * а также связанные с модулем директории и
    * файлы.
    * В XML класс передает информацию об ошибках
    * и (или) список установленных модулей.
    *
    * @return	void
    */
	
	function ajax_delete_module()
	{
		//-----------------------------------------------
		// Загружаем информацию
		//-----------------------------------------------
		
		$module = $this->engine->DB->simple_exec_query( array(	'select'	=> 'module_id, module_key, module_default',
																'from'		=> 'modules_list',
																'where'		=> "module_id='{$this->engine->input['id']}'"
																)	);
																
		if( !$module['module_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_wrong_module_id'] ) );
		}
		
		if( $module['module_default'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['js_error_module_is_active'] ) );
		}
		
		//-----------------------------------------------
		// Удаляем информацию и настройки из БД
		//-----------------------------------------------
		
		$this->engine->DB->do_delete( "modules_list", "module_id='{$this->engine->input['id']}'" );
		$this->engine->DB->do_delete( "modules_settings", "setting_module='{$this->engine->input['id']}'" );
		
		//-----------------------------------------------
		// Удаляем файлы, связанные с модулем
		//-----------------------------------------------
		
		$file = $this->engine->home_dir."modules/module_{$module['module_key']}.php";
		
		if( file_exists( $file ) and !@unlink( $file ) )
		{
			$cant_delete[] = $file;
		}
		
		$dir = $this->engine->home_dir."modules/{$module['module_key']}";
		
		if( file_exists( $dir ) and $this->engine->remove_dir( $dir ) !== TRUE )
		{
			$cant_delete[] = $dir;
		}
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'lang_key',
													'from'		=> 'languages',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $lang = $this->engine->DB->fetch_row() )
		{
			$file_path = $this->engine->home_dir."languages/{$lang['lang_key']}/module_{$module['module_key']}.lng";
			
			if( file_exists( $file_path ) and !@unlink( $file_path ) )
			{
				$cant_delete[] = $file_path;
			}
		}
		
		if( is_array( $cant_delete ) )
		{
			$array['Message'] = str_replace( "<#LIST#>", implode( "\n", $cant_delete ), $this->engine->lang['error_cant_delete_files'] );
		}
		
		//-----------------------------------------------
		// Формируем список модулей и возвращаем XML
		//-----------------------------------------------
		
		$this->_get_modules_list();
		
		$array['List'] = &$this->html;
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Включение модуля
    * 
    * Проверяет наличие программы и ее версию.
    * Если программы существует и ее текущая версия
    * поддерживается модулем, включает этот модуль.
    *
    * @return	void
    */
	
	function ajax_enable_module()
	{
		//-----------------------------------------------
		// Подгружаем класс для работы с программами
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "downloader" );
		
		//-----------------------------------------------
		// Проверяем исполняемый файл
		//-----------------------------------------------
		
		if( !$this->engine->classes['downloader']->module_exists( "", $this->engine->input['id'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->classes['downloader']->error ) );
		}
		
		//-----------------------------------------------
		// Проверяем версию программы
		//-----------------------------------------------
		
		if( $this->engine->classes['downloader']->load_module( "", $this->engine->input['id'] ) === FALSE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->classes['downloader']->error ) );
		}
		
		if( ( $version = $this->engine->classes['downloader']->module['class']->std_get_program_version() ) === FALSE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->classes['downloader']->module['class']->error ) );
		}
		
		if( strcmp( $this->engine->classes['downloader']->module['version'], $version ) > 0 )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_version_not_supported'] ) );
		}
		
		//-----------------------------------------------
		// Проверяем версию модуля
		//-----------------------------------------------
		
		$module = $this->engine->DB->simple_exec_query( array(	'select'	=> 'm.module_version, v.version_min',
																'from'		=> 'modules_versions m LEFT JOIN modules_versions m ON(v.version_module=m.module_key)',
																'where'		=> "m.module_id='{$this->engine->input['id']}'",
																)	);
																
		if( $module['version_min'] )
		{
			preg_match( "#(\d)\.(\d)\.(\d)( ([abr])(\d+))?#", $module['module_version'], $version['current'] );
			preg_match( "#(\d)\.(\d)\.(\d)( ([abr])(\d+))?#", $module['version_min'], $version['minimum'] );
			
			if( $version['current'][1] < $version['minimum'][1] or 
				$version['current'][2] < $version['minimum'][2] or
				$version['current'][3] < $version['minimum'][3] or
			   ($version['current'][4] and !$version['minimum'][4] ) or
			    strcmp( $version['current'][4], $version['minimum'][4] ) < 0 or 
			    $version['current'][5] < $version['minimum'][5] )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_module_needs_update'] ) );
			}
		}
		
		//-----------------------------------------------
		// Включаем модуль
		//-----------------------------------------------
		
		$this->engine->DB->do_update( "modules_list", array( 'module_enabled' => 1 ), "module_id='{$this->engine->input['id']}'" );
		
		//-----------------------------------------------
		// Формируем список модулей и возвращаем XML
		//-----------------------------------------------
		
		$this->_get_modules_list();
		
		$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['module_enabled'], 'List' =>& $this->html ) );		
	}
	
	/**
    * Обновление информации о версии
    * 
    * Проверяет текущую версию программы,
    * обслуживаемой модулем и заносит
    * полученную информацию в базу данных.
    *
    * @return	void
    */
	
	function ajax_update_module_version()
	{
		//-----------------------------------------------
		// Подгружаем класс для работы с программами
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "downloader" );
		
		//-----------------------------------------------
		// Проверяем исполняемый файл
		//-----------------------------------------------
		
		if( !$this->engine->classes['downloader']->module_exists( "", $this->engine->input['id'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->classes['downloader']->error ) );
		}
		
		//-----------------------------------------------
		// Проверяем версию программы
		//-----------------------------------------------
		
		if( $this->engine->classes['downloader']->load_module( "", $this->engine->input['id'] ) === FALSE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->classes['downloader']->error ) );
		}
		
		if( ( $version = $this->engine->classes['downloader']->module['class']->std_get_program_version() ) === FALSE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->classes['downloader']->module['class']->error ) );
		}
		
		//-----------------------------------------------
		// Обновляем информацию
		//-----------------------------------------------
		
		$this->engine->DB->do_update( "modules_list", array( 'module_engine_version_current' => &$version ), "module_id='{$this->engine->input['id']}'" );
		
		//-----------------------------------------------
		// Перезагружаем окно с информацией
		//-----------------------------------------------
		
		$this->ajax_show_info();
	}
	
	/**
    * Вывод списка модулей
    * 
    * Считывает данные из БД и на их основе
    * строит таблицу со списком модулей.
    *
    * @return	void
    */

	function _get_modules_list()
	{
		//-----------------------------------------------
		// Подгружаем шаблон
		//-----------------------------------------------
		
		$this->engine->load_skin( "modules" );
		
		//-----------------------------------------------
		// Получаем настройки модуля
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'm.*, v.version_min',
													'from'		=> 'modules_list m LEFT JOIN modules_versions v ON(v.version_module=m.module_key)',
													'order'		=> 'm.module_key'
													)	);
		$this->engine->DB->simple_exec();
		
		$this->engine->classes['output']->table_add_header( $this->engine->lang['module_default']	, "16%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['module_name']		, "30%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['module_version']	, "30%" );
		$this->engine->classes['output']->table_add_header( "&nbsp;"								, "8%" );
		$this->engine->classes['output']->table_add_header( "&nbsp;"								, "8%" );
		$this->engine->classes['output']->table_add_header( "&nbsp;"								, "8%" );
		
		$this->html .= $this->engine->classes['output']->table_start();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->message = array(	'text'	=> $this->engine->lang['warning_no_modules'],
									'type'	=> 'red'
									);
									
			$this->html .= $this->engine->classes['output']->table_add_row_single_cell( $this->engine->lang['no_modules'] );
		}
		else while( $module = $this->engine->DB->fetch_row() )
		{						
			if( !$module['module_enabled'] )
			{
				$enabled = "disabled='disabled'";
				
				$module['module_name'] = $this->engine->skin['modules']->module_locked( $module['module_name'], $module['module_id'] );
			}
			
			preg_match( "#(\d)\.(\d)\.(\d)( ([abr])(\d+))?#", $module['module_version'], $version['current'] );
			preg_match( "#(\d)\.(\d)\.(\d)( ([abr])(\d+))?#", $module['version_min'], $version['minimum'] );
			
			$module['module_version'] = str_replace( array( "a", "b", "r" ), array( "alpha ", "beta ", "RC " ), $module['module_version'] );
			
			if( $version['current'][1] < $version['minimum'][1] or 
				$version['current'][2] < $version['minimum'][2] or
				$version['current'][3] < $version['minimum'][3] or
			   ($version['current'][4] and !$version['minimum'][4] ) or
			    strcmp( $version['current'][4], $version['minimum'][4] ) < 0 or 
			    $version['current'][5] < $version['minimum'][5] )
			{
				$module['module_version'] .= $this->engine->skin['modules']->module_needs_update( &$module['version_min'] );
			}
			
			$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->skin['global']->form_radio( "module_default", $module['module_id'], $module['module_default'], "", $enabled )	, "row1", "style='text-align:center'" ),
								array(	$module['module_name']																											, "row2" ),
								array(	$module['module_version']																										, "row2" ),
								array(	$this->engine->skin['global']->element_button( $module['module_id'], "module", "info" )											, "row1", "style='text-align:center'" ),
								array(	$this->engine->skin['global']->element_button( $module['module_id'], "module", "settings" )										, "row1", "style='text-align:center'" ),
								array(	$this->engine->skin['global']->element_button( $module['module_id'], "module", "delete" )										, "row1", "style='text-align:center'" ),
								)	);
		}
		
		$this->html .= $this->engine->classes['output']->table_add_submit_multi( array(	array( "submit", $this->engine->lang['module_set_default']	, "", "onmousedown=\"my_getbyid('hidden_action').value='default';\"" ),
																						array( "submit", $this->engine->lang['module_add']			, "", "onmousedown=\"my_getbyid('hidden_action').value='install';\"" )
																						)	);
		
		$this->html .= $this->engine->classes['output']->table_end();
	}
	
	/**
    * Обновление настроек
    * 
    * Обновляет настройки модуля в соответствии
    * с переданными значениями.
    *
    * @return	void
    */
	
	function _apply_module_settings()
	{
		//-----------------------------------------------
		// Формируем массив с идентификаторами и значениями
		//-----------------------------------------------
		
		foreach( $this->engine->input as $key => $value ) if( preg_match( "#setting_(\d+)#", $key, $match ) )
		{
			$ids[ $match[1] ] = $value;
		}
		
		if( !is_array( $ids ) )
		{
			return;
		}
		
		//-----------------------------------------------
		// Получаем текущие настройки
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'setting_id, setting_default, setting_value, setting_actions',
													'from'		=> 'modules_settings',
													'where'		=> "setting_id IN('".implode( "','", array_keys( $ids ) )."')",
													)	);
		$this->engine->DB->simple_exec();
			
		while( $setting = $this->engine->DB->fetch_row() )
		{
			$settings[] = $setting;
		}
			
		//-----------------------------------------------
		// Обновляем настройки
		//-----------------------------------------------
			
		if( is_array( $settings ) ) foreach( $settings as $setting )
		{
			if( preg_match( "#before-save:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting );
			}
				
			if( $setting['setting_value'] != $ids[ $setting['setting_id'] ] and preg_match( "#before-update:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting );
			}
			
			$this->engine->DB->do_update( 'modules_settings', array( 'setting_value' => $ids[ $setting['setting_id'] ] ), "setting_id={$setting['setting_id']}  AND setting_module='{$this->engine->input['id']}'" );
		
			if( preg_match( "#after-save:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting );
			}
			
			if( $setting['setting_value'] != $ids[ $setting['setting_id'] ] and preg_match( "#after-update:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting );
			}
		}
	}
	
	/**
    * Восстановление умолчаний
    * 
    * Обновляет настройки модуля, присваивая
    * им значения по умолчанию.
    *
    * @return	void
    */
	
	function _apply_module_defaults()
	{
		//-----------------------------------------------
		// Получаем текущие настройки
		//-----------------------------------------------
			
		$this->engine->DB->simple_construct( array(	'select'	=> 'setting_id, setting_default, setting_value, setting_actions',
													'from'		=> 'modules_settings',
													)	);
		$this->engine->DB->simple_exec();
			
		while( $setting = $this->engine->DB->fetch_row() )
		{
			$settings[] = $setting;
		}
			
		//-----------------------------------------------
		// Обновляем настройки
		//-----------------------------------------------
			
		if( is_array( $settings ) ) foreach( $settings as $setting )
		{
			if( preg_match( "#before-default:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting );
			}
				
			$this->engine->DB->do_update( 'modules_settings', array( 'setting_value' => NULL ), "setting_id={$setting['setting_id']} AND setting_module='{$this->engine->input['id']}'" );
				
			if( preg_match( "#after-default:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting );
			}
		}
	}
	
	/**
    * Установка модуля
    * 
    * Загружает функцию установки в
    * соответствии с переданным номером
    * шага.
    *
    * @return	void
    */
	
	function ajax_install_module()
	{
		switch( $this->engine->input['id'] )
		{
			case '1':
				$this->_install_step_1();
				break;
				
			case '2':
				$this->_install_step_2();
				break;
				
			default:
				$this->_install_step_0();
				break;
		}
	}
	
	/**
    * Установка модуля (шаг 0)
    * 
    * Ищет файлы модулей в корневой директории
    * системы. Если файлы найдены, то выводит
    * их список.
    * Также выводит поле для загрузки установочных
    * файлов с компьютера пользователя.
    *
    * @return	void
    */

	function _install_step_0()
	{
		//-----------------------------------------------
		// Ищем файлы в корневой директории
		//-----------------------------------------------
		
		if( $dir = opendir( $this->engine->home_dir ) )
		{
			while( FALSE !== ( $file = readdir( $dir ) ) )
			{
	        	if( preg_match( "#^ados_module_((\w+)_([\d|\.]+)_?([a-zA-Z]+)?_?(\d+)?)\.tar\.gz$#i", $file, $match ) )
	        	{
	        		$modules[ $match[1] ] = ucfirst( str_replace( "_", " ", $match[2] ) )." ".$match[3];
	        		
	        		if( $match[4] ) $modules[ $match[1] ] .= " ".$match[4];
	        		if( $match[5] ) $modules[ $match[1] ] .= " ".$match[5];
	        	}
	    	}
	    	
	    	closedir( $dir );
		}
		else 
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_read_root_dir'] ) );
		}
		
		//-----------------------------------------------
		// Выводим форму
		//-----------------------------------------------
		
		$output  = $this->engine->classes['output']->form_start( array(	'tab'	=> 'modules',
																		'ajax'	=> 'yes',
																		'type'	=> 'install',
																		'id'	=> 1,
																		), "id='step_form' enctype='multipart/form-data' onsubmit=\"return Upload.submit( this, {'onLoad' : ajax_module_load, 'onComplete' : ajax_module_upload} )\"" );
																		
		$output .= "<div style='padding:5px;background-color:#f1f1f1;'>\n";
		$output .= $this->engine->lang['module_install_step_0_welcome']."<br/><br/>";
		
		if( is_array( $modules ) )
		{
			$output .= $this->engine->lang['module_install_step_0_choose'];
			$output .= "<table style='width=100%' cellspacing='0' cellpadding='0'>";
			
			$output .= "<tr><td width='35%' style='vertical-align:middle;padding-top:5px;'>";
			$output .= $this->engine->skin['global']->form_radio( "code", "install", 1, $this->engine->lang['module_install_step_0_do_install'] );
			$output .= "</td><td style='padding-top:5px;'>";
			$output .= $this->engine->skin['global']->form_dropdown( "install", &$modules, "", "medium" );
			$output .= "</td></tr>";
			
			$output .= "<tr><td width='35%' style='vertical-align:middle;padding-top:5px;'>";
			$output .= $this->engine->skin['global']->form_radio( "code", "upload", 0, $this->engine->lang['module_install_step_0_do_upload'] );
			$output .= "</td><td style='padding-top:5px;'>";
			$output .= $this->engine->skin['global']->form_upload( "upload", "medium" );
			$output .= "</td></tr>";
			
			$output .= "</table>";
		}
		else 
		{
			$output .= $this->engine->skin['global']->form_hidden( "code", "upload" );
			$output .= $this->engine->lang['module_install_step_0_upload'];
			$output .= "<div style='padding:7px 5px 0 5px;'>\n";
			$output .= $this->engine->skin['global']->form_upload( "upload", "medium" );
			$output .= "</div>\n";
		}
		
		$output .= "<div style='padding:5px'>\n";
		$output .= $this->engine->skin['global']->form_checkbox( "update", 0, $this->engine->lang['module_install_step_0_do_update'], "id='update'" );
		$output .= "</div>\n";
		$output .= "<div style='font-size:8pt;'>".$this->engine->lang['module_install_step_0_note']."</div>";
		
		$style = "style='border-bottom:0;border-left:0;border-right:0;'";
		
		$output .= "</div>\n";		
		$output .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['module_install_next'], "id='button_submit'", &$style, &$style );
		
		//-----------------------------------------------
		// Возвращаем XML
		//-----------------------------------------------
		
		$html = $this->engine->skin['global']->ajax_window( $this->engine->lang['module_install_title'], &$output );
		
		$this->engine->classes['output']->generate_xml_output( array( 'HTML' => &$html ) );
	}
	
	/**
    * Установка модуля (шаг 1)
    * 
    * Загружает установочный файл во временную папку и
    * туда же распаковывает его содержимое.
    * Если используется установочный файл, находящийся
    * в корневой директории, то выполняет только
    * распаковку содержимого.
    *
    * @return	void
    */
	
	function _install_step_1()
	{
		$encode_xml = $this->engine->input['code'] == 'upload' ? TRUE : FALSE;
		$tmp_dir 	= $this->engine->home_dir."tmp/";
		
		//-----------------------------------------------
		// Создаем временную директорию и очищаем ее
		//-----------------------------------------------
			
		if( !file_exists( &$tmp_dir ) and mkdir( &$tmp_dir ) === FALSE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_make_temp_dir'] ), $encode_xml );
		}
		
		$this->engine->remove_dir( &$tmp_dir, TRUE );
		
		//-----------------------------------------------
		// Загружаем файл
		//-----------------------------------------------
		
		if( $this->engine->input['code'] == 'upload' )
		{
			//-----------------------------------------------
			// Подгружаем класс для загрузки файла
			//-----------------------------------------------
			
			if( $this->engine->load_module( "class", "upload" ) === FALSE )
			{
				$this->engine->remove_dir( $tmp_dir );
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_load_module']."'Upload'." ), TRUE );
			}
			
			$this->engine->classes['upload']->save_path = $tmp_dir;
			
			//-----------------------------------------------
			// Сохраняем загруженный файл во временную
			// директорию и проверям наличие ошибок
			//-----------------------------------------------
			
			$this->engine->classes['upload']->upload_process();
			
			if( $this->engine->classes['upload']->error )
			{
				$this->engine->remove_dir( $tmp_dir );
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->classes['upload']->error ), TRUE );
			}
			
			$file_name = $this->engine->classes['upload']->parsed_file_name;
		}
		
		//-----------------------------------------------
		// Проверяем имеющийся файл
		//-----------------------------------------------
		
		else
		{
			$file_name = "ados_module_".$this->engine->input['install'].".tar.gz";
			
			if( !file_exists( $this->engine->home_dir.$file_name ) )
			{
				$this->engine->remove_dir( $tmp_dir );
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_find_install_file'] ) );
			}
			
			//-----------------------------------------------
			// Копируем его во временную директорию
			//-----------------------------------------------
			
			if( copy( $this->engine->home_dir.$file_name, $tmp_dir.$file_name ) === FALSE )
			{
				$this->engine->remove_dir( $tmp_dir );
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_copy_to_temp_dir'] ) );
			}
		}
		
		//-----------------------------------------------
		// Загружаем TAR класс
		//-----------------------------------------------
		
		if( $this->engine->load_module( "class", "tar", TRUE, array( $tmp_dir.$file_name ) ) === FALSE )
		{
			$this->engine->remove_dir( $tmp_dir );
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_load_module']."'Tar'." ), $encode_xml );
		}
		
		//-----------------------------------------------
		// Извлекаем файлы из архива во временную директорию
		//-----------------------------------------------
		
		if( $this->engine->classes['tar']->extract( $tmp_dir ) === FALSE )
		{
			$this->engine->remove_dir( $tmp_dir );
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->classes['tar']->error ), $encode_xml );
		}
		
		if( $dir = opendir( $tmp_dir ) )
		{
			//-----------------------------------------------
			// Проверяем файл настроек
			//-----------------------------------------------
			
			if( !is_readable( $tmp_dir."module_settings.xml" ) )
			{
				$this->engine->remove_dir( $tmp_dir );
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_read_module_settings'] ), $encode_xml );
			}
			
			//-----------------------------------------------
			// Читаем XML с настройками
			//-----------------------------------------------
			
			if( ( $xml = simplexml_load_file( $tmp_dir."module_settings.xml" ) ) === FALSE )
			{
				$this->engine->remove_dir( $tmp_dir );
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_read_module_settings'] ), $encode_xml );
			}
			
			//-----------------------------------------------
			// Получаем и сохраняем информацию о модуле
			//-----------------------------------------------
			
			$module = array();
			$values = array( 'key', 'name', 'author', 'url', 'version', 'engine_author', 'engine_url', 'engine_version_support' );
			
			foreach( $xml->module->children() as $name => $content )
			{
				if( in_array( $name, $values ) ) $module["module_{$name}"] = $content;
			}
			
			if( !$module['module_name'] or !$module['module_key'] )
			{
				$this->engine->remove_dir( $tmp_dir );
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_read_module_settings'] ), $encode_xml );
			}
			
			//-----------------------------------------------
			// Проверяем файл модуля
			//-----------------------------------------------
			
			if( !is_file( $tmp_dir."modules/module_{$module['module_key']}.php" ) )
			{
				$this->engine->remove_dir( $tmp_dir );
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_find_module_file'] ), $encode_xml );
			}
			
			//-----------------------------------------------
			// Если модуль уже установлен - обновляем его
			//-----------------------------------------------
			
			$present = $this->engine->DB->simple_exec_query( array(	'select'	=> 'module_id, module_name, module_version',
																	'from'		=> 'modules_list',
																	'where'		=> "module_key='{$module['module_key']}'"
																	)	);
					
			preg_match( "#(\d+)\.(\d+)\.(\d+)\s?(alpha|beta|rc)?\s?(\d+)?#i", $present['module_version'], $version['current'] );
			preg_match( "#(\d+)\.(\d+)\.(\d+)\s?(alpha|beta|rc)?\s?(\d+)?#i", $present['module_version'], $version['new'] );
			
			if( $version['current'][1] < $version['new'][1] or 
				$version['current'][2] < $version['new'][2] or
				$version['current'][3] < $version['new'][3] or
				$version['current'][3] and !$version['new'][3] or
				strcasecmp( $version['current'][4], $version['new'][4] ) < 0 or
				$version['current'][5] < $version['new'][5] )
			{
				$must_update = TRUE;
			}
			
			if( $present['module_name'] )
			{
				if( $present['module_name'] != $module['module_name'] or $must_update or !$this->engine->input['update'] )
				{
					$this->engine->remove_dir( $tmp_dir );
					$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_better_module_present'] ), $encode_xml );
				}
				
				$this->engine->DB->do_update( "modules_list", $module, "module_key='{$module['module_key']}'" );
				
				$module['module_id'] =& $present['module_id'];
				
				//-----------------------------------------------
				// Получаем настройки модуля
				//-----------------------------------------------
				
				if( $xml->settings )
				{
					$this->engine->DB->simple_construct( array(	'select'	=> 'setting_key, setting_id',
																'from'		=> 'modules_settings',
																'where'		=> "setting_module='{$module['module_id']}'"
																)	);
					$this->engine->DB->simple_exec();
					
					while( $setting = $this->engine->DB->fetch_row() )
					{
						$settings[ $setting['setting_key'] ] = $setting['setting_id'];
					}
				}
				else 
				{
					$settings = array();
				}
			}
			
			//-----------------------------------------------
			// Если модуль не установлен - устанавливаем
			//-----------------------------------------------
			
			else 
			{
				$this->engine->DB->do_insert( "modules_list", $module );
				
				$module['module_id'] = $this->engine->DB->get_insert_id();
				
				$settings = array();
			}
			
			//-----------------------------------------------
			// Проверяем настройки
			//-----------------------------------------------
			
			if( !$xml->settings )
			{
				$this->engine->remove_dir( $tmp_dir );
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_module_settings'] ), $encode_xml );
			}
			
			//-----------------------------------------------
			// Поочередно обрабатываем настройки
			//-----------------------------------------------
					
			foreach( $xml->settings->children() as $element )
			{
				$can_edit = FALSE;
				
				$values = array();
				
				//-----------------------------------------------
				// Настройку можно изменить
				//-----------------------------------------------
				
				if( $element['can_edit'] == 'true' )
				{
					$can_edit = TRUE;
				}
				
				//-----------------------------------------------
				// Настройку необходимо обновить
				//-----------------------------------------------
				
				if( $element['force_upgrade'] == 'true' )
				{
					$force_upgrade = TRUE;
				}
				
				//-----------------------------------------------
				// Обрабатываем и сохраняем (обновляем) настройку
				//-----------------------------------------------
				
				foreach( $element->children() as $type => $content )
				{
					$values[ $type ] = $content;
				}
				
				if( $values['key'] )
				{
					$save = array(	'setting_key'		=> $values['key'],
									'setting_default'	=> $values['default'],
									'setting_type'		=> $values['type'],
									'setting_position'	=> $values['position'],
									'setting_actions'	=> $values['action'],
									'setting_module'	=> $module['module_id']
									);
									
					if( $settings[ (string) $values['key'] ] )
					{
						if( preg_match( "#before-upgrade:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $save['setting_actions'], $match ) )
						{
							$this->engine->call_service_function( &$match, $save );
						}
			
						if( $force_upgrade ) $this->engine->DB->do_update( "modules_settings", $save, "setting_id='{$settings[ (string) $values['key'] ]}'" );
						
						$saved_ids[] = $settings[ (string) $values['key'] ];
						
						if( preg_match( "#after-upgrade:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $save['setting_actions'], $match ) )
						{
							$this->engine->call_service_function( &$match, $save );
						}
					}
					else 
					{
						if( preg_match( "#before-insert:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $save['setting_actions'], $match ) )
						{
							$this->engine->call_service_function( &$match, $save );
						}
						
						$this->engine->DB->do_insert( "modules_settings", $save );
						
						$saved_ids[] = $this->engine->DB->get_insert_id();
						
						if( preg_match( "#after-insert:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $save['setting_actions'], $match ) )
						{
							$this->engine->call_service_function( &$match, $save );
						}
						
						$save['setting_value'] =& $save['setting_default'];
						$save['setting_id'] = $this->engine->DB->get_insert_id();
						
						if( $can_edit ) $to_edit[ $this->engine->DB->get_insert_id() ] = $save;
					}
					
					$saved_settings[] = (string) $values['key'];
				}
			}
			
			if( is_array( $to_edit ) )
			{
				usort( $to_edit, array( "modules", "_sort_settings" ) );
			}
			
			//-----------------------------------------------
			// Удаляем устаревшие настройки
			//-----------------------------------------------
			
			if( is_array( $saved_settings ) and is_array( $settings ) )
			{
				$to_delete = array_intersect( array_keys( $settings ), $saved_settings );
				
				if( count( $to_delete ) ) $this->engine->DB->do_delete( "modules_settings", "setting_key IN ('".implode( "','", $to_delete )."') AND setting_module='{$module['module_id']}' AND setting_id NOT IN ('".implode( "','", $saved_ids )."')" );
			}
			
			//-----------------------------------------------
			// Копируем файлы из поддиректории lang
			//-----------------------------------------------
			
			$patterns = array(	'files'	=> array( "#^module_{$module['module_key']}\.lng$#" ),
								'dirs'	=> array( "#^[a-z]{2}$#i" ),
								);
			
			$this->engine->copy_dir( $tmp_dir."languages", $this->engine->home_dir."languages", 0755, FALSE, $patterns );
			
			//-----------------------------------------------
			// Копируем файлы из поддиректории modules
			//-----------------------------------------------
			
			if( is_file( $tmp_dir."modules/module_{$module['module_key']}.php" ) )
			{
				copy( $tmp_dir."modules/module_{$module['module_key']}.php", $this->engine->home_dir."modules/module_{$module['module_key']}.php" );
				
				if( is_dir( $tmp_dir."modules/{$module['module_key']}" ) )
				{
					$this->engine->copy_dir( $tmp_dir."modules/{$module['module_key']}", $this->engine->home_dir."modules/{$module['module_key']}", 0755 );
				}
			}
    	}
    	else 
    	{
    		$this->engine->remove_dir( $tmp_dir );
    		$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_read_temp_dir'] ), $encode_xml );
    	}
    	
    	//-----------------------------------------------
		// Удаляем временные файлы
		//-----------------------------------------------
		
		$this->engine->remove_dir( $tmp_dir );
		
		//-----------------------------------------------
		// Сообщение об успешной установке
		//-----------------------------------------------
		
		$complete_text	= $this->engine->input['update']
						? $this->engine->lang['module_install_step_1_updated']
						: $this->engine->lang['module_install_step_1_installed'];
						
		$complete_text = str_replace( "<#MODULE#>" , $module['module_name']		, $complete_text );
		$complete_text = str_replace( "<#VERSION#>", $module['module_version']	, $complete_text );
		
		$style = "style='border-bottom:0;border-left:0;border-right:0;'";
		
		//-----------------------------------------------
		// Если требуется, выводим список настроек
		//-----------------------------------------------
		
		if( is_array( $to_edit ) )
		{
			$this->engine->load_lang( "module_".$module['module_key'] );
			
			$output  = $this->engine->classes['output']->form_start( array(	'tab'		=> 'modules',
																			'module'	=> $module['module_id'],
																			), "id='step_form' onsubmit='ajax_module_install(2); return false;'" );
																			
			$output .= "<div style='padding:5px;background-color:#f1f1f1;'>\n";
			$output .= $complete_text."<br/>\n";
			$output .= $this->engine->lang['module_install_step_1_settings'];
			$output .= "</div>\n";
			$output .= "<div style='padding:5px;background-color:#f1f1f1;'>\n";
			
			$this->engine->classes['output']->table_add_header( "", "30%" );
			$this->engine->classes['output']->table_add_header( "", "70%" );
		
			$output .= $this->engine->classes['output']->table_start();
			
			foreach( $to_edit as $id => $setting )
			{
				$output .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang[ "setting_{$setting['setting_key']}" ]		, "row1" ),
								array(	$this->engine->classes['output']->parse_setting( &$setting )	, "row2" ),
								)	);
			}
			
			$output .= $this->engine->classes['output']->table_end();
			$output .= "</div>\n";
				
			$output .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['module_install_next'], "id='button_submit'", &$style, &$style );
		}
		
		//-----------------------------------------------
		// Если настраивать нечего, выводим сообщение об
		// успешной установке
		//-----------------------------------------------
		
		else 
		{
			$output  = $this->engine->classes['output']->form_start( array(	'tab'	=> 'modules',
																			), "onsubmit=\"my_getbyid('ajax_window').style.display='none'; ajax_window_loaded=null; return false;\"" );
																			
			$output .= "<div style='padding:5px;background-color:#f1f1f1;'>\n";
			$output .= $complete_text;
			$output .= "</div>";
			$output .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['module_install_done'], "", &$style, &$style );
			
			//-----------------------------------------------
			// Проверяем исполняемый файл модуля
			//-----------------------------------------------
			
			$this->engine->load_module( "class", "downloader" );
			
			if( $this->engine->classes['downloader']->module_exists( $module['module_key'] ) === FALSE )
			{
				$this->engine->DB->do_update( "modules_list", array( 'module_enabled' => 0 ), "module_id='{$module['module_id']}'" );
			}
			
			//-----------------------------------------------
			// Проверяем версию программы
			//-----------------------------------------------
			
			else if( $this->engine->classes['downloader']->load_module( "", $this->engine->input['id'] ) === FALSE )
			{
				$this->engine->DB->do_update( "modules_list", array( 'module_enabled' => 0 ), "module_id='{$module['module_id']}'" );
			}
			else if( ( $version = $this->engine->classes['downloader']->module['class']->std_get_program_version() ) === FALSE )
			{
				$this->engine->DB->do_update( "modules_list", array( 'module_enabled' => 0 ), "module_id='{$module['module_id']}'" );
			}
			else if( strcmp( $this->engine->classes['downloader']->module['version'], $version ) > 0 )
			{
				$this->engine->DB->do_update( "modules_list", array( 'module_enabled' => 0 ), "module_id='{$module['module_id']}'" );
			}
		}
		
		//-----------------------------------------------
		// Формируем список модулей и возвращаем XML
		//-----------------------------------------------
		
		$this->_get_modules_list();
		
		$html = $this->engine->skin['global']->ajax_window( $this->engine->lang['module_install_title'], &$output );
		
		$this->engine->classes['output']->generate_xml_output( array( 'HTML' => &$html, 'List' => &$this->html ), $encode_xml );
	}
	
	/**
    * Установка модуля (шаг 2)
    * 
    * Сохраняет настройки модуля.
    *
    * @return	void
    */
	
	function _install_step_2()
	{
		//-----------------------------------------------
		// Получаем список настроек
		//-----------------------------------------------
		
		foreach( $this->engine->input as $key => $value )
		{
			if( preg_match( "#^setting_(\d+)$#", $key, $match ) )
			{
				$ids[] = $match[1];
			}
		}
		
		//-----------------------------------------------
		// Получаем текущие настройки из БД
		//-----------------------------------------------
		
		if( is_array( $ids ) )
		{
			$this->engine->DB->simple_construct( array(	'select'	=> 'setting_id, setting_default',
														'from'		=> 'modules_settings',
														'where'		=> "setting_module='{$this->engine->input['module']}' AND setting_id IN('".implode( "','", $ids )."')"
														)	);
			$this->engine->DB->simple_exec();
			
			while( $setting = $this->engine->DB->fetch_row() )
			{
				$settings[ $setting['setting_id'] ] = $setting['setting_default'];
			}
			
			//-----------------------------------------------
			// Обновляем настройки
			//-----------------------------------------------
			
			if( is_array( $settings ) ) foreach( $settings as $id => $setting )
			{
				$new =& $this->engine->input["setting_{$id}"];
				
				$this->engine->DB->do_update( "modules_settings", array( 'setting_value' => $setting == $new ? NULL : $new ), "setting_id='{$id}'" );
			}
		}
		
		//-----------------------------------------------
		// Проверяем исполняемый файл модуля
		//-----------------------------------------------
			
		$this->engine->load_module( "class", "downloader" );
			
		if( $this->engine->classes['downloader']->module_exists( "", $this->engine->input['module'] ) !== FALSE )
		{
			$this->engine->DB->do_update( "modules_list", array( 'module_enabled' => 1 ), "module_id='{$this->engine->input['module']}'" );
		}
		
		//-----------------------------------------------
		// Проверяем версию программы
		//-----------------------------------------------
			
		if( $this->engine->classes['downloader']->load_module( "", $this->engine->input['module'] ) === FALSE )
		{
			$this->engine->DB->do_update( "modules_list", array( 'module_enabled' => 0 ), "module_id='{$this->engine->input['module']}'" );
		}
		else if( ( $version = $this->engine->classes['downloader']->module['class']->std_get_program_version() ) === FALSE )
		{
			$this->engine->DB->do_update( "modules_list", array( 'module_enabled' => 0 ), "module_id='{$this->engine->input['module']}'" );
		}
		else if( strcmp( $this->engine->classes['downloader']->module['version'], $version ) > 0 )
		{
			$this->engine->DB->do_update( "modules_list", array( 'module_enabled' => 0 ), "module_id='{$this->engine->input['module']}'" );
		}
		
		//-----------------------------------------------
		// Выводим сообщение об успешном обновлении настроек
		//-----------------------------------------------
		
		$style = "style='border-bottom:0;border-left:0;border-right:0;'";
		
		$output  = $this->engine->classes['output']->form_start( array(	'tab'	=> 'modules',
																			), "onsubmit=\"my_getbyid('ajax_window').style.display='none'; ajax_window_loaded=null; return false;\"" );
																			
		$output .= "<div style='padding:5px;background-color:#f1f1f1;'>\n";
		$output .= $this->engine->lang['module_install_step_2_complete'];
		$output .= "</div>";
		$output .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['module_install_done'], "", &$style, &$style );
		
		//-----------------------------------------------
		// Формируем список модулей и возвращаем XML
		//-----------------------------------------------
		
		$this->_get_modules_list();
		
		$html = $this->engine->skin['global']->ajax_window( $this->engine->lang['module_install_title'], &$output );
		
		$this->engine->classes['output']->generate_xml_output( array( 'HTML' => &$html, 'List' =>& $this->html ) );
	}
	
	/**
    * Сортировка настроек
    * 
    * Сортирует настройки модуля в зависимости
    * от указанного положения в списке.
    *
    * @return	void
    */
	
	function _sort_settings( $a, $b )
	{
		return $a['setting_position'] > $b['setting_position'] ? 1 : ( $a['setting_position'] == $b['setting_position'] ? 0 : -1 );
	}
}

?>