<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Страница закачек
*/

/**
* Класс, содержащий функции для
* страницы закачек.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class download
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
	* Активный пользователь
	*
	* @var array
	*/

	var $active_user	= array(	'id'	=> 0,
									'sub'	=> 'all',
									);
		
	/**
	* Количество доступных страниц
	*
	* @var array
	*/

	var $pages_total	= 0;								
									
	/**
	* Тип сортировки списка закачек
	*
	* @var string
	*/
	
	var $sort_by		= "";
	
	/**
	* Шаблон ссылок для закачки и наборы
	* зарезервированных символов
	*
	* @var array
	*/
	
	var $patterns	= array(	'links'		=> "",
								'urls'		=> "",
								'illegal'	=> "\\/?%\*:|\"<>",
								);
										
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Загружает языковые строки, шаблон и JavaScript.
	* В зависимости от переданных параметров запускает
	* необходимую функцию.
	* 
	* @return	bool	TRUE
	*/
	
	function __class_construct()
	{
		$this->engine->load_lang( "download" );
		$this->engine->load_skin( "download" );
		
		$this->engine->classes['output']->java_scripts['link'][] = "download";
		$this->engine->classes['output']->java_scripts['link'][] = "files";
		
		if( $this->engine->config['reload_time'] )
		{
			$this->engine->classes['output']->java_scripts['footer'][] = "setInterval( ajax_download_refresh, ".( 1000 * $this->engine->config['reload_time'] )." );";
		}
		
		$this->engine->classes['output']->java_scripts['footer'][] = "setInterval( ajax_check_position, 100 )";
		
		//-----------------------------------------------
		// Создаем шаблон для определения URL
		//-----------------------------------------------
		
		$ip_num = '(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])';
		
		$this->patterns['links'] = "#^(http|ftp)://(([\w-]*\.)*[a-zA-Z]{2,5}|$ip_num\\.$ip_num\\.$ip_num\\.$ip_num)(:\d{1,5})?/([^\s]+)?( \[desc\](.+?)\[/desc])?+$#i";
		$this->patterns['urls']  = "#^(http|ftp)://(([\w-]*\.)*[a-zA-Z]{2,5}|$ip_num\\.$ip_num\\.$ip_num\\.$ip_num)(:\d{1,5})?/(.+)?( \[desc\](.+?)\[/desc])?+$#i";
		
		//-----------------------------------------------
		// Определение текущей страницы
		//-----------------------------------------------
		
		if( !is_numeric( $this->engine->input['st'] ) ) $this->engine->input['st'] = 0;
		
		$this->engine->input['page'] = ceil( $this->engine->input['st'] / 100 ) + 1;
		
		//-----------------------------------------------
		// AJAX запрос
		//-----------------------------------------------
		
		if( $this->engine->input['ajax'] == 'yes' ) switch( $this->engine->input['type'] )
		{
			case 'show_items':
			case 'set_page':
				$this->ajax_show_items();
				break;
				
			case 'download_add':
				$this->ajax_show_add_form();
				break;
				
			case 'download_parse':
				$this->ajax_parse_add_form();
				break;
				
			case 'download_confirm':
				$this->ajax_parse_confirm_form();
				break;
				
			case 'download_change_state':
				$this->ajax_download_change_state();
				break;
				
			case 'file_add':
				$this->ajax_add_download();
				break;
				
			case 'file_edit':
			case 'download_edit':
				$this->ajax_show_download_form();
				break;
				
			case 'file_refresh':
				$this->ajax_refresh_download_info();
				break;
		}
		
		//-----------------------------------------------
		// Запрос на построение изображения
		//-----------------------------------------------
		
		if( isset( $this->engine->input['progress'] ) )
		{
			$this->_show_progress_bar();
		}
		
		//-----------------------------------------------
		// Обычный запрос
		//-----------------------------------------------
			
		$this->show_downloads();
		
		return TRUE;
	}
	
	/**
    * Расписание закачек
    * 
    * Выводит расписание закачек для всех пользователей
    * сразу или только для определенного пользователя.
    *
    * @return	void
    */
	
	function show_downloads()
	{
		//-----------------------------------------------
		// Название страницы и системное сообщение
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['page_title'];
		$this->page_info['desc']	= $this->engine->lang['page_desc'];
		
		$this->message = array(	'text'	=> "",
								'type'	=> "",
								);
		
		//-----------------------------------------------
		// Выводим меню
		//-----------------------------------------------
		
		$this->html  = $this->engine->skin['download']->page_top();
		
		$this->html .= $this->engine->skin['download']->users_list_top();
		
		//-----------------------------------------------
		// Выводим список пользователей
		//-----------------------------------------------
		
		$this->_get_users_list();
		
		$this->html .= $this->engine->skin['download']->users_list_bottom();
		
		//-----------------------------------------------
		// Выводим список файлов в активной категории
		//-----------------------------------------------
		
		$this->html .= $this->engine->skin['download']->page_middle();
		
		$this->engine->input['id']  =& $this->active_user['id'];
		$this->engine->input['sub'] =& $this->active_user['sub'];
		
		$this->_get_downloads_list();
		
		$this->html .= $this->engine->skin['download']->page_bottom();
		
		$this->html  = str_replace( "<!--PAGE_MENU-->", $this->engine->skin['download']->page_menu(), $this->html );
	}
	
	/**
    * Вывод списка закачек
    * 
    * Вызывает функцию для составления списка
    * закачек, находящихся в указанном состоянии.
    *
    * @return	void
    */
	
	function ajax_show_items()
	{
		//-----------------------------------------------
		// Получаем информацию о закачках
		//-----------------------------------------------
		
		if( !is_numeric( $this->engine->input['id'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_item_id'] ) );
		}
		
		$this->_get_downloads_list();
		
		//-----------------------------------------------
		// Обновляем содержимое и возвращаем XML
		//-----------------------------------------------
		
		$array = array(	"List"			=> &$this->html,
						"Update_11"		=> $this->engine->skin['download']->page_menu(),
						"Function_0"	=> "update_pages_number({$this->pages_total},{$this->engine->input['st']})",
						"Function_1"	=> "ajax_reselect_items()",
						);
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Вывод формы для добавления закачек
    * 
    * Выводит на экран форму для добавления одной или
    * нескольких ссылок на закачиваемые файлы.
    *
    * @return	void
    */
	
	function ajax_show_add_form()
	{
		//-----------------------------------------------
		// Проверяем права на создание собственных закачек
		//-----------------------------------------------
		
		if( $this->engine->config['use_share_cats'] and ( !$this->engine->member['user_admin'] and $this->engine->input['id'] != 0 ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_shared_downloads_only'] ) );
		}
		else if( !$this->engine->member['user_admin'] and !in_array( $this->engine->input['id'], array( 0, $this->engine->member['user_id'] ) ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_rights'] ) );
		}
		
		//-----------------------------------------------
		// Создаем форму
		//-----------------------------------------------
		
		$style = "style='border-bottom:0;border-left:0;border-right:0;'";
		
		$output  = $this->engine->classes['output']->form_start( array(	'tab'	=> 'download',
																			), "id='ajax_form' onsubmit='ajax_parse_form(); return false;'" );
																			
		$output .= "<div style='padding:5px;background-color:#f1f1f1;'>\n";
		$output .= $this->engine->lang['form_add_links_here']."<br/>";
		$output .= $this->engine->skin['global']->form_textarea( "links", "", "large", "id='links_list' style='margin-top:5px;height:100px;width:580px;'" );
		$output .= "</div>";
		$output .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['form_next'], "", &$style, &$style );
		
		//-----------------------------------------------
		// Возвращаем XML
		//-----------------------------------------------
		
		$html = $this->engine->skin['global']->ajax_window( $this->engine->lang['form_add_links_title'], &$output );
		
		$this->engine->classes['output']->generate_xml_output( array( 'HTML' => &$html ) );
	}
	
	/**
    * Обработка формы добавления закачек
    * 
    * Обрабатывает значение поля формы добавления закачек и
    * ищет в нем ссылки на файлы.
    * Передает управление функции в зависимости от количества
    * найденных ссылок.
    *
    * @return	void
    */
	
	function ajax_parse_add_form()
	{
		//-----------------------------------------------
		// Подгружаем класс работы с файлами
		//-----------------------------------------------
			
		$this->engine->load_module( "class", "files" );
		
		$this->engine->classes['files']->parent = &$this;
		
		//-----------------------------------------------
		// Проверяем, есть ли значение поля
		//-----------------------------------------------
		
		if( trim( $this->engine->input['links'] ) == "" )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['js_error_links_list_empty'] ) );
		}
		
		$got_it = FALSE;
		
		//-----------------------------------------------
		// Обрабатываем явно заданные ссылки
		//-----------------------------------------------
		
		if( preg_match_all( "#\[url\](.+?)\[/url\]#", $this->engine->input['links'], $urls, PREG_SET_ORDER ) )
		{
			foreach( $urls as $url ) if( preg_match( $this->patterns['urls'], $url[1], $mtch ) )
			{
				$more_match[] = $mtch;
				
				$got_it = TRUE;
			}
		}
		
		$this->engine->input['links'] = preg_replace( "#\[url\](.+?)\[/url\]#", "", $this->engine->input['links'] );
		
		//-----------------------------------------------
		// Ищем ссылки
		//-----------------------------------------------
		
		if( preg_match_all( $this->patterns['links']."m", $this->engine->input['links'], $match, PREG_SET_ORDER ) )
		{
			$got_it = TRUE;
		}
		
		//-----------------------------------------------
		
		if( $got_it === FALSE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_links'] ) );
		}
		else if( is_array( $more_match ) )
		{
			$match = array_merge( $match, $more_match );
		}
		
		foreach( $match as $link )
		{
			foreach( $link as $lid => $str ) $link[ $lid ] = str_replace( "%20", " ", $str );
			
			if( ( $url = $this->engine->classes['files']->_parse_link( $link ) ) === FALSE ) continue;
			
			$links[] = array(	'link'	=> $link[1]."://".$link[2].$link[8]."/".$url,
								'desc'	=> $link[11] ? $this->engine->urludecode( $link[11] ) : ""
								);
		}
		
		if( ( $count = count( $links ) ) > 1 )
		{
			$this->ajax_show_confirm_form( &$links );
		}
		else if( $count == 1 )
		{
			$this->ajax_show_download_form( &$links[0] );
		}
		else 
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_links'] ) );
		}
	}
	
	/**
    * Вывод формы подтверждения добавления закачек
    * 
    * Выводит форму со списком найденных после обработки предыдущей
    * формы ссылок на файлы с тем, чтобы пользователь выбрал те ссылки
    * из списка, которые требуется закачать.
    * 
    * @param 	array			Список ссылок
    *
    * @return	void
    */
	
	function ajax_show_confirm_form( $links )
	{
		if( !is_array( $links ) ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_links'] ) );
		
		$priorities = array( 0 => $this->engine->lang['priority_low'],
							 1 => $this->engine->lang['priority_med'],
							 2 => $this->engine->lang['priority_high'],
							 );
							 
		//-----------------------------------------------
		// Стили для Оперы
		//-----------------------------------------------
		
		if( strstr( strtolower( $_SERVER['HTTP_USER_AGENT'] ), "opera" ) )
		{
			$style_opera = "margin-left:21px;width:521px;";
		}
		
		//-----------------------------------------------
		// Создаем форму
		//-----------------------------------------------
		
		$style = "style='border-bottom:0;border-left:0;border-right:0;'";
		
		$output  = $this->engine->classes['output']->form_start( array(	'tab'	=> 'download',
																			), "id='ajax_form' onsubmit='ajax_confirm_form(); return false;'" );
																			
		$output .= "<div style='padding:5px;background-color:#f1f1f1;'>\n";
		$output .= "<div style='padding-bottom:5px;'>".$this->engine->lang['form_add_links_confirm']."</div>";
		$output .= "<div id='can_overflow' style='overflow:auto;' >\n";
		
		foreach( $links as $lid => $link )
		{
			$display = $link['desc'] ? "" : "display:none;";
			
			$output .= "<div style='padding-top:5px;'>";
			$output .= $this->engine->skin['global']->form_checkbox( "link_add_{$lid}", 1, "", "onclick='ajax_check_links_state(this)'" );
			$output .= " ";
			$output .= $this->engine->skin['global']->form_text( "link_{$lid}", $link['link'], "confirm" );
			$output .= $this->engine->skin['global']->form_dropdown( "link_{$lid}_priority", $priorities, 1, "confirm" );
			$output .= $this->engine->skin['download']->desc_button( &$link['desc'], &$lid );
			$output .= $this->engine->skin['global']->form_textarea( "link_{$lid}_desc", $link['desc'], "confirm", "id='desc_{$lid}' style='{$display}{$style_opera}'" );
			$output .= "</div>\n";
		}
		
		$output .= "</div>\n";
		$output .= "<div style='padding-top:5px;'>";
		$output .= $this->engine->skin['global']->form_checkbox( "link_add_all", 1, "", "onclick='ajax_check_all_links(this)'" );
		$output .= " ";
		$output .= $this->engine->lang['form_add_links_check_all'];
		$output .= "</div>\n";
		$output .= "</div>";
		$output .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['form_next'], "", &$style, &$style );
		
		//-----------------------------------------------
		// Возвращаем XML
		//-----------------------------------------------
		
		$html = $this->engine->skin['global']->ajax_window( $this->engine->lang['form_add_links_title'], &$output );
		
		$this->engine->classes['output']->generate_xml_output( array( 'HTML' => &$html ) );
	}
	
	/**
    * Обработка формы подтверждения добавления закачек
    * 
    * Проверяет правильность переданных ссылок и сохраняет
    * их в кэше. Затем для каждой из сохраненных ссылок
    * выводит окно свойств.
    *
    * @return	void
    */
	
	function ajax_parse_confirm_form()
	{
		//-----------------------------------------------
		// Подгружаем класс работы с файлами
		//-----------------------------------------------
			
		$this->engine->load_module( "class", "files" );
		
		$this->engine->classes['files']->parent = &$this;
		
		//-----------------------------------------------
		
		if( $this->engine->input['uid'] and preg_match( "#^[a-zA-Z0-9]{32}$#", $this->engine->input['uid'] ) )
		{
			//-----------------------------------------------
			// Устанавливаем идентификатор типа операции
			//-----------------------------------------------
			
			$this->engine->input['type'] = 'file_add';
		}
		else 
		{
			//-----------------------------------------------
			// Получаем ссылки
			//-----------------------------------------------
			
			foreach( $this->engine->input as $name => $value )
			{
				if( preg_match( "#^link_(\d+)$#", $name, $match ) and $value and $this->engine->input['link_add_'.$match[1] ] ) $links[ $match[1] ] = $value;
			}
			
			if( !is_array( $links ) ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_links'] ) );
			
			//-----------------------------------------------
			// Проверяем переданные ссылки
			//-----------------------------------------------
			
			foreach( $links as $lid => $link )
			{
				$links[ $lid ] = $link = str_replace( "%20", " ", $link );
				
				if( !preg_match( $this->patterns['urls'], $link, $match ) or $this->engine->classes['files']->_parse_link( $match ) === FALSE )
				{
					$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_links'] ) );
				}
			}
			
			//-----------------------------------------------
			// Сохраняем ссылки в кэш
			//-----------------------------------------------
			
			$uid = md5( microtime().rand() );
			
			$time_now = time();
			
			foreach( $links as $lid => $link )
			{
				$value = array(	'link'	=> $link,
								'desc'	=> $this->engine->urludecode( $this->engine->input['link_'.$lid.'_desc'] )
								);
				
				$this->engine->DB->do_insert( "system_cache", array( 'cache_name'		=> "links_list",
																	 'cache_value'		=> serialize( $value ),
																	 'cache_uid'		=> $uid,
																	 'cache_added'		=> $time_now,
																	 'cache_priority'	=> intval( $this->engine->input['link_'.$lid.'_priority'] )
																	 )	);
			}
			
			//-----------------------------------------------
			// Составляем список параметров
			//-----------------------------------------------
			
			$this->engine->input['type']	= 'file_add';
			$this->engine->input['cached']	= count( $links ) + 1;
			$this->engine->input['uid']		= $uid;
			
			unset( $links );
		}
		
		//-----------------------------------------------
		// Передаем управление функции вывода окна
		//-----------------------------------------------
		
		$this->engine->classes['files']->properties_window();
	}
	
	/**
    * Вывод формы управления закачкой
    * 
    * Выводит форму с параметрами закачки и инструментами управления
    * ее состоянием.
    * 
    * @param 	array	[opt]	( 'link' => Ссылка на закачиваемый файл,
    * 							  'desc' => Описание файла
    * 							  )
    *
    * @return	void
    */
	
	function ajax_show_download_form( $link=array() )
	{
		if( !in_array( $this->engine->input['type'], array( 'download_edit', 'file_edit' ) ) and !preg_match( $this->patterns['urls'], $link['link'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_link'] ) );
		}
		
		//-----------------------------------------------
		// Подгружаем класс работы с файлами
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "files" );
		
		$this->engine->classes['files']->parent = &$this;
		
		//-----------------------------------------------
		// Выводим форму с параметрами закачки
		//-----------------------------------------------
		
		if( !in_array( $this->engine->input['type'], array( 'download_edit', 'file_edit' ) ) )
		{
			$this->engine->input['type'] = 'file_add';
			$this->engine->input['link'] = &$link['link'];
			$this->engine->input['desc'] = &$link['desc'];
		}
		
		$this->engine->classes['files']->properties_window();
	}
	
	/**
    * Изменение состояния закачек
    * 
    * Изменяет состояние закачек с переданными идентификаторами
    * на указанное.
    * 
    * @param 	string	[opt]	Ссылка на закачиваемый файл
    *
    * @return	void
    */
	
	function ajax_download_change_state()
	{
		//-----------------------------------------------
		// Можно ли изменить состояние?
		//-----------------------------------------------
		
		if( !in_array( $this->engine->input['state'], array( "run", "pause", "stop", "delete" ) ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_change_state'] ) );
		}
		
		//-----------------------------------------------
		// Проверяем переданные идентификаторы
		//-----------------------------------------------
		
		$ids = explode( ",", $this->engine->input['id'] );
		
		if( !$ids[0] ) unset( $ids[0] );
		
		if( !count( $ids ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_ids_to_change'] ) );
		}
		
		//-----------------------------------------------
		// Получаем сведения о закачках
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'file_id, file_user, file_dl_module',
													'from'		=> 'categories_files',
													'where'		=> "file_id IN('".implode( "','", $ids )."')",
													'order'		=> 'file_dl_last_start, file_id'
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_ids_found'] ) );
		}
		
		while( $file = $this->engine->DB->fetch_row() )
		{
			if( ( $file['file_user'] and $file['file_user'] != $this->engine->member['user_id'] ) or ( !$file['file_user'] and !$this->engine->config['shared_can_control'] ) ) continue;
			else $modules[ $file['file_dl_module'] ][] = $file;
		}
		
		if( !is_array( $modules ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_modules'] ) );
		}
		
		//-----------------------------------------------
		// Подгружаем класс для работы с файлами
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "files", FALSE );
		
		//-----------------------------------------------
		// Для файлов каждого модуля изменяем состояние
		//-----------------------------------------------
		
		foreach( $modules as $mid => $files ) foreach( $files as $file )
		{
			if( $this->engine->classes['files']->change_download_state( $this->engine->input['state'], $file['file_id'], $file['file_dl_module'], FALSE ) === FALSE )
			{
				// PUT TO LOG
			}
		}
		
		//-----------------------------------------------
		// Запускаем сохраненные в кэше закачки
		//-----------------------------------------------
		
		if( is_array( $this->engine->cache['download']['files'] ) )
		{
			$this->engine->classes['downloader']->download_start_cached();
		}
		
		//-----------------------------------------------
		// Обновляем список файлов и возвращаем XML
		//-----------------------------------------------
		
		$this->engine->input['id'] =& $this->engine->input['auser'];
		$this->engine->input['sub'] =& $this->engine->input['asub'];
		
		$this->_get_downloads_list();
		
		$this->engine->classes['output']->generate_xml_output( array( "List" => &$this->html, "Function_0" => "ajax_reselect_items()" ) );
	}
	
	/**
    * Сохранение закачки
    * 
    * Сохраняет параметры закачки в БД и при необходимости
    * передает управление модулю для начала закчки.
    *
    * @return	void
    */	
	
	function ajax_add_download()
	{
		//-----------------------------------------------
		// Проверяем права на добавление закачки
		//-----------------------------------------------
		
		if( $this->engine->config['use_share_cats'] and !$this->engine->member['user_admin'] and $this->engine->input['auser'] != 0 )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['js_error_wrong_user'] ) );
		}
		else if( !in_array( $this->engine->input['auser'], array( 0, $this->engine->member['user_id'] ) ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_rights'] ) );
		}
		
		//-----------------------------------------------
		// Подгружаем класс работы с файлами
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "files" );
		
		$this->engine->classes['files']->parent = &$this;
		
		//-----------------------------------------------
		// Выводим форму с параметрами закачки
		//-----------------------------------------------
		
		$this->engine->input['type'] = 'file_add';
		
		$this->engine->classes['files']->properties_window();
	}
	
	/**
    * Обновление информации о закачке
    * 
    * Подгружает модуль, ассоциированный с закачиваемым
    * файлом и запускает для него функцию обновления
    * пареметров закачки.
    * Далее считывает обновленне параметры и возвращает
    * их в виде XML.
    *
    * @return	void
    */	
	
	function ajax_refresh_download_info()
	{
		//-----------------------------------------------
		// Получаем информацию о файле
		//-----------------------------------------------
		
		$file = $this->engine->DB->simple_exec_query( array(	'select'	=> '*',
																'from'		=> 'categories_files',
																'where'		=> "file_id='{$this->engine->input['id']}'"
																)	);
								
		if( !$file['file_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_download_id'] ) );
		}
		
		$this->engine->load_skin( "files" );
		
		if( $file['file_state'] == 'done' )
		{
			$file['file_dl_left'] = 0;
			$file['file_dl_time'] = 0;
		}
		else if( $file['file_state'] == 'running' )
		{
			//-----------------------------------------------
			// Подгружаем класс для работы с модулем файла
			//-----------------------------------------------
			
			$this->engine->load_module( "class", "downloader" );
			
			if( $this->engine->classes['downloader']->load_module( "", $file['file_dl_module'] ) === FALSE )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->classes['downloader']->error ) );
			}
			
			//-----------------------------------------------
			// Вызываем функцию для обновления информации
			//-----------------------------------------------
			
			if( $this->engine->classes['downloader']->update_download_state( &$file ) === FALSE )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->classes['downloader']->error ) );
			}
			
			$file['file_dl_left'] = $this->engine->classes['downloader']->module['class']->state['file_dl_left'];
			$file['file_dl_time'] = $this->engine->classes['downloader']->module['class']->state['file_dl_time'];
		}
		
		//-----------------------------------------------
		// Обрабатываем полученную информацию
		//-----------------------------------------------
		
		$file['file_time_used']  = time() - $file['file_dl_start'];
		$file['file_dl_done']    = floor( $file['file_size'] - $file['file_dl_left'] );
		$file['file_dl_percent'] = $file['file_size'] ? round( $file['file_dl_done'] / $file['file_size'] * 100, 2 ) : 0;
		
		//-----------------------------------------------
		// Обновляем также список закачек
		//-----------------------------------------------
		
		$this->engine->input['id'] =& $this->engine->input['auser'];
		$this->engine->input['sub'] =& $this->engine->input['asub'];
		
		$this->_get_downloads_list();
		
		//-----------------------------------------------
		// Формируем и возвращаем XML
		//-----------------------------------------------
		
		$array = array(	'Update_2'	 => $this->engine->convert_file_size( &$file['file_size'] ),
						'Update_7'	 => $this->engine->skin['files']->file_state( &$file['file_state'] ),
						'Function_0' => 'ajax_change_field_access('.( in_array( $file['file_state'], array( 'running', 'paused' ) ) ? '0' : '1' ).')',
						'Function_1' => "ajax_reselect_items()",
						'List'		 => &$this->html,
						);
						
		if( in_array( $file['file_state'], array( 'done', 'idle', 'error', 'query' ) ) )
		{
			$array['Update_3'] = $this->engine->skin['files']->progress_info();
			$array['Update_4'] = $this->engine->skin['files']->progress_info();
			$array['Update_5'] = "<img src='{$this->engine->base_url}tab=download&progress=".( $file['file_state'] == "done" ? 100 : 0 )."' alt='' />";
			$array['Update_6'] = "--";
		}
		else 
		{
			$array['Update_3'] = $this->engine->skin['files']->progress_info( $file['file_time_used'], $file['file_dl_done'], $file['file_dl_percent'] );
			$array['Update_4'] = $this->engine->skin['files']->progress_info( $file['file_dl_time'], $file['file_dl_left'], 100 - $file['file_dl_percent'] );
			$array['Update_5'] = "<img src='{$this->engine->base_url}tab=download&progress={$file['file_dl_percent']}' alt='' />";
			$array['Update_6'] = $this->engine->convert_file_size( $file['file_dl_speed'] ).$this->engine->lang['per_sec'];
		}
						
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	//-----------------------------------------------
	
	/**
    * Вывод списка пользователей
    * 
    * Получает список пользователей, добавляет в начало
    * дополнительные параметры и выводит на экран.
    *
    * @return	void
    */
	
	function _get_users_list()
	{
		//-----------------------------------------------
		// Определяем активный элемент
		//-----------------------------------------------
		
		$active = explode( ",", $this->engine->my_getcookie( "list_active" ) );
		
		foreach( $active as $item )
		{
			if( preg_match( "#download=(-?\d+):(\w+)#", $item, $match ) )
			{
				$this->active_user = array( "id" => $match[1], "sub" => $match[2] );
				
				$got_active = 1;
				
				break;
			}
		}
		
		//-----------------------------------------------
		// Определяем скрытые элементы
		//-----------------------------------------------
		
		$hidden_users = array();
		
		$hidden = explode( ":", $this->engine->my_getcookie( "list_hidden" ) );
		
		foreach( $hidden as $item )
		{
			if( preg_match( "#download=((\d+,?)*)#", $item, $match ) and $match[1] != "" )
			{
				$hidden_users = explode( ",", $match[1] );
				
				break;
			}
		}
		
		//-----------------------------------------------
		// Помещаем в начало списка дополнительные пункты
		//-----------------------------------------------
		
		$users[] = array( 'user_name' => &$this->engine->lang['user_all']  , 'user_id' => -1 );
		$users[] = array( 'user_name' => &$this->engine->lang['user_share'], 'user_id' => 0  );
		
		//-----------------------------------------------
		// Получаем список пользователей
		//-----------------------------------------------
		
		if( $this->engine->member['user_admin'] )
		{
			$this->engine->DB->simple_construct( array(	'select'	=> 'user_id, user_name',
														'from'		=> 'users_list'
														)	);
			$this->engine->DB->simple_exec();
		
			while( $user = $this->engine->DB->fetch_row() )
			{
				$users[] = $user;
				
				if( $user['user_id'] == $this->active_user['id'] ) $got_active = TRUE;
			}
		}
		else 
		{
			$users[] = &$this->engine->member;
			
			if( $this->engine->member['user_id'] == $this->active_user['id'] ) $got_active = TRUE;
		}
		
		//-----------------------------------------------
		// Проверяем наличие активного пользователя
		//-----------------------------------------------
		
		if( !$got_active and !$this->active_user['id'] )
		{
			$this->active_user = array( "id" => $this->engine->member['user_id'], "sub" => 'all' );
		}
		
		if( $this->active_user['sub'] != 'all' and in_array( $this->active_user['id'], $hidden_users ) )
		{
			$this->active_user['sub'] = 'all';
		}
		
		$this->engine->classes['output']->java_scripts['embed'][] = "var active_user = '{$this->active_user['id']}';
																	 var active_sub = '{$this->active_user['sub']}';";
		
		//-----------------------------------------------
		// Выводим список пользователей
		//-----------------------------------------------
		
		foreach( $users as $user )
		{
			$active = $user['user_id'] == $this->active_user['id'] ? 1 : 0;
			$hidden = in_array( $user['user_id'], $hidden_users ) ? 1 : 0;
			
			$this->html .= $this->engine->skin['download']->users_list_item( &$user, $user['user_id'] > 0 ? 'single' : 'multi', &$active, &$hidden, $this->active_user['sub'] );
		}
	}
	
	/**
    * Вывод списка закачек
    * 
    * Формирует и выводит список закачек в соответствии с
    * заданным состоянием.
    *
    * @return	void
    */
	
	function _get_downloads_list()
	{
		//-----------------------------------------------
		// Определяем активный столбец и тип сортировки
		//-----------------------------------------------
		
		preg_match( "#tab_download_(user|name|size|left|time|state|priority)=(asc|desc)#", $this->engine->my_getcookie( "sort_params" ), $match );
		
		$match[1] = $match[1] ? $match[1] : "name";
		$match[2] = $match[2] ? ( $match[2] == "asc" ? "desc" : "asc" ) : "asc";
		
		$active['user']	 	= $match[1] == 'user'  	  ? array( 'img' => $this->engine->skin['download']->downloads_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['state'] 	= $match[1] == 'state' 	  ? array( 'img' => $this->engine->skin['download']->downloads_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['priority'] = $match[1] == 'priority' ? array( 'img' => $this->engine->skin['download']->downloads_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['name']	 	= $match[1] == 'name'  	  ? array( 'img' => $this->engine->skin['download']->downloads_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['size']  	= $match[1] == 'size'  	  ? array( 'img' => $this->engine->skin['download']->downloads_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['left']  	= $match[1] == 'left'  	  ? array( 'img' => $this->engine->skin['download']->downloads_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['time'] 	= $match[1] == 'time'  	  ? array( 'img' => $this->engine->skin['download']->downloads_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		
		$this->html .= $this->engine->skin['download']->downloads_list_headers( &$active );
		
		//-----------------------------------------------
		// Условие выборки по идентификатору пользователя
		//-----------------------------------------------
		
		if( $this->engine->input['id'] == -1 )
		{
			if( !$this->engine->member['user_admin'] ) $where[] = "f.file_user='0' OR f.file_user='{$this->engine->member['user_id']}'";
		}
		else if( $this->engine->input['id'] == 0 )
		{
			$where[] = "f.file_shared='1'";
		}
		else 
		{
			$where[] = $this->engine->member['user_admin'] ? "f.file_user='{$this->engine->input['id']}'" : "f.file_user='{$this->engine->member['user_id']}'";
			
			$where[] = "f.file_shared='0'";
		}
		
		//-----------------------------------------------
		// Условие выборки по времени события
		//-----------------------------------------------
		
		switch( $this->engine->input['sub'] )
		{
			case 'running':
				$where[] = "f.file_state='running'";
				break;
				
			case 'paused':
			case 'continue':
				$where[] = "f.file_state='paused'";
				break;
				
			case 'idle':
			case 'schedule':
				$where[] = "f.file_state='idle'";
				break;
				
			case 'query':
				$where[] = "f.file_state='query'";
				break;
				
			case 'stopped':
				$where[] = "f.file_state='stopped' AND f.file_error=0";
				break;
				
			case 'blocked':
				$where[] = "f.file_state='blocked'";
				break;
				
			case 'error':
				$where[] = "f.file_state='stopped' AND f.file_error=1";
				break;
				
			case 'done':
				$where[] = "f.file_state='done'";
				break;
		}
		
		//-----------------------------------------------
		// Получаем параметры расписания
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'event_files',
													'from'		=> "schedule_events",
													'where'		=> "event_state='query'",
													)	);
		$this->engine->DB->simple_exec();
			
		$schedule = array();
			
		while( $event = $this->engine->DB->fetch_row() )
		{
			foreach( explode( ",", $event['event_files'] ) as $file ) if( !in_array( $file, $schedule ) ) $schedule[] = $file;
		}
		
		//-----------------------------------------------
		// Определяем количество страниц
		//-----------------------------------------------
		
		$match[1] = in_array( $match[1], array( 'left', 'time' ) ) ? "dl_".$match[1] : $match[1];
		
		$pages = $this->engine->DB->simple_exec_query( array(	'select'	=> 'COUNT(file_id) as total',
																'from'		=> "categories_files AS f LEFT JOIN users_list AS u ON (u.user_id=f.file_user)",
																'where'		=> is_array( $where ) ? implode( " AND ", &$where ) : ( $this->engine->member['user_admin'] ? "1" : "f.file_user='{$this->engine->member['user_id']}'" ),
																)	);
		
		$this->pages_total = intval( is_numeric( $pages['total'] ) ? ceil( $pages['total'] / 100 ) : 1 );
		
		$this->engine->classes['output']->java_scripts['embed'][] = "pages_total = {$this->pages_total};
																	 pages_st = {$this->engine->input['st']};";
		
		//-----------------------------------------------
		// Получаем список событий
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> "categories_files AS f LEFT JOIN users_list AS u ON (u.user_id=f.file_user)",
													'where'		=> is_array( $where ) ? implode( " AND ", &$where ) : ( $this->engine->member['user_admin'] ? "1" : "f.file_user='{$this->engine->member['user_id']}'" ),
													'order'		=> "file_{$match[1]} {$match[2]}",
													'limit'		=> array( $this->engine->input['st'], 100 ),
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->html .= $this->engine->skin['download']->downloads_list_message( &$this->engine->lang['no_downloads'] );
			
			$i = 1;
		}
		else while( $item = $this->engine->DB->fetch_row() )
		{
			$items[ $item['file_id'] ] = $item;
			
			if( $item['file_state'] == "running" ) $modules[ $item['file_dl_module'] ][ $item['file_id'] ] = $item;
		}
		
		//-----------------------------------------------
		// Обновляем информацию о состоянии
		// закачивающихся файлов
		//-----------------------------------------------
		
		if( is_array( $modules ) )
		{
			//-----------------------------------------------
			// Подгружаем класс для работы с модулями
			//-----------------------------------------------
			
			$this->engine->load_module( "class", "downloader" );
			
			//-----------------------------------------------
			// Для файлов каждого модуля обновляем информацию
			//-----------------------------------------------
			
			foreach( $modules as $mid => $files )
			{
				$this->engine->classes['downloader']->load_module( "", &$mid );
				
				foreach( $files as $fid => $file )
				{
					foreach( $file as $name => $value )
					{
						$clear[ $name ] = $value;
					}
					
					$file =& $clear;
					
					$this->engine->classes['downloader']->update_download_state( $file );
					
					foreach( $this->engine->classes['downloader']->file as $name => $value ) $items[ $file['file_id'] ][ $name ] = $value;
				}
				
				$this->engine->classes['downloader']->unload_module();
			}
		}
		
		//-----------------------------------------------
		// Определяем, какие закачки запланированы
		//-----------------------------------------------		
		
		if( is_array( $items ) ) foreach( $items as $iid => $item )
		{
			if( $this->engine->input['sub'] == 'schedule' and !in_array( $item['file_id'], $schedule ) ) continue;
			if( $this->engine->input['sub'] == 'continue' and !in_array( $item['file_id'], $schedule ) ) continue;
			if( $this->engine->input['sub'] == 'query' and in_array( $item['file_id'], $schedule ) ) continue;
			
			if( $this->engine->input['sub'] != 'schedule' and $item['file_state'] == "idle"   and in_array( $item['file_id'], $schedule ) ) $items[ $iid ]['file_state'] = 'schedule';
			if( $this->engine->input['sub'] != 'schedule' and $item['file_state'] == "paused" and in_array( $item['file_id'], $schedule ) ) $items[ $iid ]['file_state'] = 'continue';
		}
		
		//-----------------------------------------------
		// Делаем сортировку по состоянию и (или) по имени
		//-----------------------------------------------
		
		if( is_array( $items ) and !in_array( $match[1], array( 'name', 'priority' ) ) )
		{
			switch( $match[1] )
			{
				case 'user': $this->sort_by = "f.file_user";
				break;
				
				case 'size': $this->sort_by = "f.file_size";
				break;
				
				case 'left': $this->sort_by = "f.file_dl_left";
				break;
				
				case 'time': $this->sort_by = "f.file_dl_time";
				break;
			}
			
			usort( $items, array( "download", $match[1] == 'state' ? "_sort_by_state" : "_sort_by_name" ) );
			
			if( $match[2] == 'desc' ) $items = array_reverse( $items );
		}
		
		//-----------------------------------------------
		// Выводим список закачек
		//-----------------------------------------------
		
		if( is_array( $items ) ) foreach( $items as $item )
		{
			$row = $row == 5 ? 6 : 5;
			
			if( strlen( $item['file_name'] ) > 44 )
			{
				$item['file_name'] = substr( $item['file_name'], 0, 22 )."...".substr( $item['file_name'], -22 );
			}
			
			if( !$this->engine->config['shared_view_owner'] and $item['file_shared'] and $item['file_user'] != $this->engine->member['user_id'] and !$this->engine->member['user_admin'] )
			{
				$item['user_name'] = "--";
			}
			
			switch( $item['file_priority'] )
			{
				case 0:
					$item['file_priority'] = "low";
					break;
					
				case 1:
					$item['file_priority'] = "med";
					break;
					
				case 2:
					$item['file_priority'] = "high";
					break;
			}
			
			$item['file_size']  	= $this->engine->convert_file_size( &$item['file_size'] );
			$item['file_dl_left']	= ( $item['file_dl_left'] and $item['file_state'] != 'done' ) ? $this->engine->convert_file_size( &$item['file_dl_left'] ) : "--" ;
			$item['file_dl_time']	= ( $item['file_dl_time'] and $item['file_state'] != 'done' ) ? $this->engine->convert_time_measure( &$item['file_dl_time'] ) : "--";
			$item['file_state'] 	= $this->engine->skin['download']->item_state( $item['file_state'] );
			$item['file_priority'] 	= $this->engine->skin['download']->item_priority( $item['file_priority'] );
			
			$this->html .= $this->engine->skin['download']->downloads_item_row( &$item, $row );
			
			$i++;
		}
		
		if( !$i ) $this->html .= $this->engine->skin['download']->downloads_list_message( &$this->engine->lang['no_downloads'] );
		
		$this->html .= $this->engine->skin['download']->downloads_list_footer();
	}
	
	//-----------------------------------------------
	
	/**
    * Сортировка закачек по состоянию
    * 
    * Сортирует закачки в зависимости от их текущего
    * состояния.
    * 
    * @param 	array			Параметры первой закачки
    * @param 	array			Параметры второй закачки
    *
    * @return	int				Результат сравнения
    */
	
	function _sort_by_state( $a, $b )
	{
		if( $a['file_state'] == $b['file_state'] ) return strcasecmp( $a['file_name'], $b['file_name'] );
		
		if( $a['file_state'] == 'running' ) return -1;
		if( $b['file_state'] == 'running' ) return 1;
		
		if( $a['file_state'] == 'paused' ) return -1;
		if( $b['file_state'] == 'paused' ) return 1;
		
		if( $a['file_state'] == 'idle' ) return -1;
		if( $b['file_state'] == 'idle' ) return 1;
		
		if( $a['file_state'] == 'query' ) return -1;
		if( $b['file_state'] == 'query' ) return 1;
		
		if( $a['file_state'] == 'schedule' ) return -1;
		if( $b['file_state'] == 'schedule' ) return 1;
		
		if( $a['file_state'] == 'continue' ) return -1;
		if( $b['file_state'] == 'continue' ) return 1;
		
		if( $a['file_state'] == 'stopped' ) return -1;
		if( $b['file_state'] == 'stopped' ) return 1;
		
		if( $a['file_state'] == 'blocked' ) return -1;
		if( $b['file_state'] == 'blocked' ) return 1;
		
		if( $a['file_state'] == 'error' ) return -1;
		if( $b['file_state'] == 'error' ) return 1;
		
		if( $a['file_state'] == 'done' ) return -1;
		if( $b['file_state'] == 'done' ) return 1;
	}

	/**
    * Сортировка закачек по имени файла
    * 
    * Сортирует закачки в зависимости от имени 
    * закачиваемого файла.
    * 
    * @param 	array			Параметры первой закачки
    * @param 	array			Параметры второй закачки
    *
    * @return	int				Результат сравнения
    */
	
	function _sort_by_name( $a, $b )
	{
		if( $a[ $this->sort_by ] == $b[ $this->sort_by ] ) return strcasecmp( $a['file_name'], $b['file_name'] );
		
		return 0;
	}
	
	/**
	* Создание изображения
	* 
	* Генерирует изображение - индикатор прогресса
	* закачки и выводит его на экран.
	* 
	* @return 	void
	*/
	
	function _show_progress_bar()
	{
		header( "Content-type: image/gif" );
		
		$percent = 300 / 100;
		
		$total = ceil( intval( $this->engine->input['progress'] ) * $percent );
		
		$progress = floor( $this->engine->input['progress'] );
		
		if( $progress > 33 ) $total++;
		if( $progress > 66 ) $total++;
		if( $progress > 99 ) $total++;
		
		$img = imagecreate( 302, 16 );
		
		imagecolorallocate( $img, 255, 255, 255 );
		
		$green = imagecolorallocate( $img, 118, 177, 39 );
		
		if( $this->engine->input['progress'] ) imagefilledrectangle( $img, 0, 0, $total, 17, $green );
		
		exit( imagegif( $img ) );
	}

}

?>