<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Страница управления категориями
*/

/**
* Класс, содержащий функции для
* страницы управления категориями.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class categories
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
	* Список категорий
	*
	* @var array
	*/
	
	var $categories		= array(	'unsorted'		=> array(),
									'sorted'		=> array(),
									'hidden'		=> array(),
									'delete'		=> array(),
									'active'		=> "",
									'users_hidden'	=> array(),
									);
									
	/**
	* Список пользователей
	*
	* @var array
	*/
	
	var $users			= array();
	
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
		$this->engine->load_lang( "categories" );
		$this->engine->load_skin( "categories" );
		
		$this->engine->classes['output']->java_scripts['link'][] = "files";
		
		$this->engine->classes['output']->java_scripts['footer'][] = "setInterval( ajax_check_position, 100 )";
		
		//-----------------------------------------------------------
		// Получаем идентификаторы скрытых категорий и
		// идентификатор активной категории
		//-----------------------------------------------------------
		
		$this->engine->classes['output']->java_scripts['link'][] = "categories";
		
		$hidden_cats = $this->engine->my_getcookie( "hidden_cats" );
		
		$this->categories['hidden'] = explode( ",", $hidden_cats );
		
		$this->engine->classes['output']->java_scripts['embed'][] = "var hidden_cats = '{$hidden_cats}';";
		
		$active = explode( ",", $this->engine->my_getcookie( "list_active" ) );
		
		foreach( $active as $item )
		{
			if( preg_match( "#cat=(\w+)#", $item, $match ) )
			{
				$this->categories['active'] = $match[1];
				
				break;
			}
		}
		
		$hidden = explode( ":", $this->engine->my_getcookie( "list_hidden" ) );
		
		foreach( $hidden as $item )
		{
			if( preg_match( "#cat=((\d+,?)*)#", $item, $match ) and $match[1] != "" )
			{
				$this->categories['users_hidden'] = explode( ",", $match[1] );
				
				break;
			}
		}
		
		//-----------------------------------------------------------
		// Определение текущей страницы
		//-----------------------------------------------------------
		
		if( !is_numeric( $this->engine->input['st'] ) ) $this->engine->input['st'] = 0;
		
		$this->engine->input['page'] = ceil( $this->engine->input['st'] / 100 ) + 1;
		
		//-----------------------------------------------------------
		// AJAX запрос
		//-----------------------------------------------------------
			
		if( $this->engine->input['ajax'] == 'yes' ) switch( $this->engine->input['type'] )
		{
			case 'show_contents':
			case 'set_page':
				$this->ajax_show_contents();
				break;
			
			case 'category_add':
			case 'category_edit':
				$this->ajax_category_add_edit();
				break;
				
			case 'category_delete':
				$this->ajax_category_delete();
				break;
				
			case 'category_move':
				$this->ajax_category_move();
				break;
			
			case 'file_info':	
			case 'file_edit':
				$this->ajax_file_show_edit();
				break;
				
			case 'file_delete':
				$this->ajax_file_delete();
				break;
				
			case 'file_move':
				$this->ajax_file_move();
				break;
		}
		
		//-----------------------------------------------------------
		// Обычный запрос
		//-----------------------------------------------------------
			
		$this->show_categories_list();
		
		return TRUE;
	}
	
	/**
    * Список категорий
    * 
    * Выводит список пользовательских и
    * общих категорий и находящихся в них
    * файлов.
    *
    * @return	void
    */
	
	function show_categories_list()
	{
		$this->engine->classes['output']->java_scripts['embed'][] = "var lang_pass_click_to_edit = '{$this->engine->lang['pass_click_to_edit']}';
																	 var lang_user_delete = '{$this->engine->lang['user_delete']}';
																	 var lang_error_user_not_correct = '{$this->engine->lang['error_user_not_correct']}';";
		
		//-----------------------------------------------------------
		// Название страницы и системное сообщение
		//-----------------------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['page_title'];
		$this->page_info['desc']	= $this->engine->lang['page_desc'];
		
		$this->message = array(	'text'	=> "",
								'type'	=> "",
								);
		
		//-----------------------------------------------------------
		// Выводим меню
		//-----------------------------------------------------------
		
		$this->html  = $this->engine->skin['categories']->page_top();
		
		$this->html .= $this->engine->skin['categories']->cat_list_top();
		
		//-----------------------------------------------------------
		// Выводим список категорий
		//-----------------------------------------------------------
		
		$this->_get_categories_list();
		
		$this->engine->classes['output']->java_scripts['embed'][] = "var active_cat = '{$this->categories['active']}';";
		
		$this->html .= $this->engine->skin['categories']->cat_list_bottom();
		
		//-----------------------------------------------------------
		// Выводим список файлов в активной категории
		//-----------------------------------------------------------
		
		$this->html .= $this->engine->skin['categories']->page_middle();
		
		$this->engine->input['id'] =& $this->categories['active'];
		
		$this->_get_category_contents();
		
		$this->html .= $this->engine->skin['categories']->page_bottom();
		
		$this->html  = str_replace( "<!--PAGE_MENU-->", $this->engine->skin['categories']->page_menu(), $this->html );
	}
	
	/**
    * Вывод содержимого категории
    * 
    * Вызывает функцию для сбора информации по содержимому
    * категории в зваисисмости от ее типа.
    *
    * @return	void
    */
	
	function ajax_show_contents()
	{
		//-----------------------------------------------------------
		// Получаем информацию о содержимом категории
		//-----------------------------------------------------------
		
		if( !is_numeric( $this->engine->input['id'] ) and !preg_match( "#^root_\d+$#", $this->engine->input['id'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_cat_id'] ) );
		}
		
		$this->_get_category_contents();
		
		//-----------------------------------------------------------
		// Обновляем содержимое и возвращаем XML
		//-----------------------------------------------------------
		
		$array = array(	"List"			=> &$this->html,
						"Update_11"		=> $this->engine->skin['categories']->page_menu(),
						"Function_0"	=> "update_pages_number({$this->pages_total},{$this->engine->input['st']})",
						"Function_1"	=> "ajax_reselect_files()",
						);
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Добавление и редактирование категории
    * 
    * Выводит параметры текущей категории с возможностью
    * их редактирования.
    *
    * @return	void
    */
	
	function ajax_category_add_edit()
	{
		$type = $this->engine->input['type'] == "category_add" ? "add" : "edit";
		
		//-----------------------------------------------------------
		// Проверяем права на добавление
		//-----------------------------------------------------------
		
		if( $type == "add" and !$this->engine->member['user_admin'] and ( $this->engine->config['use_share_cats'] or !$this->engine->config['can_add_cats'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_add_cat'] ) );
		}
		
		//-----------------------------------------------------------
		// Проверяем права на редактирование
		//-----------------------------------------------------------
		
		if( $type == "edit" and !$this->engine->member['user_admin'] and ( $this->engine->config['use_share_cats'] or !$this->engine->config['can_edit_cats'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_edit_cat'] ) );
		}
		
		//-----------------------------------------------------------
		// Получаем параметры категории
		//-----------------------------------------------------------
		
		if( $type == "add" and preg_match( "#^root_(\d+)$#", $this->engine->input['id'], $match ) )
		{
			if( is_numeric( $match[1] ) and $match[1] == 0 )
			{
				$user['user_id'] = 0;
			}
			else 
			{
				$user = $this->engine->DB->simple_exec_query( array(	'select'	=> 'user_id, user_name',
																		'from'		=> 'users_list',
																		'where'		=> "user_id='{$match[1]}'"
																		)	);
			}
			
			if( !is_numeric( $user['user_id'] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_get_owner_id'] ) );
			}
			
			$root_cat = TRUE;
			
			$cat = array(	'cat_id'	=> 0,
							'cat_user'	=> &$user['user_id'],
							'user_name'	=> &$user['user_name'],
							);
		}
		else
		{		
			$cat = $this->engine->DB->simple_exec_query( array(	'select'	=> '*',
																'from'		=> 'categories_list as c LEFT JOIN users_list as u ON ( u.user_id=c.cat_user )',
																'where'		=> "cat_id='{$this->engine->input['id']}'"
																)	);
		}
		
		if( !is_numeric( $cat['cat_id'] ) and $root_cat !== TRUE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_cat_id'] ) );
		}
		
		if( !$this->engine->member['user_admin'] and $cat['cat_user'] == 0 )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_'.$type.'_cat'] ) );
		}
		
		if( $cat['user_name'] )
		{
			$cat['user_name'] = preg_replace( "#\W#", "_", strtolower( $cat['user_name'] ) );
		}
		else 
		{
			$cat['user_name'] = "_all";
		}
		
		//-----------------------------------------------------------
		// Обновляем параметры категории
		//-----------------------------------------------------------
		
		if( $this->engine->input['apply'] )
		{
			if( !$this->engine->input['cat_name'] )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_cat_name'] ) );
			}
			
			if( !$this->engine->input['cat_icon'] )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_cat_icon'] ) );
			}
			
			if( $this->engine->input['cat_path'] and !preg_match( "#^[\w|/| ]+$#", $this->engine->input['cat_path'] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_cat_path'] ) );
			}
			
			if( $this->engine->input['cat_types'] and !preg_match( "#([a-zA-Z]{1,5}\s*)+#", $this->engine->input['cat_types'] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_cat_types'] ) );
			}
			
			$this->engine->config['save_path'] = preg_replace( "#/$#", "", $this->engine->config['save_path'] );
			
			$params = array(	'cat_name'	=> $this->engine->input['cat_name'],
								'cat_icon'	=> preg_replace( "#\W#", "_", $this->engine->input['cat_icon'] ),
								'cat_path'	=> $this->engine->config['save_path']."/".preg_replace( "#\W#", "_", strtolower( $cat['user_name'] ) )."/".$this->engine->input['cat_path'],
								'cat_desc'	=> $this->engine->input['cat_desc'],
								'cat_types'	=> $this->engine->input['cat_types'],
								);
								
			if( $type == "add" )
			{
				$params['cat_user'] =& $cat['cat_user'];
				$params['cat_root'] =& $cat['cat_id'];
				
				$this->engine->DB->do_insert( "categories_list", &$params );
			}
			else
			{
				$this->engine->DB->do_update( "categories_list", &$params, "cat_id='{$cat['cat_id']}'" );
				
				$array['Message'] =& $this->engine->lang['category_edited'];
			}
			
			//-----------------------------------------------------------
			// Обновляем дерево категорий
			//-----------------------------------------------------------
			
			$this->_get_categories_list();
			
			$array['Update_0'] = &$this->html;
			
			//-----------------------------------------------------------
			// Закрываем AJAX окно при добалении
			//-----------------------------------------------------------
			
			if( $type == "add" )
			{
				$array['CloseWindow'] = TRUE;
				
				$this->engine->classes['output']->generate_xml_output( &$array );
			}
			
			//-----------------------------------------------------------
			// Обновляем параметры категории
			//-----------------------------------------------------------
			
			$cat = array(	'cat_id'	=> $cat['cat_id'],
							'cat_name'	=> &$params['cat_name'],
							'cat_icon'	=> &$params['cat_icon'],
							'cat_path'	=> &$this->engine->input['cat_path'],
							'cat_desc'	=> &$params['cat_desc'],
							'cat_types'	=> &$params['cat_types'],
							);
		}
		
		//-----------------------------------------------------------
		// Преобразуем путь до каталога в относительный
		//-----------------------------------------------------------
		
		$cat['cat_path'] = preg_replace( "#^".$this->engine->config['save_path']."/*".$cat['user_name']."/*#i", "", $cat['cat_path'] );
		
		//-----------------------------------------------------------
		// Формируем список пиктограмм
		//-----------------------------------------------------------
		
		$images = scandir( $this->engine->home_dir."images" );
		
		foreach( $images as $image ) if( preg_match( "#^icon_(\w+)\.png$#", $image, $match ) )
		{
			$selected = ( ( $type == "edit" and $cat['cat_icon'] == $match[1] ) or ( $type == "add" and $match[1] == "folder1" ) ) ? 1 : 0;
			
			$icons .= $this->engine->skin['global']->form_radio( "cat_icon", $match[1], &$selected, "<img src='images/icon_{$match[1]}.png' alt='{$match[1]}' />" );
			
			if( $i == 6 )
			{
				$icons .= "<br/>";
				$i = 0;
			}
			else
			{
				$i++;
			}
		}
		
		//-----------------------------------------------------------
		// Формируем таблицу с параметрами
		//-----------------------------------------------------------
		
		$cat['cat_id'] = $cat['cat_id'] ? $cat['cat_id'] : "root_".$cat['cat_user'];
		
		$table  = $this->engine->classes['output']->form_start( array(	'tab'		=> 'categories',
																		), "id='ajax_form' onsubmit='ajax_apply_params( \"{$cat['cat_id']}\", \"{$type}\" ); return false;'" );
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$table .= $this->engine->classes['output']->table_start( "", "100%", "", "", "id='ajax_table' style='border:0'" );
		
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['category_name']																			, "row1" ),
								array(	$this->engine->skin['global']->form_text( "cat_name", $type == "add" ? "" : $cat['cat_name'], "medium" )		, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['category_icon']																			, "row1" ),
								array(	$icons																											, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['category_path']																			, "row1" ),
								array(	$this->engine->skin['global']->form_text( "cat_path", $type == "add" ? "" : $cat['cat_path'], "medium" )		, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['category_desc']																			, "row1" ),
								array(	$this->engine->skin['global']->form_textarea( "cat_desc", $type == "add" ? "" : $cat['cat_desc'], "medium" )	, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['category_types']																			, "row1" ),
								array(	$this->engine->skin['global']->form_text( "cat_types", $type == "add" ? "" : $cat['cat_types'], "medium" )		, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_end();
								
		$table .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['apply_settings'], "", "style='border:0;'" );
		
		//-----------------------------------------------------------
		// Формируем и возвращаем XML
		//-----------------------------------------------------------
		
		$caption = $type == "add" ? $this->engine->lang['category_edit'] : $this->engine->lang['category_edit']." ".$cat['cat_name'];
		
		$html = $this->engine->skin['global']->ajax_window( &$caption, &$table );
		
		$array['HTML'] =& $html;
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Удаление категории
    * 
    * Удаляет указанную категорию, все ее дочерние
    * категории и очищает список файлов, относящихся
    * к этим категориям.
    *
    * @return	void
    */
	
	function ajax_category_delete()
	{
		//-----------------------------------------------------------
		// Проверяем права на удаление
		//-----------------------------------------------------------
		
		if( !$this->engine->member['user_admin'] and ( $this->engine->config['use_share_cats'] or !$this->engine->config['can_delete_cats'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_cat'] ) );
		}
		
		//-----------------------------------------------------------
		// Получаем параметры категории
		//-----------------------------------------------------------
		
		$cat = $this->engine->DB->simple_exec_query( array(	'select'	=> 'cat_id, cat_user, cat_root',
															'from'		=> 'categories_list',
															'where'		=> "cat_id='{$this->engine->input['id']}'"
															)	);
															
		if( !$cat['cat_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_cat_id'] ) );
		}
		
		if( !$this->engine->member['user_admin'] and $cat['cat_user'] == 0 )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_cat'] ) );
		}
		
		//-----------------------------------------------------------
		// Удаляем категорию, подкатегории и файлы
		//-----------------------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'cat_id, cat_root',
													'from'		=> 'categories_list',
													'where'		=> "cat_user='{$cat['cat_user']}'"
													)	);
		$this->engine->DB->simple_exec();
		
		while( $cat = $this->engine->DB->fetch_row() )
		{
			$this->categories['unsorted'][] = $cat;
		}
		
		$this->categories['delete'][] = $this->engine->input['id'];
		
		$this->_get_all_subcats( &$this->engine->input['id'] );
		
		if( count( $this->categories['delete'] ) )
		{
			$this->engine->DB->do_delete( "categories_list", "cat_id IN('".implode( "','", $this->categories['delete'] )."')" );
			$this->engine->DB->do_delete( "categories_files", "file_cat IN('".implode( "','", $this->categories['delete'] )."')" );
		}
		
		$this->categories['unsorted'] = array();
		
		//-----------------------------------------------------------
		// Обновляем список категорий и возвращаем XML
		//-----------------------------------------------------------
		
		$this->_get_categories_list();
		
		$array['Update_0'] = &$this->html;
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Перемещение категории
    * 
    * Проверяет права на перемещение и условия его выполнения и,
    * в случае отсутствия ошибок, перемещает активную категорию
    * в указанную.
    *
    * @return	void
    */
	
	function ajax_category_move()
	{
		//-----------------------------------------------------------
		// Проверяем права на перемещение
		//-----------------------------------------------------------
		
		if( !$this->engine->member['user_admin'] and ( $this->engine->config['use_share_cats'] or !$this->engine->config['can_move_cats'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_move_cat'] ) );
		}
		
		//-----------------------------------------------------------
		// Получаем параметры категории
		//-----------------------------------------------------------
		
		$cat = $this->engine->DB->simple_exec_query( array(	'select'	=> '*',
															'from'		=> 'categories_list',
															'where'		=> "cat_id='{$this->engine->input['id']}'"
															)	);
		
		if( !$cat['cat_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_cat_id'] ) );
		}
		
		if( !$this->engine->member['user_admin'] and $cat['cat_user'] == 0 )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_move_cat'] ) );
		}
		
		if( preg_match( "#^root_(\d+)$#", $this->engine->input['to'], $match ) )
		{
			if( $cat['cat_root'] == 0 ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_move_to_parent'] ) );
			if( $cat['cat_user'] != $match[1] ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_parent_cat'] ) );
			
			$this->engine->DB->do_update( "categories_list", array( "cat_root" => 0 ), "cat_id='{$cat['cat_id']}'" );
			
			//-----------------------------------------------------------
			// Обновляем список категорий и возвращаем XML
			//-----------------------------------------------------------
			
			$this->_get_categories_list();
			
			$array['Update_0'] = &$this->html;
			
			$this->engine->classes['output']->generate_xml_output( &$array );
		}
		else if( !is_numeric( $this->engine->input['to'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_to_id'] ) );
		}
		
		if( $cat['cat_id'] == $this->engine->input['to'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_parent_cat'] ) );
		}
		
		//-----------------------------------------------------------
		// Формируем список категорий для перемещения
		//-----------------------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'categories_list',
													'where'		=> "cat_user='{$cat['cat_user']}'",
													'order'		=> 'cat_root, cat_name'
													)	);
		$this->engine->DB->simple_exec();
		
		while( $cat_list = $this->engine->DB->fetch_row() )
		{
			$this->categories['unsorted'][ $cat['cat_user'] ][] = $cat_list;
		}
		
		if( !count( $this->categories['unsorted'][ $cat['cat_user'] ] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_cats_found'] ) );
		}
		
		$this->categories['temp'] = $this->categories['unsorted'][ $cat['cat_user'] ];
		
		$this->_sort_cats( $cat['cat_user'] );
		
		$this->categories['unsorted'] = &$this->categories['temp'];
		
		$this->categories['delete'][] = $cat['cat_id'];
		
		$max_level = $this->_get_all_subcats( $cat['cat_id'] );
		
		unset( $this->categories['unsorted'] );
		
		//-----------------------------------------------------------
		// Проверяем, не пытаемся ли мы переместить
		// категорию в одну из дочерних категорий или
		// создать слишком много уровней вложенности
		//-----------------------------------------------------------
		
		foreach( $this->categories['sorted'][ $cat['cat_user'] ] as $cat_list )
		{
			if( $cat_list['cat_id'] == $this->engine->input['to'] )
			{
				if( in_array( $cat_list['cat_id'], $this->categories['delete'] ) )
				{
					$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_move_to_child'] ) );
				}
				else if( $cat_list['cat_level'] + $max_level >= 5 )
				{
					$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['js_error_last_level_move'] ) );
				}
				else if( $cat_list['cat_id'] == $cat['cat_root'] )
				{
					$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_move_to_parent'] ) );
				}
				
				$this->engine->DB->do_update( "categories_list", array( "cat_root" => $cat_list['cat_id'] ), "cat_id='{$cat['cat_id']}'" );
				
				$done = TRUE;
				
				break;
			}
		}
		
		if( !$done )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_move_this_cat'] ) );
		}
		
		$this->categories = array(	'unsorted'	=> array(),
									'sorted'	=> array(),
									'hidden'	=> array(),
									'delete'	=> array(),
									'active'	=> $cat['cat_id'],
									);
		
		//-----------------------------------------------------------
		// Обновляем список категорий и возвращаем XML
		//-----------------------------------------------------------
		
		$this->_get_categories_list();
		
		$array['Update_0'] = &$this->html;
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Параметры файла
    * 
    * Подгружает класс работы с файлами и вызывает функцию
    * вывода AJAX окна с информацией о файле.
    *
    * @return	void
    */
	
	function ajax_file_show_edit()
	{
		if( $this->engine->load_module( "class", "files" ) === FALSE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['err_module_load_failed'] ) );
		}
		
		$this->engine->classes['files']->parent =& $this;
		
		$this->engine->classes['files']->properties_window();
	}
	
	/**
    * Удаление файла
    * 
    * Подгружает класс работы с файлами и вызывает функцию
    * вывода AJAX окна с информацией о файле.
    *
    * @return	void
    */
	
	function ajax_file_delete()
	{
		if( $this->engine->load_module( "class", "files" ) === FALSE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['err_module_load_failed'] ) );
		}
		
		$this->engine->classes['files']->parent =& $this;
		
		$this->engine->classes['files']->delete_file();
	}
	
	/**
    * Перемещение файлов
    * 
    * Проверяет права на перемещение и условия его выполнения и,
    * в случае отсутствия ошибок, перемещает активные файлы в
    * указанную категорию.
    *
    * @return	void
    */
	
	function ajax_file_move()
	{
		//-----------------------------------------------------------
		// Получаем идентификаторы файлов
		//-----------------------------------------------------------
		
		$ids = explode( ",", $this->engine->input['id'] );
		
		if( !count( $ids ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_file_ids'] ) );
		}
		
		if( $ids[0] == "" ) unset( $ids[0] );
		
		//-----------------------------------------------------------
		// Получаем параметры категории 'Откуда'
		//-----------------------------------------------------------
		
		if( preg_match( "#^root_(\d+)$#", $this->engine->input['from'], $match ) )
		{
			$cat['from']['cat_user'] = $match[1];
			$cat['from']['cat_id'] = &$this->engine->input['from'];
		}
		else 
		{
			$cat['from'] = $this->engine->DB->simple_exec_query( array(	'select'	=> 'cat_id, cat_user',
																		'from'		=> 'categories_list',
																		'where'		=> "cat_id='{$this->engine->input['from']}'"
																		)	);
			
			if( !$cat['from']['cat_id'] ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_cat_id'] ) );
		}
		
		//-----------------------------------------------------------
		// Получаем параметры категории 'Куда'
		//-----------------------------------------------------------
		
		if( preg_match( "#^root_(\d+)$#", $this->engine->input['to'], $match ) )
		{
			$cat['to']['cat_user'] = $match[1];
			$cat['to']['cat_id'] = 0;
		}
		else 
		{
			$cat['to'] = $this->engine->DB->simple_exec_query( array(	'select'	=> 'cat_id, cat_user',
																		'from'		=> 'categories_list',
																		'where'		=> "cat_id='{$this->engine->input['to']}'"
																		)	);
			
			if( !$cat['to']['cat_id'] ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_cat_id'] ) );
		}
		
		//-----------------------------------------------------------
		// Получаем параметры файлов
		//-----------------------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'f.*',
													'from'		=> 'categories_files f LEFT JOIN categories_list c ON (c.cat_id=f.file_cat)',
													'where'		=> "f.file_id IN('".implode( "','", $ids )."')"
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_files_found'] ) );
		}
		
		while( $file = $this->engine->DB->fetch_row() )
		{
			$files[] = $file;
		}
		
		foreach( $files as $file )
		{			
			if( $file['file_cat'] == 0 and !$this->engine->member['user_admin'] and $file['file_user'] != $this->engine->member['user_id'] and !$this->engine->config['shared_can_delete' ] ) continue;
			
			$file['cat_user'] = intval( $file['cat_user'] );
			
			//-----------------------------------------------------------
			// Перемещаем файл
			//-----------------------------------------------------------
			
			if( $cat['from']['cat_user'] == 0 and $file['cat_user'] != $cat['to']['cat_user'] ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_wrong_parent_cat_for_file'] ) );
			
			if( $cat['from']['cat_user'] and $file['file_user'] != $cat['to']['cat_user'] ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_wrong_parent_cat_for_file'] ) );
				
			$this->engine->DB->do_update( "categories_files", array( "file_cat" => &$cat['to']['cat_id'] ), "file_id='{$file['file_id']}'" );
		}
		
		//-----------------------------------------------------------
		// Обновляем список файлов и возвращаем XML
		//-----------------------------------------------------------
		
		$this->engine->input['id'] = $cat['from']['cat_id'];
			
		$this->_get_category_contents();
			
		$this->engine->classes['output']->generate_xml_output( array( 'List' => &$this->html, 'Function_0' => 'ajax_reselect_files()' ) );
	}
	
	/**
    * Первоначальная сортировка категорий
    * 
    * Сортирует категории по идентификатору родительской категории
    * и по названию.
    * Сортировка необходима, если используется кодирование строк в
    * БД для работы с нелатинскими символами, т.к. в данном случае
    * при выборке из БД сортировка кодированных строк может не
    * совпадать с сортировкой обработанных строк.
    * 
    * @param 	array			Параметры первой категории
    * @param 	array			Параметры второй категории
    *
    * @return	int				Результат сравнения
    */
	
	function _sort_cats_basic( $a, $b )
	{
		if( $a['cat_root'] != $b['cat_root'] ) return strcmp( $a['cat_root'], $b['cat_root'] );
		if( $a['cat_name'] != $b['cat_name'] ) return strcmp( $a['cat_name'], $b['cat_name'] );
		
		return 0;
	}
	
	/**
    * Сортировка категорий
    * 
    * Рекурсивная функция для создания правильной структуры общих
    * категорий и категорий пользователей.
    * 
    * Внимание: рекурсия может применяться для сортировки категорий
    * с ограниченным уровнем вложенности. Для  высоких уровней
    * требуются значительные ресурсы, поэтому для сортировки
    * многоуровневых структур применяются другие алгоритмы.
    * Однако для данной системы уровень вложенности ограничен пятью,
    * т.к. автор считает такое количество оптимальным для системы.
    * 
    * @param 	int		[opt]	ID пользователя - владельца категорий
    * @param 	int		[opt]	Текущий идентификатор категории
    * @param 	int		[opt]	Текущий уровень
    * @param 	int		[opt]	Максимальный уровень
    *
    * @return	void
    */
	
	function _sort_cats( $uid=0, $id=0, $level=1, $max_level=5 )
	{
		if( $level > $max_level )
		{
			return;
		}
		
		foreach( $this->categories['unsorted'][ $uid ] as $cid => $cat ) if( $cat['cat_root'] == $id )
		{
			$cat['cat_level'] = $level;
			
			$this->categories['sorted'][ $uid ][] = $cat;
			
			unset( $this->categories['unsorted'][ $uid ][ $cid ] );
			
			$level_up = $level + 1;
			
			$this->_sort_cats( $uid, $cat['cat_id'], $level_up, $max_level );
		}
	}
	
	/**
    * Повторная сортировка категорий
    * 
    * В случае, если были добавлены корневые директории
    * пользователей, не имеющих категорий, производится
    * повторная сортировка категорий по имени пользователя.
    * 
    * @param 	array			Параметры первой категории
    * @param 	array			Параметры второй категории
    *
    * @return	int				Результат сравнения
    */
	
	function _resort_cats( $a, $b )
	{
		if( $a == 0 ) return -1;
		if( $b == 0 ) return 1;
		
		return strcasecmp( $this->users[ $a ], $this->users[ $b ] );
	}
	
	/**
    * Получение идентификаторов подкатегорий
    * 
    * Рекурсивная функция для получения идентификаторов
    * всех дочерних категорий указанной категории.
    * 
    * @param 	int				ID родительской категории
    * @param 	int				Уровень вложенности
    *
    * @return	int		Максимальный уровень вложенности
    */
	
	function _get_all_subcats( $cid, $level=1 )
	{
		foreach( $this->categories['unsorted'] as $id => $cat )
		{
			if( $cat['cat_root'] == $cid )
			{
				$this->categories['delete'][] = $cat['cat_id'];
				
				$ids[] = $cat['cat_id'];
				
				unset( $this->categories['unsorted'][ $id ] );
			}
		}
		
		if( is_array( $ids ) ) foreach( $ids as $id )
		{
			$max_level = $this->_get_all_subcats( $id, $level_up = $level + 1 );
			
			$level = $max_level > $level ? $max_level : $level;
		}
		
		return $level;
	}
	
	/**
    * Вывод структуры категорий
    * 
    * Обрабатывает массив с отсортированными категориями
    * и выводит соответствующее дерево категорий.
    *
    * @return	void
    */
	
	function _get_categories_list()
	{
		//-----------------------------------------------------------
		// Получаем список категорий
		//-----------------------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'categories_list',
													'where'		=> $this->engine->member['user_admin'] ? "1" : "cat_user IN('0','{$this->engine->member['user_id']}')",
													'order'		=> 'cat_user, cat_root, cat_name',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $cat = $this->engine->DB->fetch_row() )
		{
			$this->categories['unsorted'][ $cat['cat_user'] ][] = $cat;
			
			if( !$active and $cat['cat_id'] == $this->categories['active'] ) $active = TRUE;
		}
		
		if( count( $this->categories['unsorted'] ) ) foreach( array_keys( $this->categories['unsorted'] ) as $uid )
		{
			if( $this->engine->config['check_non_latin_chars'] )
			{
				usort( $this->categories['unsorted'][ $uid ], array( "categories", "_sort_cats_basic" ) );
			}
			
			$this->_sort_cats( $uid );
		}
		
		//-----------------------------------------------------------
		// Получаем имена пользователей
		//-----------------------------------------------------------
		
		$users = array( 0 => &$this->engine->lang['shared_cat'] );
		
		if( $this->engine->member['user_admin'] )
		{
			$this->engine->DB->simple_construct( array(	'select'	=> 'user_id, user_name',
														'from'		=> 'users_list',
														)	);
			$this->engine->DB->simple_exec();
			
			while( $user = $this->engine->DB->fetch_row() )
			{
				$users[ $user['user_id'] ] = $user['user_name'];
			}
		}
		else
		{
			$users[ $this->engine->member['user_id'] ] = &$this->engine->member['user_name'];
		}
		
		//-----------------------------------------------------------
		// Убеждаемся, что имеются категории всех пользователей
		//-----------------------------------------------------------
		
		foreach( $users as $user_id => $user )
		{
			if( !$this->categories['sorted'][ $user_id ] )
			{
				$this->categories['sorted'][ $user_id ] = array();
				
				$resort = TRUE;
			}
			
			if( !$active and $this->categories['active'] == "root_{$user_id}" ) $active = TRUE;
		}
		
		//-----------------------------------------------------------
		// Повторно сортируем категории по именам пользователей
		//-----------------------------------------------------------
		
		if( $resort )
		{
			$this->users =& $users;
			
			uksort( $this->categories['sorted'], array( "categories", "_resort_cats" ) );
			
			unset( $this->users );
		}
		
		//-----------------------------------------------------------
		// Убеждаемся, что активная категория имеется в списке
		//-----------------------------------------------------------
		
		if( !$active ) $this->categories['active'] = "";
		
		//-----------------------------------------------------------
		// Выводим категории
		//-----------------------------------------------------------
		
		foreach( $this->categories['sorted'] as $user_id => $categories )
		{			
			if( !$user_id or $this->engine->member['user_admin'] or !$this->engine->config['use_share_cats'] or $this->engine->member['user_id'] == $user_id )
			{
				$user_active = $this->categories['active'] == "root_{$user_id}" ? TRUE : FALSE;
				$user_image = $this->_get_user_image( &$user_id );
				
				$this->html .= $this->engine->skin['categories']->cat_list_root( &$users[ $user_id ], &$user_id, &$user_active, &$user_image );
				
				foreach( $categories as $cid => $cat )
				{
					$cat['cat_children'] = $categories[ $cid + 1 ]['cat_root'] == $cat['cat_id'] ? 1 : 0;
					$cat['cat_down']	 = $categories[ $cid + 1 ] ? 1 : 0;
					$cat['cat_hidden']	 = in_array( $cat['cat_id'], $this->categories['hidden'] ) ? 1 : 0;
					
					$d_cid = $cid + 1;
					
					while( $categories[ $d_cid ] )
					{
						if( $categories[ $d_cid ]['cat_root'] == $cat['cat_root'] )
						{
							$cat['cat_relation'] = 1;
							break;
						}
						
						$d_cid++;
					}
					
					$cat['cat_relation'] = $cat['cat_relation'] ? 1 : 0;
					$cat['cat_cid'] = $cid;
					
					$cat['cat_img'] = $this->_get_category_image( &$cat, &$user_id );
					
					if( !$this->categories['active'] )
					{
						$this->categories['active'] = $cat['cat_id'];
						$cat['cat_active'] = TRUE;
					}
					else 
					{
						$cat['cat_active'] = $this->categories['active'] == $cat['cat_id'] ? TRUE : FALSE;
					}
					
					$categories[ $cid ] = $cat;
					$this->categories['sorted'][ $user_id ][ $cid ] = $cat;
					
					$this->html .= $this->engine->skin['categories']->cat_list_element( &$cat );
				}
			}
		}
	}
	
	/**
    * Добавление изображений корневой категории
    * 
    * Определяет изображение, которое следует
    * показать для корневой пользовательской
    * директорий в соответствии с текущими
    * параметрами.
    * 
    * @param 	int				Идентификатор пользователя
    *
    * @return	string			Набор изображений
    */
	
	function _get_user_image( $user_id )
	{
		//-----------------------------------------------------------
		// Проверяем, показываются ли подкатегории
		//-----------------------------------------------------------
		
		$is_array = count( $this->categories['sorted'][ $user_id ] );
		
		if( in_array( $user_id, $this->categories['users_hidden'] ) )
		{
			$img['src'] = $is_array ? "plus4" : "empty";
			$img['alt'] = $is_array ? "[+]-" : "[x]-";
			$img['jsc'] = $is_array ? "show('0','{$user_id}')" : "show('0','{$user_id}')";
		}
		else
		{
			$img['src'] = $is_array ? "minus3" : "empty";
			$img['alt'] = $is_array ? "[-]-" : "[x]-";
			$img['jsc'] = $is_array ? "hide('0','{$user_id}')" : "show('0','{$user_id}')";
		}
		
		$img['cat'] = $user_id;
		$img['num'] = 4;
		
		$image .= $this->engine->skin['categories']->cat_list_image( &$img );
		
		//-----------------------------------------------------------
		// Возвращаем результат
		//-----------------------------------------------------------
		
		return $image;
	}
	
	/**
    * Добавление изображений структуры
    * 
    * Определяет положение категории в общей
    * структуре и создает ряд изображений для
    * визуализации этого положения.
    * 
    * @param 	array			Параметры категории
    * @param 	int				Идентификатор пользователя
    *
    * @return	string			Набор изображений
    */
	
	function _get_category_image( $cat, $user_id )
	{
		for( $i=1; $i <= $cat['cat_level']; $i++ )
		{
			//-----------------------------------------------------------
			// Получаем информацию о родительской категории
			//-----------------------------------------------------------
			
			$j = $i;
					
			while( true )
			{
				$cat['cat_up'] =& $this->categories['sorted'][ $user_id ][ $cat['cat_cid'] - ( $cat['cat_level'] - $j ) ];
				
				if( !$cat['cat_up'] or $cat['cat_up']['cat_level'] == $i ) break;
						
				$j--;
			}
					
			$cat['cat_up_rel']	= ( $cat['cat_up']['cat_root'] != $cat['cat_root'] and $cat['cat_up']['cat_relation'] ) ? 1 : 0;
			$cat['cat_up_hidden'] = $cat['cat_up']['cat_hidden'] ? 1 : $cat['cat_up_hidden'];
			
			if( $i == 1 and in_array( "root_{$cat['cat_user']}", $this->categories['hidden'] ) ) $cat['cat_up_hidden'] = 1;
			
			//-----------------------------------------------------------
			// Определяем изображение для каждого уровня
			// вложенности
			//-----------------------------------------------------------
			
			if( $cat['cat_down'] )
			{
				if( $cat['cat_level'] == $i )
				{
					if( $cat['cat_children'] )
					{
						if( $cat['cat_relation'] )
						{
							$img['src'] = $cat['cat_hidden'] ? "plus2" : "minus2";
							$img['alt'] = $cat['cat_hidden'] ? "[+]-" : "[-]-";
							$img['jsc'] = $cat['cat_hidden'] ? "show('{$cat['cat_id']}','{$cat['cat_user']}')" : "hide('{$cat['cat_id']}','{$cat['cat_user']}')";
							$img['num'] = 2;
						}
						else 
						{
							$img['src'] = $cat['cat_hidden'] ? "plus1" : "minus1";
							$img['alt'] = $cat['cat_hidden'] ? "[+]-" : "[-]-";
							$img['jsc'] = $cat['cat_hidden'] ? "show('{$cat['cat_id']}','{$cat['cat_user']}')" : "hide('{$cat['cat_id']}','{$cat['cat_user']}')";
							$img['num'] = 1;
						}
					}
					else 
					{
						if( $cat['cat_relation'] )
						{
							$img['src'] = "line3";
							$img['alt'] = "&nbsp;|--";
						}
						else 
						{
							$img['src'] = "line2";
							$img['alt'] = "&nbsp;`--";
						}
					}
				}
				else 
				{
					if( $cat['cat_up_rel'] )
					{
						$img['src'] = "line1";
						$img['alt'] = "&nbsp;|&nbsp;&nbsp;";
					}
					else 
					{
						$img['src'] = "none";
						$img['alt'] = "&nbsp;&nbsp;&nbsp;&nbsp;";
					}
					
					unset( $cat['cat_up'] );
				}
			}
			else 
			{
				if( $cat['cat_children'] )
				{
					$img['src'] = $cat['cat_hidden'] ? "plus1" : "minus1";
					$img['alt'] = $cat['cat_hidden'] ? "[+]-" : "[-]-";
					$img['jsc'] = $cat['cat_hidden'] ? "show('{$cat['cat_id']}','{$cat['cat_user']}')" : "hide('{$cat['cat_id']}','{$cat['cat_user']}')";
					$img['num'] = 1;
				}
				else if( $i == $cat['cat_level'] )
				{
					$img['src'] = "line2";
					$img['alt'] = "&nbsp;`--";
				}
				else 
				{
					$img['src'] = "none";
					$img['alt'] = "&nbsp;&nbsp;&nbsp;&nbsp;";
				}
			}
			
			//-----------------------------------------------------------
			// Подгружаем шаблон изображения и ссылку, если
			// необходимо
			//-----------------------------------------------------------
			
			$img['cat'] =& $cat['cat_id'];
			
			$image .= $this->engine->skin['categories']->cat_list_image( &$img );
		}
		
		//-----------------------------------------------------------
		// Возвращаем результат
		//-----------------------------------------------------------
		
		return $image;
	}
	
	/**
    * Вывод списка файлов категории
    * 
    * Формирует и выводит список файлов, ассоциированных
    * с данной категорией.
    *
    * @return	void
    */
	
	function _get_category_contents()
	{
		$this->engine->load_skin( "files" );
		
		//-----------------------------------------------------------
		// Определяем активный столбец и тип сортировки
		//-----------------------------------------------------------
		
		preg_match( "#tab_categories_(state|name|added|size)=(asc|desc)#", $this->engine->my_getcookie( "sort_params" ), $match );
		
		$match[1] = $match[1] ? $match[1] : "name";
		$match[2] = $match[2] ? ( $match[2] == "asc" ? "desc" : "asc" ) : "asc";
		
		$active = array(	'state'	=> $match[1] == 'state' ? array( 'img' => $this->engine->skin['categories']->cat_content_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' ),
							'name'	=> $match[1] == 'name'  ? array( 'img' => $this->engine->skin['categories']->cat_content_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' ),
							'added'	=> $match[1] == 'added' ? array( 'img' => $this->engine->skin['categories']->cat_content_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' ),
							'size'	=> $match[1] == 'size'  ? array( 'img' => $this->engine->skin['categories']->cat_content_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' ),
							);
		
		$this->html .= $this->engine->skin['categories']->cat_content_headers( &$active );
		
		if( preg_match( "#^root_(\d+)$#", $this->engine->input['id'], $user ) )
		{
			$where  = "file_cat='0' AND ";
			$where .= $user[1] ? "file_user='{$user[1]}' AND file_shared<>1" : "file_shared=1";
		}
		else if( is_numeric( $this->engine->input['id'] ) )
		{
			$where = "file_cat='{$this->engine->input['id']}'";
		}
		else 
		{
			$this->html .= $this->engine->skin['categories']->cat_content_message( &$this->engine->lang['error_wrong_cat_id'] );
			return;
		}
		
		//-----------------------------------------------------------
		// Определяем количество страниц
		//-----------------------------------------------------------
		
		$by_name = $match[1] == 'file_name' ? "" : ", file_name {$match[2]}";
		
		$pages = $this->engine->DB->simple_exec_query( array(	'select'	=> 'COUNT(file_id) as total',
																'from'		=> 'categories_files',
																'where'		=> &$where,
																)	);
		
		$this->pages_total = intval( is_numeric( $pages['total'] ) ? ceil( $pages['total'] / 100 ) : 1 );
		
		$this->engine->classes['output']->java_scripts['embed'][] = "pages_total = {$this->pages_total};
																	 pages_st = {$this->engine->input['st']};";
		
		//-----------------------------------------------------------
		// Получаем список файлов категории
		//-----------------------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'file_id, file_name, file_size, file_added, file_state',
													'from'		=> 'categories_files',
													'where'		=> &$where,
													'order'		=> "file_{$match[1]} {$match[2]} {$by_name}",
													'limit'		=> array( $this->engine->input['st'], 100 ),
													)	);
		$this->engine->DB->simple_exec();
		
		while( $file = $this->engine->DB->fetch_row() )
		{
			$files[] = $file;
		}
		
		if( !is_array( $files ) )
		{
			$this->html .= $this->engine->skin['categories']->cat_content_message( &$this->engine->lang['cat_is_empty'] );
		}
		
		//-----------------------------------------------------------
		// Делаем сортировку по состоянию и (или) по имени
		//-----------------------------------------------------------
		
		if( is_array( $files ) and $match[1] != 'name' )
		{
			switch( $match[1] )
			{
				case 'added': $this->sort_by = "file_added";
				break;
				
				case 'size': $this->sort_by = "file_size";
				break;
			}
			
			usort( $files, array( "categories", $match[1] == 'state' ? "_sort_by_state" : "_sort_by_name" ) );
			
			if( $match[2] == 'desc' ) $files = array_reverse( $files );
		}
		
		//-----------------------------------------------------------
		// Выводим список файлов
		//-----------------------------------------------------------
		
		if( is_array( $files ) ) foreach( $files as $file )
		{
			$row = $row == 5 ? 6 : 5;
			
			$file['file_state'] = $this->engine->skin['files']->file_state( &$file['file_state'], FALSE );
			$file['file_added'] = $this->engine->get_date( &$file['file_added'], "LONG" );
			$file['file_size'] = $this->engine->convert_file_size( &$file['file_size'] );
			
			$this->html .= $this->engine->skin['categories']->cat_file_row( &$file, $row );
		}
		
		$this->html .= $this->engine->skin['categories']->cat_content_footer();
	}
	
	//-----------------------------------------------------------
	
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

}

?>