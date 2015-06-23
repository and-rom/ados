/**
* @package		ADOS - Automatic File Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			AJAX - Функции для страницы закачек
*/

/**
* Идентификаторы выделенных файлов
* 
* @var	string
*/

var active_items	= "";

/**
* Временный идентификатор активного элемента
* 
* @var	int
*/

var temp_id			= null;

/**
* Время последнего обновления состояния
* закачек
* 
* @var	int
*/

var last_reload		= 0;

/**
* Идентификатор таймера обновления
* 
* @var	int
*/

var timer_id		= null;

/**
* Просмотр содержимого группы
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* Изменияет визуальное выделение активной
* группы.
* 
* @param	int		Идентификатор текущей группы
*
* @return	void
*/

function ajax_change_user( id, state )
{
	//--------------------------------------------
	// Проверяем, не хотим ли мы увидеть то, что уже видно
	//--------------------------------------------
	
	if( active_user == id && active_sub == state )
	{
		return;
	}
	
	//--------------------------------------------
	// Изменяем стили текущего и нового активного
	// элемента
	//--------------------------------------------
	
	cur_elem = my_getbyid( "user_"+active_user+"_"+active_sub );
	new_elem = my_getbyid( "user_"+id+"_"+state );
	
	color = cur_elem.style.backgroundColor;
		
	cur_elem.style.backgroundColor = "transparent";
	new_elem.style.backgroundColor = color;
	
	//--------------------------------------------
	// Записываем номер нового активного
	// пользователя и нового активного состояния
	//--------------------------------------------
	
	active_user = id;
	active_sub = state;
	
	cookie = my_getcookie( "list_active" );
	
	list_active = cookie ? cookie.split( "," ) : new Array();
	list_new = new Array();
	
	for( i = 0; i < list_active.length; i++ )
	{
		if( list_active[i].match( /download=-?\d+:\w+/ ) )
		{
			list_new[ list_new.length ] = "download="+id+":"+state;
			var got_it = true;
		}
		else
		{
			list_new[ list_new.length ] = list_active[i];
		}
	}
	
	if( !got_it ) list_new[list_new.length] = "download="+id+":"+state;
	
	my_setcookie( "list_active", list_new.join( "," ), 1 );
	
	//--------------------------------------------
	// Выводим содержимое
	//--------------------------------------------
	
	active_events = "";
	
	ajax_window( "download", "show_items&sub="+state, id );
}

/**
* Изменение состояния пользователя
*
* Изменяет видимость подкатегорий пользователя
* в соответствии с переданным параметром.
* 
* @param	int		Идентификатор пользователя
* @param	bool	Отобразить пдкатегории
*
* @return	void
*/

function ajax_toggle_user( id, show )
{
	span = my_getbyid( "root_"+id );
	
	if( show )
	{
		my_show_div( my_getbyid( 'running_'+id ) );
		my_show_div( my_getbyid( 'paused_'+id ) );
		my_show_div( my_getbyid( 'idle_'+id ) );
		my_show_div( my_getbyid( 'query_'+id ) );
		my_show_div( my_getbyid( 'schedule_'+id ) );
		my_show_div( my_getbyid( 'continue_'+id ) );
		my_show_div( my_getbyid( 'stopped_'+id ) );
		my_show_div( my_getbyid( 'blocked_'+id ) );
		my_show_div( my_getbyid( 'error_'+id ) );
		my_show_div( my_getbyid( 'done_'+id ) );
		
		ajax_hide_user( id, "download", false );
		
		span.innerHTML = "<a href=\"javascript:ajax_toggle_user('"+id+"',false);\"><img src='images/minus3.gif' alt='[-]-' /></a>";
	}
	else
	{
		my_hide_div( my_getbyid( 'running_'+id ) );
		my_hide_div( my_getbyid( 'paused_'+id ) );
		my_hide_div( my_getbyid( 'idle_'+id ) );
		my_hide_div( my_getbyid( 'query_'+id ) );
		my_hide_div( my_getbyid( 'schedule_'+id ) );
		my_hide_div( my_getbyid( 'continue_'+id ) );
		my_hide_div( my_getbyid( 'stopped_'+id ) );
		my_hide_div( my_getbyid( 'blocked_'+id ) );
		my_hide_div( my_getbyid( 'error_'+id ) );
		my_hide_div( my_getbyid( 'done_'+id ) );
		
		ajax_hide_user( id, "download", true );
		
		span.innerHTML = "<a href=\"javascript:ajax_toggle_user('"+id+"',true);\"><img src='images/plus4.gif' alt='[+]-' /></a>";
		
		if( active_user == id && active_sub != 'all' ) ajax_change_user( id, 'all' );
	}
}

/**
* Добавление списка ссылок
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_download_add()
{
	if( active_user < 0 )
	{
		alert( lang_error_wrong_user );
		return;
	}
	
	ajax_window( "download", "download_add", active_user );
}

/**
* Обработка формы со ссылками
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_parse_form()
{
	if( my_getbyid( 'links_list' ).value == "" )
	{
		alert( lang_error_links_list_empty );
		return;
	}
	
	ajax_window( "download", "download_parse"+my_parse_form( 'ajax_form' ), active_user );
}

/**
* Подтверждение добавления ссылок
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_confirm_form()
{
	ajax_window( "download", "download_confirm"+my_parse_form( 'ajax_form' ), active_user );
}

/**
* Обработка следующей ссылки списка
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @param	string	Идентификатор списка
* @param	int		Количество ссылок в списке
*
* @return	void
*/

function ajax_parse_next_link( uid, num )
{
	ajax_window( "download", "download_confirm&uid="+uid+"&cached="+num, active_user );
}

/**
* Показ или скрытие поле с описанием
*
* В зависимости от переданного параметра
* открывает или скрывает поле с описанием
* для файла.
*
* @param	bool	Показать поле
* @param	int		Идентификатор ссылки
*
* @return	void
*/

function ajax_toggle_desc_field( show, id )
{
	var field = my_getbyid( "desc_"+id );
	var lnk	  = my_getbyid( "desc_link_"+id );
	
	var ajax_window	= document.getElementById( 'ajax_window' );
	
	if( show )
	{
		my_show_div( field );
		
		lnk.href = "javascript:ajax_toggle_desc_field(0,'"+id+"')";
		lnk.innerHTML = "<img src='images/desc_close.png' alt='"+lang_desc_field_close+"' title='"+lang_desc_field_close+"' />";
		
		if( my_getbyid( 'can_overflow' ) ) return;
		
		if( ajax_window.currentStyle ) ajax_window.height = ( parseInt( ajax_window.height ) + 35 ) + "px";
		else ajax_window.style.setProperty( 'height', ( parseInt( document.defaultView.getComputedStyle( ajax_window, null ).getPropertyValue( 'height' ) ) + 35 ) + "px", null );
	}
	else
	{
		my_hide_div( field );
		
		lnk.href = "javascript:ajax_toggle_desc_field(1,'"+id+"')";
		lnk.innerHTML = "<img src='images/desc_open.png' alt='"+lang_desc_field_open+"' title='"+lang_desc_field_open+"' />";
		
		if( my_getbyid( 'can_overflow' ) ) return;
		
		if( ajax_window.currentStyle ) ajax_window.height = ( parseInt( ajax_window.height ) - 35 ) + "px";
		else ajax_window.style.setProperty( 'height', ( parseInt( document.defaultView.getComputedStyle( ajax_window, null ).getPropertyValue( 'height' ) ) - 35 ) + "px", null );
	}
}

/**
* Проверка состояния флажков
*
* В зависимости от состояния флажков формы изменяет
* состояние дополнительного флажка.
*
* @param	object	Текущий флажок
*
* @return	void
*/

function ajax_check_links_state( obj )
{
	if( !obj.checked )
	{
		my_getbyid( 'ajax_form' ).link_add_all.checked = false;
		return;
	}
	
	form = my_getbyid( 'ajax_form' );
	
	i = 0;
	var unchecked = false;
	
	while( chbox = eval( "form.link_add_"+i ) )
	{
		if( chbox.checked == false )
		{
			unchecked = true;
			break;
		}
		
		i++;
	}
	
	my_getbyid( 'ajax_form' ).link_add_all.checked = unchecked ? false : true;
}

/**
* Изменение состояния всех флажков
*
* В зависимости от состояния текущего флажка
* меняет состояние всех остальных флажков формы.
*
* @param	object	Текущий флажок
*
* @return	void
*/

function ajax_check_all_links( obj )
{
	state = obj.checked;
	
	form = my_getbyid( 'ajax_form' );
	
	i = 0;
	
	while( chbox = eval( "form.link_add_"+i ) )
	{
		chbox.checked = state;
		i++;
	}
}

/**
* Изменение значения поля 'Относительный путь'
*
* Изменияет значение поля в зависимости от выбранной
* категории для сохранения файла.
*
* @param	object	Текущая категория
*
* @return	void
*/

function ajax_change_path_value( obj )
{
	paths = my_getbyid( "ajax_form" ).hidden_paths;
	
	if( paths && paths.options.length ) for( i = 0; i < paths.options.length; i ++ )
	{
		if( paths.options[ i ].value == obj.value )
		{
			my_getbyid( "ajax_form" ).file_path.value = paths.options[ i ].text;
			
			break;
		}
	}
}

/**
* Свойства файла
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_download_edit()
{
	items_list = active_items.split( "," );
	
	if( items_list.length != 2 )
	{
		alert( lang_error_select_item_edit );
		return;
	}
	
	ajax_window( "download", "download_edit&auser="+active_user+"&asub="+active_sub, items_list[1] );
}

/**
* Изменение состояния закачек
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_download_change_state( state, to_all )
{
	//--------------------------------------------
	// Подтверждение остановки или удаления
	//--------------------------------------------
	
	if( state == 'stop' )
	{
		if( to_all && !confirm( lang_confirm_download_stop_all ) ) return;
		else if( !to_all && active_items.length > 1 && !confirm( lang_confirm_download_stop_multi ) ) return;
	}
	else if( state == 'delete' )
	{
		if( to_all && !confirm( lang_confirm_download_delete_all ) ) return;
		else if( !to_all && active_items.length > 1 && !confirm( lang_confirm_download_delete_multi ) ) return;
	}
	
	//--------------------------------------------
	// Составляем список идентификаторов
	//--------------------------------------------
	
	if( to_all )
	{
		files_list = new Array();
		
		file_node = my_getbyid( "list" ).firstChild; // #text или <table>
		if( file_node.nodeType == 3 ) file_node = file_node.nextSibling // уже точно <table>
		
		file_node = file_node.firstChild; // #text или <tbody>
		if( file_node.nodeType == 3 ) file_node = file_node.nextSibling // уже точно <tbody>
		
		file_node = file_node.firstChild; // <tr>
		
		while( file_node = file_node.nextSibling )
		{
			if( file_node.nodeType == 1 && file_node.id && ( id = file_node.id.match( /^item_(\d+)_row_\d+$/ ) ) ) files_list[ files_list.length ] = id[1];
		}
		
		if( !files_list.length )
		{
			alert( lang_error_list_is_empty );
			return;
		}
		
		items_list = files_list.join( "," );
	}
	else
	{
		items_list = active_items.split( "," );
		
		if( items_list.length < 2 )
		{
			alert( lang_error_select_item_change_state );
			return;
		}
		
		items_list = active_items;
	}
	
	//--------------------------------------------
	// Вызываем AJAX окно
	//--------------------------------------------
	
	ajax_window( "download", "download_change_state&auser="+active_user+"&asub="+active_sub+"&state="+state, items_list );
}

/**
* Обновление списка
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_download_refresh()
{
	if( ajax_window_loaded && my_getbyid( 'file_window' ) ) return false;
	else if( timer_id ) clearInterval( timer_id );
	
	time = Date.parse( new Date() );
	
	if( ( time - last_reload ) < 1000 ) return;
	
	if( my_getbyid( "wait_bar" ).style.display != "none" ) return;
	
	last_reload = time;
	
	ajax_window( "download", "show_items&sub="+active_sub, active_user );
}

/**
* Изменение состояния полей формы
*
* Включает или отключает возможность доступа к полям
* формы свойств закачки, отвечающих за ссылку на файл,
* используемый модуль и выбор ассоциированного события.
*
* @return	void
*/

function ajax_change_field_access( on )
{
	form = my_getbyid( "ajax_form" );
	
	form.file_link.disabled	  = on ? false : true;
	form.file_module.disabled = on ? false : true;
	form.file_event.disabled  = on ? false : true;
}

/**
* Изменить состояние строки
*
* В зависимости от переданного параметра
* включает или выключает подсветку строки.
*
* @param	bool	Включить подсветку
* @param	int		Идентификатор закачки
*
* @return	void
*/

function ajax_toggle_item_row( on, id )
{
	//--------------------------------------------
	// Проверяем, не зажат ли Ctrl
	//--------------------------------------------
	
	if( ctrl_enabled )
	{
		if( on || temp_id )
		{
			ajax_toggle_item_selection( id );
			temp_id = 0;
		}
		
		return;
	}
	
	//--------------------------------------------
	// Изменяем состояние строки
	//--------------------------------------------
	
	temp_id = on ? id : 0;
	
	items_list = active_items.split( "," );
	
	for( i = 0; i < items_list.length; i++ ) if( items_list[ i ] == id )
	{
		return;
	}
	
	num = my_getbyid( "item_"+id+"_row_5" ) ? ( on ? 7 : 5 ) : ( on ? 8 : 6 );
	
	if( user_cell  = my_getbyid( "item_"+id+"_user"  ) ) user_cell.className  = "row"+num;
	if( state_cell = my_getbyid( "item_"+id+"_state" ) ) state_cell.className = "row"+num;
	
	my_getbyid( "item_"+id+"_priority" ).className = "row"+num;
	my_getbyid( "item_"+id+"_name" ).className = "row"+num;
	my_getbyid( "item_"+id+"_size" ).className = "row"+num;
	my_getbyid( "item_"+id+"_left" ).className = "row"+num;
	my_getbyid( "item_"+id+"_time" ).className = "row"+num;
}

/**
* Изменить состояние закачки
*
* Проверяет, внесена ли закачка в список активных.
* Если да, то убирает ее из списка; если нет,
* то вносит в список.
*
* @param	int		Идентификатор закачки
*
* @return	void
*/

function ajax_toggle_item_selection( id )
{
	new_list   = new Array();
	
	items_list = active_items.split( "," );
	
	var get_it = false;
	
	for( i = 0; i < items_list.length; i++ )
	{
		if( items_list[ i ] == id )
		{
			get_it = true;
		}
		else
		{
			new_list[ new_list.length ] = items_list[i];
		}
	}
	
	if( !get_it ) new_list[ new_list.length ] = id;
	
	num = my_getbyid( "item_"+id+"_row_5" ) ? ( get_it ? 5 : 7 ) : ( get_it ? 6 : 8 );
		
	if( user_cell  = my_getbyid( "item_"+id+"_user"  ) ) user_cell.className  = "row"+num;
	if( state_cell = my_getbyid( "item_"+id+"_state" ) ) state_cell.className = "row"+num;
	if( state_cell = my_getbyid( "item_"+id+"_state" ) ) state_cell.className = "row"+num;
		
	my_getbyid( "item_"+id+"_priority" ).className = "row"+num;
	my_getbyid( "item_"+id+"_name" ).className = "row"+num;
	my_getbyid( "item_"+id+"_size" ).className = "row"+num;
	my_getbyid( "item_"+id+"_left" ).className = "row"+num;
	my_getbyid( "item_"+id+"_time" ).className = "row"+num;
	
	active_items = new_list.join( "," );
}

/**
* Повторное выделение закачек
*
* Заново выделяет ранее выделенные закачки, которые
* все еще находятся в списке.
*
* @return	void
*/

function ajax_reselect_items()
{
	//--------------------------------------------
	// Выделяем уже выделенные ранее закачки
	//--------------------------------------------
	
	items = active_items.split( "," );
	
	active_items = "";
	
	for( i = 0; i < items.length; i++ )
	{
		if( items[i] ) ajax_toggle_item_selection( items[i] );
	}
}

/**
* Сортировка закачек
*
* Записывает в cookie параметры сортировки
* закачек в списках для указанной секции.
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @param	string	Название поля
* @param	string	Тип сортировки
*
* @return	void
*/

function ajax_sort_items( id, type )
{
	sorting = new Array();
	new_sorting = new Array();
	
	sort_params = my_getcookie( "sort_params" );
	
	if( sort_params ) sorting = sort_params.split( "," );
	
	for( i = 0; i < sorting.length; i ++ )
	{
		if( sorting[i] && !sorting[i].match( /tab_download_(user|name|size|left|time|state|priority)=(asc|desc)/ ) ) new_sorting[ new_sorting.length ] = sorting[i];
	}
	
	new_sorting[ new_sorting.length ] = "tab_download_"+id+"="+type;
	
	my_setcookie( "sort_params", new_sorting.join( "," ), 1 );
	
	ajax_window( "download", "show_items&sub="+active_sub, active_user );
}