<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Страница управления настройками
*/

/**
* Класс, содержащий функции для
* страницы управления настройками.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class settings
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
	* Конструктор класса (не страндартный PHP)
	* 
	* Загружает языковые строки и шаблон.
	* В зависимости от переданных параметров запускает
	* необходимую функцию.
	* 
	* @return	bool	TRUE
	*/
	
	function __class_construct()
	{
		$this->engine->load_lang( "settings" );
		$this->engine->load_skin( "settings" );
		
		//-----------------------------------------------
		// AJAX запрос
		//-----------------------------------------------
			
		if( $this->engine->input['ajax'] == 'yes' ) switch( $this->engine->input['type'] )
		{
			case 'settings_apply':
				$this->ajax_apply_settings();
				break;
				
			case 'view_auth_list':
				$this->ajax_view_auth_list();
				break;
				
			case 'update_auth_list':
				$this->ajax_update_auth_list();
				break;
				
			case 'delete_auth':
				$this->ajax_delete_auth();
				break;
				
			case 'view_lang_list':
				$this->ajax_view_lang_list();
				break;
				
			case 'update_lang_list':
				$this->ajax_update_lang_list();
				break;
				
			case 'delete_lang':
				$this->ajax_delete_lang();
				break;
				
			case 'about_ados':
				$this->ajax_about_ados();
				break;
				
			case 'check_update':
				$this->ajax_check_update();
				break;
				
			case 'delete_lock_file':
				$this->ajax_delete_lock_file();
				break;
		}
			
		//-----------------------------------------------
		// Обычный запрос
		//-----------------------------------------------
			
		$this->engine->classes['output']->java_scripts['link'][] = "settings";
			
		$this->show_settings();
		
		return TRUE;
	}
	
	/**
    * Список настроек
    * 
    * Выводит список разрешенных для изменения
    * настроек.
    *
    * @return	void
    */
	
	function show_settings()
	{
		$this->engine->classes['output']->java_scripts['embed'][] = "var lang_auth_click_to_edit = '{$this->engine->lang['auth_click_to_edit']}';
																	 var lang_list_auth_delete = '{$this->engine->lang['list_auth_delete']}';
																	 var lang_list_lang_delete = '{$this->engine->lang['list_lang_delete']}';
																	 var lang_error_auth_not_correct = '{$this->engine->lang['error_auth_not_correct']}';
																	 var lang_error_lang_not_correct = '{$this->engine->lang['error_lang_not_correct']}';";
		
		//-----------------------------------------------
		// Название страницы и системное сообщение
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['page_title'];
		$this->page_info['desc']	= $this->engine->lang['page_desc'];
		
		$this->message = array(	'text'	=> "",
								'type'	=> "",
								);
		
		//-----------------------------------------------
		// Форма со списком настроек
		//-----------------------------------------------
		
		$this->html .= $this->engine->classes['output']->form_start( array(	'tab'	=> 'settings',
																			'save'	=> 'settings',
																			), "id='settings_form' onsubmit='ajax_apply_settings(); return false;'" );
		
		$this->html .= "<div id='list'>\n";
																			
		$this->_get_settings();
		
		$this->html .= "</div>\n";
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
	
	/**
    * Сохранение настроек
    * 
    * В зависимости от указанного параметра вызывает
    * функцию сохранения настроек или загрузки
    * умолчаний.
    *
    * @return	void
    */
	
	function ajax_apply_settings()
	{
		//-----------------------------------------------
		// Обновляем настройки
		//-----------------------------------------------
		
		if( $this->engine->input['type'] == 'settings_apply' )
		{
			if( $this->engine->input['save'] == 'settings' )
			{
				$this->_apply_settings();
			
				$array['Message'] =& $this->engine->lang['settings_applied'];
			}
			else 
			{
				$this->_apply_defaults();
			
				$array['Message'] =& $this->engine->lang['defaults_applied'];
			}
		}
		
		//-----------------------------------------------
		// Обновляем список настроек и возвращаем XML
		//-----------------------------------------------
		
		$this->_get_settings();
		
		$array['List'] =& $this->html;
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Вывод списка авторизации
    * 
    * Формирует форму с параметрами авторизации
    * для имеющихся доменов.
    *
    * @return	void
    */
	
	function ajax_view_auth_list()
	{
		//-----------------------------------------------
		// Получаем список доменов
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'domains_list',
													'order'		=> 'domain_id',
													)	);
		$this->engine->DB->simple_exec();
		
		//-----------------------------------------------
		// Создаем форму со списком доменов
		//-----------------------------------------------
		
		$form = $this->engine->classes['output']->form_start( array(	'tab'	=> 'settings',
																		), "id='auth_list' onsubmit='ajax_update_list_auth(); return false;'" );
		
		$this->engine->classes['output']->table_add_header( $this->engine->lang['auth_domain']	, "30%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['auth_login']	, "25%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['auth_pass']	, "30%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['auth_share']	, "10%" );
		$this->engine->classes['output']->table_add_header( ""									, "15%" );
		
		$form .= $this->engine->classes['output']->table_start( "", "100%", "id='ajax_table'", "", "style='border:0'" );
		
		if( $this->engine->DB->get_num_rows() ) while( $domain = $this->engine->DB->fetch_row() )
		{
			if( $domain['domain_pass'] )
			{
				$input = array(	'value' => $this->engine->lang['auth_click_to_edit'],
								'type'	=> 'text',
								'misc'	=> "onfocus='ajax_change_type(this,true)' onblur='ajax_change_type(this,false)'"
								);
			}
			else
			{
				$input = array(	'type' 		=> "password",
								'disabled'	=> "disabled='disabled'"
								);
			}
			
			$form .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->skin['global']->form_text( "domain_{$domain['domain_id']}_name", $domain['domain_name'], "small", "text", "style='width:164px;'" 										), "row1"   ),
								array(	$this->engine->skin['global']->form_text( "domain_{$domain['domain_id']}_user", $domain['domain_user'], "small", "text", "style='width:133px;'" 										), "row2"   ),
								array(	$this->engine->skin['global']->form_checkbox( "domain_{$domain['domain_id']}_use_pass", $domain['domain_pass'] ? 1 : 0, "", "onclick=\"ajax_toggle_pass_state('{$domain['domain_id']}',this)\"" 	).
										$this->engine->skin['global']->form_text( "domain_{$domain['domain_id']}_pass", $input['value'], "small", $input['type'], "style='width:133px;' {$input['misc']} {$input['disabled']}"	), "row2"	),
								array(	$this->engine->skin['global']->form_checkbox( "domain_{$domain['domain_id']}_share", $domain['domain_share']												), "row1", "style='text-align:center;'" ),
								array(	$this->engine->skin['global']->element_button( $domain['domain_id'], "list_auth", "delete" 																	), "row1", "style='text-align:center;'" ),
								)	);
								
			$last_id = $domain['domain_id'];
		}
		
		$form .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->skin['global']->form_text( "domain_".( $last_id + 1 )."_name", "", "small", "text", "style='width:164px;' onblur='ajax_add_list_auth_row(this,".( $last_id + 2 ).");'"	), "row1" ),
								array(	$this->engine->skin['global']->form_text( "domain_".( $last_id + 1 )."_user", "", "small", "text", "style='width:133px;'" 														 		), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "domain_".( $last_id + 1 )."_use_pass", 0, "", "onclick=\"ajax_toggle_pass_state('".( $last_id + 1 )."',this)\"" ).
										$this->engine->skin['global']->form_text( "domain_".( $last_id + 1 )."_pass", "", "small", "password", "style='width:133px;' disabled='disabled'" 								 		), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "domain_".( $last_id + 1 )."_share", 0										  					   		), "row1", "style='text-align:center;'"   ),
								array(	$this->engine->skin['global']->element_button( 0, "list_auth", "delete" 																				), "row1", "style='text-align:center;'"   ),
								)	);
		
		$form .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['auth_apply'] );
		
		$form .= $this->engine->classes['output']->table_end();
		
		$form .= $this->engine->classes['output']->form_end();
		
		//-----------------------------------------------
		// Обновляем список настроек и возвращаем XML
		//-----------------------------------------------
		
		if( $this->engine->input['type'] == 'update_auth_list' )
		{
			$array['Message'] = $this->engine->lang['auth_list_updated'];
		}
		
		$array['HTML'] = $this->engine->skin['global']->ajax_window( $this->engine->lang['domain_auth_params'], &$form );
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Обновление списка авторизации
    * 
    * Обновляет список авторизации в соответствии с
    * переданными значениями.
    *
    * @return	void
    */
	
	function ajax_update_auth_list()
	{
		//-----------------------------------------------
		// Получаем список доменов
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'domain_id, domain_pass',
													'from'		=> 'domains_list',
													'order'		=> 'domain_id',
													)	);
		$this->engine->DB->simple_exec();
		
		if( $this->engine->DB->get_num_rows() ) while( $domain = $this->engine->DB->fetch_row() )
		{
			$domains['passwords'][ $domain['domain_id'] ] = $domain['domain_pass'];
		}
		else 
		{
			$domains['passwords'] = array();
		}
		
		//-----------------------------------------------
		// Проверяем переданные значения
		//-----------------------------------------------
		
		foreach( $this->engine->input as $name => $value ) if( preg_match( "#domain_(\d+)#", $name, $match ) )
		{
			$domain = explode( ",", $value, 4 );
			
			$domain[3] = $domain[3] == "[********]" ? $domains['passwords'][ $match[1] ] : $domain[3];
			
			if( !$domain[0] or !$domain[1] )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_auth_not_correct'] ) );
			}
			
			if( !preg_match( "#(http://|ftp://)([\*\w-]\.?)+#", $domain[0] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_auth_name_incorrect'].$domain[0] ) );
			}
			
			if( !preg_match( "#\w#", $domain[1] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_auth_user_incorrect'].$domain[0] ) );
			}
			
			if( $domain[3] and !preg_match( "#\w#", $domain[3] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_auth_pass_incorrect'].$domain[0] ) );
			}
			
			$info = array(	'domain_id'		=> $match[1],
							'domain_name'	=> $domain[0],
							'domain_user'	=> $domain[1],
							'domain_pass'	=> $domain[3],
							'domain_share'	=> $domain[2] ? 1 : 0,
							);
			
			array_key_exists( $match[1], $domains['passwords'] ) ? $domains['updated'][] = $info : $domains['new'][] = $info;
		}
		
		//-----------------------------------------------
		// Сохраняем список
		//-----------------------------------------------
		
		if( count( $domains['updated'] ) ) foreach( $domains['updated'] as $domain )
		{
			$this->engine->DB->do_update( "domains_list", &$domain, "domain_id='{$domain['domain_id']}'" );
		}
		
		if( count( $domains['new'] ) ) foreach( $domains['new'] as $domain )
		{
			$this->engine->DB->do_insert( "domains_list", &$domain );
		}
		
		//-----------------------------------------------
		// Обновляем список доменов и возвращаем XML
		//-----------------------------------------------
		
		$this->ajax_view_auth_list();
	}
	
	/**
    * Удаление домена
    * 
    * Удаляет информацию о домене из БД.
    *
    * @return	void
    */
	
	function ajax_delete_auth()
	{
		//-----------------------------------------------
		// Получаем информацию о домене
		//-----------------------------------------------
		
		$domain = $this->engine->DB->simple_exec_query( array(	'select'	=> 'domain_id',
																'from'		=> 'domains_list',
																'where'		=> "domain_id='{$this->engine->input['id']}'"
																)	);
		
		if( !$domain['domain_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_domain_id'] ) );
		}
		
		//-----------------------------------------------
		// Удаляем информацию из БД
		//-----------------------------------------------
		
		$this->engine->DB->do_delete( "domains_list", "domain_id='{$this->engine->input['id']}'" );
		
		//-----------------------------------------------
		// Обновляем список доменов и возвращаем XML
		//-----------------------------------------------
		
		$this->ajax_view_auth_list();
	}
	
	/**
    * Вывод списка языков системы
    * 
    * Формирует форму с параметрами установленных
    * и используемых языков интерфейса системы.
    *
    * @return	void
    */
	
	function ajax_view_lang_list()
	{
		//-----------------------------------------------
		// Получаем список языков
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'languages',
													'order'		=> 'lang_id',
													)	);
		$this->engine->DB->simple_exec();
		
		//-----------------------------------------------
		// Создаем форму со списком языков
		//-----------------------------------------------
		
		$form = $this->engine->classes['output']->form_start( array(	'tab'	=> 'settings',
																		), "id='lang_list' onsubmit='ajax_update_list_lang(); return false;'" );
		
		$this->engine->classes['output']->table_add_header( $this->engine->lang['lang_name']	, "25%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['lang_key']		, "15%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['lang_default']	, "15%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['lang_authors']	, "25%" );
		$this->engine->classes['output']->table_add_header( ""									, "15%" );
		
		$form .= $this->engine->classes['output']->table_start( "", "100%", "id='ajax_table'", "", "style='border:0'" );
		
		if( $this->engine->DB->get_num_rows() ) while( $lang = $this->engine->DB->fetch_row() )
		{
			$authors['names'] = $lang['lang_authors'] ? unserialize( stripslashes( $lang['lang_authors'] ) ) : array();
			$authors['links'] = $lang['lang_links'] ? unserialize( stripslashes( $lang['lang_links'] ) ) : array();
			$authors['list']  = array();
			
			if( count( $authors['names'] ) )
			{
				foreach( $authors['names'] as $nid => $name )
				{
					$authors['list'][] = $authors['links'][ $nid ] ? "<a href='{$authors['links'][ $nid ]}'>{$name}</a>" : $name;
				}
				
				$authors['string'] = implode( ", ", $authors['list'] );
			}
			else
			{
				$authors['string'] = "&nbsp;";
			}
			
			$form .= $this->engine->classes['output']->table_add_row( array( 
								array( $this->engine->skin['global']->form_text( "lang_{$lang['lang_id']}_name", $lang['lang_name'], "small", "text", "style='width:145px;'"	), "row1"	),
								array( $this->engine->skin['global']->form_text( "lang_{$lang['lang_id']}_key", $lang['lang_key'], "small", "text", "style='width:80px;'" 		), "row2"	),
								array( $this->engine->skin['global']->form_radio( "lang_default", $lang['lang_id'], $lang['lang_default'] 			), "row2", "style='text-align:center;'"	),
								array( $authors['string']																														 , "row2"	),
								array( $this->engine->skin['global']->element_button( $lang['lang_id'], "list_lang", "delete" 						), "row1", "style='text-align:center;'"	),
								)	);
								
			$last_id = $lang['lang_id'];
		}
		
		$form .= $this->engine->classes['output']->table_add_row( array( 
								array( $this->engine->skin['global']->form_text( "lang_".( $last_id + 1 )."_name", "", "small", "text", "style='width:145px;' onblur='ajax_add_list_lang_row(this,".( $last_id + 2 ).");'" 	), "row1"   ),
								array( $this->engine->skin['global']->form_text( "lang_".( $last_id + 1 )."_key", "", "small", "text", "style='width:80px;'" 														 		), "row2"   ),
								array( $this->engine->skin['global']->form_radio( "lang_default", ( $last_id + 1 ), 0																			), "row2", "style='text-align:center;'" ),
								array( "&nbsp;"																																												 , "row2"   ),
								array( $this->engine->skin['global']->element_button( 0, "list_lang", "delete" 																					), "row1", "style='text-align:center;'" ),
								)	);
		
		$form .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['lang_apply'] );
		
		$form .= $this->engine->classes['output']->table_end();
		
		$form .= $this->engine->classes['output']->form_end();
		
		//-----------------------------------------------
		// Обновляем список настроек и возвращаем XML
		//-----------------------------------------------
		
		if( $this->engine->input['type'] == 'update_lang_list' )
		{
			$array['Message'] = $this->engine->lang['lang_list_updated'];
			
			$array['Update_18'] = $this->engine->classes['output']->get_lang_menu();
		}
		else if( $this->engine->input['type'] == 'delete_lang' )
		{
			$array['Update_18'] = $this->engine->classes['output']->get_lang_menu();
			
			if( !array_key_exists( $this->engine->member['user_lang'], &$this->engine->languages['list'] ) )
			{
				$array['Function_0'] = "window.location.reload()";
			}
		}
		
		$array['HTML'] = $this->engine->skin['global']->ajax_window( $this->engine->lang['system_lang_list'], &$form );
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Обновление списка языков
    * 
    * Обновляет список языков системы в соответствии с
    * переданными значениями.
    *
    * @return	void
    */
	
	function ajax_update_lang_list()
	{
		//-----------------------------------------------
		// Получаем список языков
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'lang_id',
													'from'		=> 'languages',
													)	);
		$this->engine->DB->simple_exec();
		
		if( $this->engine->DB->get_num_rows() ) while( $lang = $this->engine->DB->fetch_row() )
		{
			$languages['list'][] = $lang['lang_id'];
		}
		else 
		{
			$languages['list'] = array();
		}
		
		//-----------------------------------------------
		// Проверяем переданные значения
		//-----------------------------------------------
		
		foreach( $this->engine->input as $name => $value ) if( preg_match( "#lang_(\d+)#", $name, $match ) )
		{
			$lang = explode( ",", $value, 2 );
			
			if( !$lang[0] or !$lang[1] )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_lang_not_correct'] ) );
			}
			
			if( !preg_match( "#^[a-zA-Z]{2}$#", $lang[1] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_lang_key_incorrect'].$lang[0] ) );
			}
			
			$dir = $this->engine->home_dir."languages/{$lang[1]}";
			
			if( !is_dir( $dir ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_lang_no_dir'].$lang[0] ) );
			}
			
			if( !is_readable( $dir."/categories.lng" )	or
				!is_readable( $dir."/download.lng" 	 )	or 
				!is_readable( $dir."/files.lng" 	 )	or
				!is_readable( $dir."/global.lng"	 )	or
				!is_readable( $dir."/log.lng"	 	 )	or
				!is_readable( $dir."/modules.lng"	 )	or
				!is_readable( $dir."/schedule.lng"	 )	or
				!is_readable( $dir."/settings.lng"	 )	or
				!is_readable( $dir."/upload.lng"	 )	or
				!is_readable( $dir."/users.lng"		 )	)
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_lang_no_files'].$lang[0] ) );
			}
			
			$strings = file( $dir."/global.lng" );
			
			$authors = array(	'names'	=> array(),
								'links'	=> array(),
								);
								
			foreach( $strings as $str )
			{
				if( preg_match( "#^\s*\*\s*@(translator|tr_link|tr_email)\s+(.+)$#i", $str, $values ) )
				{
					switch( $values[1] )
					{
						case 'translator': $authors['names'][] = $values[2];
						break;
						
						case 'tr_link': $authors['links'][] = $values[2];
						break;
						
						case 'tr_email': $authors['links'][] = "mailto:".$values[2];
						break;
					}
				}
			}
			
			$info = array(	'lang_id'		=> $match[1],
							'lang_name'		=> $lang[0],
							'lang_default'	=> $this->engine->input['lang_default'] == $match[1] ? 1 : 0,
							'lang_key'		=> strtolower( $lang[1] ),
							'lang_authors'	=> count( $authors['names'] ) ? serialize( $authors['names'] ) : "",
							'lang_links'	=> count( $authors['links'] ) ? serialize( $authors['links'] ) : "",
							);
			
			in_array( $info['lang_id'], &$languages['list'] ) ? $languages['updated'][] = $info : $languages['new'][] = $info;
		}
		
		//-----------------------------------------------
		// Сохраняем список
		//-----------------------------------------------
		
		if( count( $languages['updated'] ) ) foreach( $languages['updated'] as $lang )
		{
			$this->engine->DB->do_update( "languages", &$lang, "lang_id='{$lang['lang_id']}'" );
		}
		
		if( count( $languages['new'] ) ) foreach( $languages['new'] as $lang )
		{
			$this->engine->DB->do_insert( "languages", &$lang );
		}
		
		//-----------------------------------------------
		// Обновляем список языков и возвращаем XML
		//-----------------------------------------------
		
		$this->engine->load_system_languages();
		
		$this->ajax_view_lang_list();
	}
	
	/**
    * Удаление языка
    * 
    * Удаляет информацию о языке системы из БД.
    *
    * @return	void
    */
	
	function ajax_delete_lang()
	{
		//-----------------------------------------------
		// Получаем информацию о домене
		//-----------------------------------------------
		
		$lang = $this->engine->DB->simple_exec_query( array(	'select'	=> 'lang_id, lang_key',
																'from'		=> 'languages',
																'where'		=> "lang_id='{$this->engine->input['id']}'"
																)	);
		
		if( !$lang['lang_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_lang_id'] ) );
		}
		else if( $lang['lang_id'] == 1 )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['js_error_lang_is_basic'] ) );
		}
		
		//-----------------------------------------------
		// Удаляем информацию из БД
		//-----------------------------------------------
		
		$this->engine->DB->do_delete( "languages", "lang_id='{$this->engine->input['id']}'" );
		
		//-----------------------------------------------
		// Обновляем список языков системы
		//-----------------------------------------------
		
		$this->engine->load_system_languages();
		
		//-----------------------------------------------
		// Обновляем языки пользователей
		//-----------------------------------------------
		
		$this->engine->DB->do_update( "users_list", array( "user_lang" => &$this->engine->languages['default'] ), "user_lang='{$lang['lang_key']}'" );
		
		//-----------------------------------------------
		// Обновляем список доменов и возвращаем XML
		//-----------------------------------------------
		
		$this->ajax_view_lang_list();
	}
	
	/**
    * О программе
    * 
    * Выводит информацию о системе, а также ссылки для
    * перевода пожертвований на счет автора.
    *
    * @return	void
    */
	
	function ajax_about_ados()
	{
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$table  = $this->engine->classes['output']->table_start( "", "100%", "", "", "id='ajax_table' style='border:0'" );
		
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_ados_version']			, "row1" ),
								array(	$this->engine->skin['settings']->ados_version()		, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_ados_update']				, "row1" ),
								array(	$this->engine->skin['settings']->ados_update()		, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_ados_copyright']			, "row1" ),
								array(	$this->engine->skin['settings']->ados_copyright()	, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_ados_eula']				, "row1" ),
								array(	$this->engine->skin['settings']->ados_eula()		, "row2" ),
								)	);
														
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_ados_contact']			, "row1" ),
								array(	$this->engine->skin['settings']->ados_contact()		, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['info_ados_donate']				, "row1" ),
								array(	$this->engine->skin['settings']->ados_donate()		, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_end();
								
		$table .= $this->engine->skin['settings']->ados_window_bottom();
		
		//-----------------------------------------------
		// Формируем и возвращаем XML
		//-----------------------------------------------
		
		$array['HTML'] = $this->engine->skin['global']->ajax_window( $this->engine->lang['about_ados'], &$table );
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Проверка наличия обновлений
    * 
    * Отсылает запрос на сайт ados.dini.su и выводит
    * полученный XML-ответ.
    *
    * @return	void
    */
	
	function ajax_check_update()
	{
		//-----------------------------------------------
		// Возвращаем XML
		//-----------------------------------------------
		
		header('Content-Type: text/xml');
		
		exit( $this->engine->classes['output']->check_for_updates( TRUE ) );
	}
	
	/**
    * Удаление блокировочного файла
    * 
    * Пытается удалить указанный блокировочный файл в коревой
    * директории системы.
    *
    * @return	void
    */
	
	function ajax_delete_lock_file()
	{
		//-----------------------------------------------
		// Проверяем идентификатор
		//-----------------------------------------------
		
		if( !in_array( $this->engine->input['id'], array( "cron", "task" ) ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_wrong_lock_id'] ) );
		}
		
		//-----------------------------------------------
		// Проверяем наличие файла
		//-----------------------------------------------
		
		$file_path = $this->engine->home_dir.$this->engine->input['id'].".lock";
		
		if( !file_exists( $file_path ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_lock_file'] ) );
		}
		
		//-----------------------------------------------
		// Удаляем файл
		//-----------------------------------------------
		
		if( !@unlink( $file_path ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_cant_unlink_lock_file'] ) );
		}
		
		$array = array( 'Message'		=> $this->engine->lang['lock_file_deleted'],
						'Function_0'	=> "my_getbyid('warn_{$this->engine->input['id']}_lock').style.display='none';",
						);
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Обновление настроек
    * 
    * Обновляет настройки системы в соответствии
    * с переданными значениями.
    *
    * @return	void
    */
	
	function _apply_settings()
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
													'from'		=> 'settings_list',
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
			if( preg_match( "#before-save:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting, $ids[ $setting['setting_id'] ] );
			}
				
			if( $setting['setting_value'] != $ids[ $setting['setting_id'] ] and preg_match( "#before-update:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting, $ids[ $setting['setting_id'] ] );
			}
			
			if( preg_match( "#parse-value:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$parsed_value = $this->engine->call_service_function( &$match, $setting, $ids[ $setting['setting_id'] ] );
				
				$ids[ $setting['setting_id'] ] = $parsed_value;
			}
				
			$this->engine->DB->do_update( 'settings_list', array( 'setting_value' => $ids[ $setting['setting_id'] ] ), "setting_id={$setting['setting_id']}" );
			
			if( preg_match( "#after-save:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting, $ids[ $setting['setting_id'] ] );
			}
			
			if( preg_match( "#after-save-all:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$call_later[] = array( $match, $setting, $ids[ $setting['setting_id'] ] );
			}
			
			if( $setting['setting_value'] != $ids[ $setting['setting_id'] ] and preg_match( "#after-update:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting, $ids[ $setting['setting_id'] ] );
			}
			
			if( $setting['setting_value'] != $ids[ $setting['setting_id'] ] and preg_match( "#after-update-all:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$call_later[] = array( $match, $setting, $ids[ $setting['setting_id'] ] );
			}
		}
		
		//-----------------------------------------------
		// Запускаем на выполнение отложенные функции
		//-----------------------------------------------
		
		if( is_array( $call_later ) )
		{
			$this->engine->get_settings();
			
			foreach( $call_later as $values ) call_user_func_array( array( &$this->engine, "call_service_function" ), &$values );
		}
	}
	
	/**
    * Восстановление умолчаний
    * 
    * Обновляет настройки системы, присваивая
    * им значения по умолчанию.
    *
    * @return	void
    */
	
	function _apply_defaults()
	{
		//-----------------------------------------------
		// Получаем текущие настройки
		//-----------------------------------------------
			
		$this->engine->DB->simple_construct( array(	'select'	=> 'setting_id, setting_default, setting_value, setting_actions',
													'from'		=> 'settings_list',
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
			
			if( $setting['setting_value'] != $setting['setting_default'] and preg_match( "#before-update:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting, $setting['setting_default'] );
			}
				
			$this->engine->DB->do_update( 'settings_list', array( 'setting_value' => NULL ), "setting_id={$setting['setting_id']}" );
				
			if( preg_match( "#after-default:\s*(\w+)\(\s*(([value|default|current|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting );
			}
			
			if( $setting['setting_value'] != $setting['setting_default'] and preg_match( "#after-update:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$this->engine->call_service_function( &$match, $setting, $setting['setting_default'] );
			}
			
			if( $setting['setting_value'] != $setting['setting_default'] and preg_match( "#after-update-all:\s*(\w+)\(\s*(([value|default|current|new|\d+|'.*']?(,\s*)?)*)\s*\)#i", $setting['setting_actions'], $match ) )
			{
				$call_later[] = array( $match, $setting, $setting['setting_default'] );
			}
		}
		
		//-----------------------------------------------
		// Запускаем на выполнение отложенные функции
		//-----------------------------------------------
		
		if( is_array( $call_later ) )
		{
			$this->engine->get_settings();
			
			foreach( $call_later as $values ) call_user_func_array( array( &$this->engine, "call_service_function" ), &$values );
		}
	}
	
	/**
    * Загрузка настроек
    * 
    * Загружает настройки системы и помещает их в
    * таблицу
    *
    * @return	void
    */
	
	function _get_settings()
	{
		//-----------------------------------------------
		// Загружаем настройки
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'l.*, g.group_key',
													'from'		=> 'settings_list l, settings_groups g',
													'where'		=> "g.group_id=l.setting_group AND ( l.setting_db='' OR l.setting_db IS NULL OR l.setting_db='".DB_ENGINE."' )",
													'order'		=> 'l.setting_group, l.setting_position'
													)	);
		$this->engine->DB->simple_exec();
		
		while( $setting = $this->engine->DB->fetch_row() )
		{
			$groups[ $setting['group_key'] ][] = $setting;
		}
		
		//-----------------------------------------------
		// Выводим меню настроек
		//-----------------------------------------------
		
		$active_tab = $this->engine->my_getcookie( "settings_tab" );
		
		$active_tab = in_array( $active_tab, array_keys( $groups ) ) ? $active_tab : "main";
		
		$tabs = array(	'main'			=> $active_tab == 'main' 		 ? "active" : "inactive",
						'download'		=> $active_tab == 'download' 	 ? "active" : "inactive",
						'categories'	=> $active_tab == 'categories' 	 ? "active" : "inactive",
						'shared_files'	=> $active_tab == 'shared_files' ? "active" : "inactive",
						'paths_change'	=> $active_tab == 'paths_change' ? "active" : "inactive",
						'schedule'		=> $active_tab == 'schedule' 	 ? "active" : "inactive",
						'log'			=> $active_tab == 'log' 		 ? "active" : "inactive",
						'misc'			=> $active_tab == 'misc' 		 ? "active" : "inactive",
						);
						
		$this->html .= $this->engine->skin['global']->div_start( "tabbed_menu", "tabbed_menu" );
						
		foreach( $tabs as $tab => $state )
		{
			$this->html .= $this->engine->skin['settings']->menu_tab( $tab, $state );
		}
		
		$this->html .= $this->engine->skin['global']->div_end();
		
		//-----------------------------------------------
		// Помещаем настройки в таблицы
		//-----------------------------------------------
		
		foreach( $groups as $group => $settings )
		{
			$display = $active_tab == $group ? "" : "style='display:none'";
			
			$this->engine->classes['output']->table_add_header( "", "45%" );
			$this->engine->classes['output']->table_add_header( "", "55%" );
			
			$this->html .= $this->engine->classes['output']->table_start( "", "100%", "", "", "id='group_{$group}' {$display}" );
		
			foreach( $settings as $setting )
			{
				$this->html .= $this->engine->classes['output']->table_add_row( array( 
									array(	$this->engine->lang['setting_'.$setting['setting_key'] ]		, "row1" ),
									array(	$this->engine->classes['output']->parse_setting( &$setting )	, "row2" ),
									)	);
			}
			
			$this->html .= $this->engine->classes['output']->table_end();
		}
		
		//-----------------------------------------------
		// Выводим ссылку на списки установленных языков
		// системы и парамтеров авторизации
		//-----------------------------------------------
		
		$this->engine->classes['output']->table_add_header( "", "45%" );
		$this->engine->classes['output']->table_add_header( "", "55%" );
		
		$display = $active_tab == "misc" ? "" : "style='display:none'";
			
		$this->html .= $this->engine->classes['output']->table_start( "", "100%", "", "", "id='group_misc' {$display}" );
		
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['setting_misc_lang']					, "row1" ),
								array(	$this->engine->skin['settings']->misc_list_link( 'lang' )	, "row2" ),
								)	);
		
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['setting_misc_auth']					, "row1" ),
								array(	$this->engine->skin['settings']->misc_list_link( 'auth' )	, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_end();
		
		//-----------------------------------------------
		// Добавляем кнопки
		//-----------------------------------------------
		
		$this->html .= $this->engine->classes['output']->table_start( "", "100%", "", "", "id='settings_submit'" );
		
		$this->html .= $this->engine->classes['output']->table_add_submit_multi( array(	array( "submit", $this->engine->lang['apply_settings'], "", "onmousedown=\"my_getbyid('hidden_save').value='settings';\"" ),
																						array( "submit", $this->engine->lang['apply_defaults'], "", "onmousedown=\"my_getbyid('hidden_save').value='defaults';\"" )
																						)	);
		
		$this->html .= $this->engine->classes['output']->table_end();
	}

}

?>