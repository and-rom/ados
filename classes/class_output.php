<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Вывод на экран
*/

/**
* Класс, содержащий функции для подготовки
* страницы к выводу на экран.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class output
{
	/**
	* Столбцы текущей таблицы
	*
	* @var array
	*/

	var $rows 			= array();
	
	/**
	* Количество столбцов
	*
	* @var int
	*/

	var $rows_count		= 0;
	
	/**
	* Массив с необходимыми Java скриптами
	*
	* @var array
	*/

	var $java_scripts	= array(	'link'	=> array(),
									'embed'	=> array(),
									);
									
	/**
	* Ссылки на тему по ADOS и на скачивание
	* последней версии
	* 
	* @var array 
	*/
	
	var $links			= array(	'info'		=> "http://wl500g.info/showthread.php?t=10012",
									'download'	=> "http://download.dini.su/ados/stable"
									);
	
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Вызывает функцию загрузки глобального шаблона.
	* Всегда возвращает TRUE.
	* 
	* @return	bool				Отметка об успешном выполнении
	*/
	
	function __class_construct()
	{
		$this->engine->load_skin( 'global' );
		
		return TRUE;
	}
	
	/**
	* Вывод на экран
	* 
	* Загружает основной шаблон и заменяет его элементы
	* на результаты работы скрипта.
	* Обработанный шаблон выводит на экран.
	* 
	* @return	void
	*/
	
	function do_output()
	{
		//-----------------------------------------
		// Проверяем необходимость обработки PNG
		//-----------------------------------------
		
		$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
		
		if ( strstr( $useragent, 'msie' ) )
		{
			preg_match( "#msie[ /]([0-9\.]{1,10})#", strtolower( $useragent ), $ver );
			
			if( $ver[1] >= 7 ) $this->engine->config['parse_png'] = FALSE;
		}
		else 
		{
			$this->engine->config['parse_png'] = FALSE;
		}
		
		//-----------------------------------------
		// Подгружаем шаблон
		//-----------------------------------------
		
		$template = $this->engine->skin['global']->main_template();
		
		//-----------------------------------------
		// Записываем накопленные события в журнал
		//-----------------------------------------
		
		$this->engine->insert_db_log();
		
		//-----------------------------------------------
		// Заменяем элементы шаблона
		//-----------------------------------------------
		
		$template = str_replace( "<!--LANGUAGE-->"		, $this->get_lang_menu()	, $template );
		$template = str_replace( "<!--JAVA_SCRIPTS-->"	, $this->get_java_scripts()	, $template );
		$template = str_replace( "<!--MENU-->"			, $this->get_menu()			, $template );
		$template = str_replace( "<!--CONTENT-->"		, $this->get_content()		, $template );
		$template = str_replace( "<!--DEBUG_INFO-->"	, $this->get_debug_info()	, $template );
		
		print $template;
	}
	
	/*-------------------------------------------------------------------------*/
	// Меню и основное содержимое
	/*-------------------------------------------------------------------------*/
	
	/**
    * Java скрипты
    * 
    * Загружает JavaScript'ы, указанные в переменной
    * $this->java_scripts.
    *
    * @return	string			Список скриптов
    */
	
	function get_java_scripts()
	{
		if( $this->engine->input['install_update'] === TRUE ) return;
		
		$scripts = array();
		
		//-----------------------------------------------
		// Языковые строки для JavaScript
		//-----------------------------------------------
		
		foreach( $this->engine->lang as $key => $value )
		{
			if( strpos( $key, "js_" ) === 0 )
			{
				$strings[] = 'var lang_'.str_replace( "js_", "", $key ).' = "'.$value.'";';
			}
		}
		
		if( is_array( $strings ) )
		{
			$predefined['embed'][] = implode( "\n\t", $strings );
		}
		
		//-----------------------------------------------
		// Глобальные предопределенные скрипты
		//-----------------------------------------------
		
		$predefined['link'][]  = 'global';
		$predefined['embed'][] = "var base_url = \"{$this->engine->base_url}\";\n\t\tvar cookie_domain = \"{$this->engine->config['cookie_domain']}\";\n\t\tvar cookie_path = \"{$this->engine->config['cookie_path']}\";";
		
		$this->java_scripts['embed'] = array_merge( $predefined['embed'], $this->java_scripts['embed'] );
		$this->java_scripts['link']  = array_merge( $predefined['link'] , $this->java_scripts['link']  );
		
		//-----------------------------------------------
		// Вывод скриптов
		//-----------------------------------------------
		
		if( count( $this->java_scripts['embed'] ) )
		{
			foreach( $this->java_scripts['embed'] as $script )
			{
				$scripts[] = $this->engine->skin['global']->java_script_embed( preg_replace( "#\n\s{2,}#", "\n\t", $script ) );
			}
		}
		
		if( count( $this->java_scripts['link'] ) )
		{
			foreach( $this->java_scripts['link'] as $script )
			{
				$scripts[] = $this->engine->skin['global']->java_script_link( $script );
			}
		}
		
		if( count( $this->java_scripts['footer'] ) )
		{
			foreach( $this->java_scripts['footer'] as $script )
			{
				$scripts[] = $this->engine->skin['global']->java_script_embed( preg_replace( "#\n\s{2,}#", "\n\t", $script ) );
			}
		}
		
		unset( $this->java_scripts );
		
		return implode( "\n", $scripts );
	}
	
	/**
    * Меню выбора языка
    * 
    * Выводит меню выбора языка системы.
    *
    * @return	string			HTML код
    */
	
	function get_lang_menu()
	{
		if( $this->engine->input['install_update'] === TRUE ) return;
		
		if( !$this->engine->member['user_id'] ) $this->engine->member['user_lang'] = $this->engine->languages['default'];
		
		$dropdown = $this->engine->skin['global']->form_dropdown( "lang_selector", &$this->engine->languages['list'], &$this->engine->member['user_lang'], "small" );
		
		return $this->engine->skin['global']->lang_menu( &$dropdown );
	}
	
	/**
    * Верхнее меню
    * 
    * Выводит верхнее меню в соответствии с
    * разрешениями для текущего пользователя.
    * Возвращает HTML код меню.
    *
    * @return	string			HTML код
    */
	
	function get_menu()
	{
		//-----------------------------------------------
		// Мы авторизованы?
		//-----------------------------------------------
		
		if( $this->engine->classes['session']->session['confirmed'] !== TRUE or $this->engine->input['install_update'] === TRUE )
		{
			return "";
		}
		
		//-----------------------------------------------
		// Выводим ссылки на разрешенные для просмотра
		// секции
		//-----------------------------------------------
		
		$menu = "";
		
		foreach( $this->engine->tabs as $tname => $tperm ) if( $this->engine->member['user_admin'] or $tperm == 'all' )
		{
			$menu .= $this->engine->skin['global']->menu_button( $tname, ( $this->engine->input['tab'] == $tname ) ? "on" : "off" );
		}
		
		if( !$this->engine->member['user_admin'] or $this->engine->input['tab'] == 'auth' )
		{
			return $this->engine->skin['global']->menu( $menu );
		}
		
		//-----------------------------------------------
		// Проверяем, нет ли установочных директорий
		//-----------------------------------------------
		
		$return = $this->engine->skin['global']->menu( $menu );
		
		if( file_exists( $this->engine->home_dir."install" ) )
		{
			$return .= $this->engine->skin['global']->system_message( &$this->engine->lang['delete_install_dir'], "red", "id='warn_install'" );
		}
		
		if( file_exists( $this->engine->home_dir."update" ) )
		{
			$return .= $this->engine->skin['global']->system_message( &$this->engine->lang['delete_update_dir'], "red", "id='warn_update'" );
		}
		
		//-----------------------------------------------
		// Проверяем, нет ли блокирующих файлов
		//-----------------------------------------------
		
		$time_now = time();
		
		if( file_exists( $this->engine->home_dir."task.lock" ) and ( $time_now - filemtime( $this->engine->home_dir."task.lock" ) ) > 60 )
		{
			$this->engine->lang['found_task_lock'] = str_replace( "<#HERE#>", $this->engine->skin['global']->delete_lock_file('task'), $this->engine->lang['found_task_lock'] );
			
			$return .= $this->engine->skin['global']->system_message( &$this->engine->lang['found_task_lock'], "red", "id='warn_task_lock'" );
		}
		
		if( file_exists( $this->engine->home_dir."cron.lock" ) and ( $time_now - filemtime( $this->engine->home_dir."cron.lock" ) ) > 60 )
		{
			$this->engine->lang['found_cron_lock'] = str_replace( "<#HERE#>", $this->engine->skin['global']->delete_lock_file('cron'), $this->engine->lang['found_cron_lock'] );
			
			$return .= $this->engine->skin['global']->system_message( &$this->engine->lang['found_cron_lock'], "red", "id='warn_cron_lock'" );
		}
		
		//-----------------------------------------------
		// Проверяем, нет ли новой версии
		//-----------------------------------------------
		
		if( $this->engine->config['check_for_updates'] )
		{
			if( ceil( $time_now - $this->engine->update_last_check['date'] ) > ( $this->engine->config['check_for_updates'] * 24 * 60 * 60 ) )
			{
				$check_again = TRUE;
				
				$info = $this->check_for_updates();
			}
			
			if( $check_again !== TRUE or $info === FALSE )
			{
				$saved = $this->engine->rc4_decrypt( $this->engine->update_last_check['result'], "updater" );
				
				if( $saved == "actual" )
				{
					$info = TRUE;
				}
				else if( $this->engine->config['__engine__']['build'] == $this->engine->update_last_check['build'] )
				{
					$this->engine->DB->do_update( "settings_list", array( "setting_options" => time()." ".$this->engine->rc4_encrypt( "actual", "updater" ) ), "setting_key='reserved_space'" );
					
					$info = TRUE;
				}
				else
				{
					$info = explode( ",", $saved );
					
					$info[1] = $this->engine->get_date( $info[1], 'MEDIUM' );
				}
			}
			
			if( $info !== TRUE )
			{
				$info[2] = &$this->links['info'];
				$info[3] = &$this->links['download'];
				
				$message = str_replace( array( "#VERSION#", "#DATE#", "#LINK_INFO#", "#LINK_DOWNLOAD#" ), &$info, $this->engine->lang['update_avaliable'] );
					
				$return .= $this->engine->skin['global']->system_message( &$message, "orange", "id='info_update'" );
			}
		}
		
		return $return;
	}
	
	/**
    * Основное содержимое
    * 
    * Формирует поле с информацией о текущей
    * странице, а также, при необходимости,
    * поле с системным сообщением.
    * Возвращает сформированные поля и
    * содержимое переменной $this->html
    *
    * @return	string			HTML код
    */

	function get_content()
	{
		$html =& $this->engine->sections[ $this->engine->input['tab'] ]->html;
		
		if( $this->engine->sections[ $this->engine->input['tab'] ]->message['text'] )
		{
			$html = call_user_method_array( "system_message", $this->engine->skin['global'], &$this->engine->sections[ $this->engine->input['tab'] ]->message ).$html;
		}
		
		if( $this->engine->sections[ $this->engine->input['tab'] ]->page_info['title'] )
		{
			$html = call_user_method_array( "page_info", $this->engine->skin['global'], &$this->engine->sections[ $this->engine->input['tab'] ]->page_info ).$html;
		}
		
		return $html;
	}
	
	/**
    * Отладочная информация
    * (Доступна только администраторам)
    * 
    * Возвращает информацию о времени выполнения
    * скрипта, количестве и времени выполнения
    * запросов в БД и текст этих запросов.
    *
    * @return	string			HTML код
    */
	
	function get_debug_info()
	{
		return IN_DEBUG ? $this->engine->classes['debug']->debug_info() : "";
	}
	
	/*-------------------------------------------------------------------------*/
	// Элементы интерфейса - таблицы
	/*-------------------------------------------------------------------------*/
	
	/**
    * Добавление заголовка таблицы
    * 
    * Создает новый столбец таблицы, увеличивая
    * число столбцов на единицу и присваивая новому
    * столбцу указанный заголовок.
    * 
    * @param 	string	[opt]	Имя столбца
    * @param 	string	[opt]	Ширина столбца
    * @param 	string	[opt]	Стиль ячейки
    * @param 	string	[opt]	Дополнительный код
    *
    * @return	void
    */
	
	function table_add_header( $caption="", $width="50%", $style="row1", $misc="" )
	{
		$this->rows[] = array(	'caption'	=> $caption,
								'style'		=> $style,
								'width'		=> $width,
								'misc'		=> $misc
								);
								
		$this->rows_count++;
	}
	
	/**
    * Начало таблицы
    * 
    * Создает начало таблицы с указанным названием и
    * первый ряд ранее указанных столбцов (если у них
    * имеются названия).
    * Возвращает HTML код.
    * 
    * @param 	string	[opt]	Заголовок таблицы
    * @param 	string	[opt]	Ширина таблицы
    * @param 	string	[opt]	Дополнительные параметры
    * @param 	string	[opt]	Дополнительный код
    * @param 	string	[opt]	Дополнительные параметры обводки
    *
    * @return	string			HTML код.
    */
	
	function table_start( $caption="", $width="100%", $misc="", $html_misc="", $bmisc="" )
	{
		$html = $this->engine->skin['global']->table_start( $caption, $width, $misc, $html_misc, $bmisc );
		
		$capt = 0;
		
		foreach( $this->rows as $row )
		{
			if( $row['caption'] )
			{
				$capt = 1;
				break;
			}
		}
		
		if( $capt )
		{
			$html .= $this->engine->skin['global']->table_tr( 0, 1 );
			
			for( $i = 0; $i < $this->rows_count; $i++ )
			{
				$html .= call_user_method_array( "table_th", $this->engine->skin['global'], $this->rows[ $i ] );
			}
			
			$html .= $this->engine->skin['global']->table_tr( 1, 0 );
		}
		
		return $html;
	}
	
	/**
    * Добавление ряда
    * 
    * Добавляет в текущую таблицу новый ряд с указанными параметрами.
    * Возвращает HTML код.
    * 
    * @param 	array			Ячейки таблицы
    * @param 	string	[opt]	Дополнительные параметры для <tr> тэга
    *
    * @return	string			HTML код.
    */
	
	function table_add_row( $cells, $misc="" )
	{
		$html = $this->engine->skin['global']->table_tr( 0, 1, $misc );
		
		foreach( $cells as $cid => $cell )
		{
			$html .= $this->engine->skin['global']->table_td( $cell[0], $cell[1], $this->rows[ $cid ]['width'], $cell[2] );
		}
		
		$html .= $this->engine->skin['global']->table_tr( 1, 0 );
		
		return $html;
	}
	
	/**
    * Добавление ряда с единственной ячейкой
    * 
    * Добавляет в таблицу ряд с одной ячейкой.
    * 
    * @param 	string			Содержимое ячейки
    * @param 	string	[opt]	Стиль ячейки
    * @param 	string	[opt]	Дополнительные параметры для ячейки
    * @param 	string	[opt]	Дополнительные параметры для <tr> тэга
    *
    * @return	string			HTML код.
    */
	
	function table_add_row_single_cell( $content, $style="row1", $cmisc="", $trmisc="" )
	{
		if( strpos( $cmisc, "style=" ) === FALSE ) $cmisc = "colspan='".$this->rows_count."'";
		else $cmisc = str_replace( "style='", "colspan='".$this->rows_count."' style='", $cmisc );
		
		$html  = $this->engine->skin['global']->table_tr( 0, 1, $trmisc );
		$html .= $this->engine->skin['global']->table_td( $content, $style, $this->rows[0]['width'] ? $this->rows[0]['width'] : "100%", $cmisc );
		$html .= $this->engine->skin['global']->table_tr( 1, 0 );
		
		return $html;
	}
	
	/**
    * Добавление ряда с кнопкой
    * 
    * Добавляет в таблицу ряд с submit-кнопкой.
    * 
    * @param 	string			Название кнопки
    * @param 	string	[opt]	Дополнительные параметры для кнопки
    * @param 	string	[opt]	Дополнительные параметры для <tr> тэга
    *
    * @return	string			HTML код.
    */
	
	function table_add_submit( $value="", $bmisc="", $trmisc="" )
	{
		$html  = $this->engine->skin['global']->table_tr( 0, 1, $trmisc );
		
		$html .= $this->engine->skin['global']->table_td( $this->engine->skin['global']->form_button( $value, "submit", $bmisc ), "row3", $this->rows_count > 1 ? "" : $this->rows[0]['width'], "colspan='{$this->rows_count}'" );
		
		$html .= $this->engine->skin['global']->table_tr( 1, 0 );
		
		return $html;
	}
	
	/**
    * Добавление кнопки
    * 
    * Добавляет в таблицу ряд с submit-кнопкой
    * и дополнительными кнопками.
    * 
    * @param 	array			array(	0 => string			Тип кнопки
    * 									1 => string			Название кнопки
    * 									2 => string	[opt]	Дополнительные параметры ссылки
    * 									3 => string	[opt]	Дополнительные параметры для кнопки
    * 									)
    * @param	string	[opt]	Дополнительные параметры для <tr> тэга
    *
    * @return	string			HTML код.
    */
	
	function table_add_submit_multi( $buttons, $trmisc="" )
	{
		$html  = $this->engine->skin['global']->table_tr( 0, 1, $trmisc );
		
		$buttons_code = array();
		
		foreach( $buttons as $button )
		{
			$bmisc  = $button[0] == "button" ? "onclick='self.location.href=\"{$this->engine->base_url}section={$this->engine->input['section']}&amp;module={$this->engine->input['module']}&amp;class={$this->engine->input['class']}&amp;key=view{$button[2]}\"' " : "";
			$bmisc .= $button[3];
			
			$buttons_code[] = $this->engine->skin['global']->form_button( $button[1], $button[0], $bmisc );
		}
		
		$html .= $this->engine->skin['global']->table_td( implode( " ", $buttons_code ), "row3", $this->rows[1]['width'], "colspan='{$this->rows_count}'" );
		
		$html .= $this->engine->skin['global']->table_tr( 1, 0 );
		
		return $html;
	}
	
	/**
    * Добавление таблицы с предупреждением об
    * удалении элемента.
    * 
    * @param 	string			Текст предупреждения
    *
    * @return	string			HTML код.
    */
	
	function table_add_alert_delete( $alert_text, $misc="" )
	{
		$this->table_add_header( ""	, "10%" );
		$this->table_add_header( ""	, "90%" );
		
		$html  = $this->table_start();
																		
		$confirmation = array(	0 => "<b>".$this->engine->lang['alert'].".</b>",
								1 => $alert_text,
								2 => ""
								);
								
		if( $misc )
		{
			$confirmation += array( 3 => $misc, 4 => "" );
		}	

		$confirmation += array(	5 => "<b>".$this->engine->lang['attention']."!</b>",
								6 => $this->engine->lang['delete_alert'],
								7 => $this->engine->lang['delete_confirm'],
								);
								
		$html .= $this->table_add_row( array( 
							array(	"<img src='images/alert.png' alt='{$this->engine->lnag['alert']}' />"	, "row1", "style='text-align:center'"	),
							array(	implode( "<br/>\n", $confirmation )										, "row2" 								),
							)	);
								
		$html .= $this->table_add_submit_multi( array(	array(	"submit"	, $this->engine->lang['yes'] ),
														array(	"button"	, $this->engine->lang['no']  ),
														)	);
		
		$html .= $this->table_end();
		
		unset( $confirmation, $misc );
		
		return $html;	
	}
	
	/**
    * Конец таблицы
    * 
    * Заканчивает таблицу и очищает переменные $this->...
    * Возвращает HTML код.
    *
    * @return	string			HTML код.
    */
	
	function table_end()
	{
		$html = $this->engine->skin['global']->table_end();
		
		$this->rows = array();
		$this->rows_count = 0;
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// Элементы интерфейса - формы
	/*-------------------------------------------------------------------------*/
	
	/**
    * Начало формы
    * 
    * Начинает форму и добавляет скрытые поля,
    * указанные в переданном массиве.
    * 
    * @param 	array	[opt]	Скрытые поля
    * @param 	string	[opt]	Дополнительные параметры
    *
    * @return	string			HTML код.
    */
	
	function form_start( $hidden=array(), $misc="" )
	{
		$html = $this->engine->skin['global']->form_start( $misc );
		
		if( is_array( $hidden ) )
		{
			foreach( $hidden as $name => $value )
			{
				$html .= $this->engine->skin['global']->form_hidden( $name, $value );
			}
		}
		
		return $html;
	}
	
	/**
    * Конец формы
    * 
    * Заканчивает форму.
    * 
    * @return		string			HTML код.
    */
	
	function form_end()
	{
		return $this->engine->skin['global']->form_end();
	}
	
	/**
    * Конец формы с кнопкой
    * 
    * Заканчивает форму, выводя отдельный ряд
    * с кнопкой отправки запроса.
    * 
    * @param 	string			Название кнопки
    * @param 	string	[opt]	Дополнительные параметры для кнопки
    * @param 	string	[opt]	Дополнительные параметры для рамки
    * @param 	string	[opt]	Дополнительные параметры для формы
    * 
    * @return	string			HTML код.
    */
	
	function form_end_submit( $value="", $bmisc="", $tblmisc="", $divmisc="" )
	{
		$button = $this->engine->skin['global']->form_button( $value, "submit", $bmisc );
		
		$return = $this->engine->skin['global']->form_row_submit( $button, $tblmisc, $divmisc );
		
		$return .= $this->engine->skin['global']->form_end();
		
		unset( $button );
		
		return $return;
	}
	
	/**
	* Обработка настройки
	* 
	* Обрабатывает параметры переданной настройки
	* и возвращает XHTML код, соответствующий ее
	* типу.
	* 
	* @param 	array			Массив с параметрами настройки
	* @param 	string	[opt]	Текущее значение настройки
	* @param 	sting	[opt]	Длина поля ввода
	*
	* @return	void
	*/
	
	function parse_setting( $setting, $value=FALSE, $length='large' )
	{
		if( is_array( $setting ) ) foreach( $setting as $key => $val )
		{
			if( strpos( $key, "l." ) == 0 ) $setting[ str_replace( "l.", "", $key ) ] = $setting[ $key ];
		}
		else 
		{
			return;
		}
		
		if( $value )
		{
			$setting['setting_value'] =& $value;
		}
		
		if( $setting['setting_value'] == "" and !is_numeric( $setting['setting_value'] ) and $setting['setting_default'] != "" )
		{
			$setting['setting_value'] =& $setting['setting_default'];
		}
		
		switch( $setting['setting_type'] )
		{
			case 'text':
				return $this->engine->skin['global']->form_text( "setting_{$setting['setting_id']}", $setting['setting_value'], &$length );
				break;
				
			case 'yes_no':
				return $this->engine->skin['global']->form_yes_no( "setting_{$setting['setting_id']}", $setting['setting_value'] );
				break;
				
			case 'dropdown':
			{	
				preg_match_all( "#(\w+)=(\w+)#", $setting['setting_options'], $options, PREG_SET_ORDER );
				
				foreach( $options as $option )
				{
					$dropdown[ $option[1] ] = is_numeric( $option[2] ) ? $option[2] : $this->engine->lang['setting_opt_'.$option[2] ];
				}
				
				return $this->engine->skin['global']->form_dropdown( "setting_{$setting['setting_id']}", &$dropdown, $setting['setting_value'], &$length );
				break;
			}
		}
	}
	
	/**
	* Вывод XML
	* 
	* Выводит XML код на основе переданных
	* параметров.
	* 
	* @param 	array			Массив с XHTML кодом
	* @param 	bool			Применять шифрование для тупого IE
	*
	* @return	void
	*/

	function generate_xml_output( $html, $encrypt=FALSE )
	{
		//-----------------------------------------
		// Записываем накопленные события в журнал
		//-----------------------------------------
		
		$this->engine->insert_db_log();
		
		//-----------------------------------------
		// Устанавливаем заголовок типа содержимого
		//-----------------------------------------
		
		$encrypt ? header('Content-Type: text/html') : header('Content-Type: text/xml');
		
		//-----------------------------------------
		// Подгружаем класс работы с XML
		//-----------------------------------------

		$xmlstr = "<?xml version='1.0' encoding='UTF-8'?><ajax><xmlcode/></ajax>";
		
		$xml = new SimpleXMLElement( $xmlstr );
		
		//-----------------------------------------
		// Обрабатываем переданные параметры
		//-----------------------------------------
		
		foreach( $html as $name => $txt )
		{
			# Добавляем запись в лог
			
			if( $name == 'Log' and is_array( $txt ) and $txt['level'] and $txt['code'] )
			{
				$this->engine->add_log_event( &$txt['level'], &$txt['code'], is_array( $txt['misc'] ) ? $txt['misc'] : array() );
			}
			
			# Есть параметры значений и атрибутов
			
			else if( is_array( $txt ) and $txt['value'] and $txt['attrs'] )
			{
				$xml->xmlcode->addChild( $name, $this->parse_xml_value( $value ) );
				
				if( $txt['attrs'] ) foreach( $txt['attrs'] as $key => $value )
				{
					$xml->xmlcode->{$name}->addAttribute( $key, $value );
				}
			}
			
			# Параметров нет
			
			else if( is_array( $txt ) ) foreach( $txt as $value )
			{
				$xml->xmlcode->addChild( $name, $value['value'] ? $this->parse_xml_value( $value['value'] ) : $this->parse_xml_value( $value ) );
				
				if( $value['attrs'] ) foreach( $value['attrs'] as $key => $value )
				{
					$xml->xmlcode->{$name}->addAttribute( $key, $value );
				}
			}
			
			# Простой текст
			
			else
			{
				$xml->xmlcode->addChild( $name, $this->parse_xml_value( $txt ) );
			}
		}
		
		if( IN_DEBUG )
		{
			$xml->xmlcode->addChild( "Update_19", $this->parse_xml_value( $this->engine->classes['debug']->debug_info( TRUE ) ) );
		}
		
		//-----------------------------------------
		// Формируем XML документ и выводим его
		//-----------------------------------------
		
		if( $encrypt )
		{
			exit( "<div id='EncodedData'>".base64_encode( $this->fix_xml_cdata( $xml->asXML() ) )."</div>" );
		}
		else 
		{
			exit( $this->fix_xml_cdata( $xml->asXML() ) );
		}
	}
	
	/**
	* Обработка строки для XML
	* 
	* Конвертирует строку в безопасную для добавления внутрь XML тэга.
	* 
	* @param 	string	[opt]	Исходная строка
	*
	* @return	string			Обработанная строка
	*/
	
	function parse_xml_value( $value="" )
	{
		if ( preg_match( "#['\"\[\]<>&]#", $value ) )
		{
			$value = str_replace( "<![CDATA[", "<!#^#|CDATA|", $value );
			$value = str_replace( "]]>"      , "|#^#]>"      , $value );
			
			$value = "<![CDATA[{$value}]]>";
		}
		
		$value = str_replace( "&"	 , "&amp;"		, $value );
		
		return $value;
	}
	
	/**
	* Обработка тэга <![CDATA[...]]>
	* 
	* Конвертирует HTML символы для правильной обработки строки.
	* 
	* @param 	string	[opt]	Исходная строка
	*
	* @return	string			Обработанная строка
	*/
	
	function fix_xml_cdata( $data )
	{
		$find[]		= '&lt;';
		$replace[]	= '<';

		$find[]		= '&gt;';
		$replace[]	= '>';
		
		$find[]		= '&amp;';
		$replace[]	= '&';

		return str_replace( $find, $replace, $data );
	}
	
	/**
	* Проверка наличия обновлений
	* 
	* Отправляет запрос на сайт ados.dini.su для проверки текущей
	* версии, доступной на данный момент.
	* 
	* @param 	bool	Проверка запрошена через AJAX
	* 
	* @return	bool	Текущая версия последняя
	* @return 	array	Сведения об обновлении
	* @return 	string	Ответ с сервера для обработки через AJAX
	*/
	
	function check_for_updates( $ajax=FALSE )
	{
		$params = "checkupdate={$this->engine->config['__engine__']['build']}";
		
		if( $ajax === TRUE ) $params .= "&for=ajax&lang={$this->engine->member['user_lang']}";
		
		//-------------------------------------------------
		// Формируем запрос
		//-------------------------------------------------
		
		$query  = "POST / HTTP/1.1\r\n";
		$query .= "Host: ados.dini.su\r\n";
		$query .= "User-Agent: ADOS/{$this->engine->config['__engine__']['version']} (File Downloading System)\r\n";
		$query .= "Accept: */*\r\n";
		$query .= "Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3\r\n";
		$query .= "Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7\r\n";
		$query .= "Connection: Close\r\n";
		$query .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$query .= "Content-Length: ".strlen( $params )."\r\n";
		$query .= "\r\n";
		$query .= $params;
		
		//-------------------------------------------------
		// Открываем поток
		//-------------------------------------------------
		
		$port = $port ? intval( str_replace( ":", "", $port ) ) : 80;
		
		for( $i = 0; $i <= 3; $i++ )
		{
			if( ( $fp = @fsockopen( "ados.dini.su", 80, $errno, $errstr, 5 ) ) !== FALSE ) break;
		}
		
		//-------------------------------------------------
		// Открыть поток не получилось
		//-------------------------------------------------
	
		if( $fp === FALSE ) return FALSE;
		
		//-------------------------------------------------
		// От сервера не получен ответ
		//-------------------------------------------------
		
		if( stream_set_timeout( $fp, 10 ) === FALSE ) return FALSE;
		
		//-------------------------------------------------
		// Отсылаем запрос
		//-------------------------------------------------
		
		fwrite( $fp, $query );
		
		//-------------------------------------------------
		// Читаем ответ
		//-------------------------------------------------
		
		$get_it = FALSE;
		
		while( !feof( $fp ) )
		{
			$str = fgets( $fp, 1024 );
			
			if( $ajax !== TRUE )
			{
				if( preg_match( "#(jmcGL7BY[A-Za-z0-9\+/=]+)#", $str, $match ) ) break;
			}
			else 
			{
				if( $get_it === FALSE and strpos( $str, "<?xml" ) !== FALSE ) $get_it = TRUE;
				if( $get_it ) $answer .= $str;
			}
		}
		
		fclose( $fp );
		
		//-------------------------------------------------
		// Возвращаем ответ сервера для AJAX
		//-------------------------------------------------
		
		if( $ajax === TRUE ) return $answer;
		
		//-------------------------------------------------
		// Обрабатываем полученный ответ
		//-------------------------------------------------
		
		$update = explode( ",", $this->engine->rc4_decrypt( $match[1], "updater" ) );
		
		preg_match( "#(\d+)\.(\d+)\.(\d+)( ([abr])(\d+))?#", $update[1], $match );
		
		if( $this->engine->config['__current__']['numeric'][0] < $match[1] and 
	 		$this->engine->config['__current__']['numeric'][1] < $match[2] and
	 		$this->engine->config['__current__']['numeric'][2] < $match[3] )
	 	{
			$this->engine->DB->do_update( "settings_list", array( "setting_options" => time()." ".$this->engine->rc4_encrypt( $update[1].",".$update[2], "updater" )." build".$update[3] ), "setting_key='reserved_space'" );
	 		
	 		return array( $update[1], $this->engine->get_date( $update[2], 'MEDIUM' ) );
	 	}
	 	else if( $this->engine->config['__engine__']['build'] < $update[3] )
	 	{
	 		$update[1] .= " - {$this->engine->lang['build']} {$update[3]}";
	 		
	 		$this->engine->DB->do_update( "settings_list", array( "setting_options" => time()." ".$this->engine->rc4_encrypt( $update[1].",".$update[2], "updater" )." build".$update[3] ), "setting_key='reserved_space'" );
	 		
	 		return array( $update[1], $this->engine->get_date( $update[2], 'MEDIUM' ) );
	 	}
	 	
	 	$this->engine->DB->do_update( "settings_list", array( "setting_options" => time()." ".$this->engine->rc4_encrypt( "actual", "updater" ) ), "setting_key='reserved_space'" );
	 	
	 	return TRUE;
	}
}

?>