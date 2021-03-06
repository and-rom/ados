/**
* @package		ADOS - Automatic File Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			AJAX - Функции для страницы расписания
*/

/**
* Идентификаторы выделенных событий
* 
* @var	string
*/

var active_events = "";

/**
* Просмотр списка событий
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* Изменияет визуальное выделение активного
* элемента.
* 
* @param	int		Идентификатор текущего пользователя
* @param	string	Идентификатор временного интервала
*
* @return	void
*/

function ajax_change_user( id, time )
{
	//--------------------------------------------
	// Проверяем, не хотим ли мы увидеть то, что уже видно
	//--------------------------------------------
	
	if( active_user == id && active_sub == time )
	{
		return;
	}
	
	//--------------------------------------------
	// Изменяем стили текущего и нового активного
	// элемента
	//--------------------------------------------
	
	cur_elem = my_getbyid( "user_"+active_user+"_"+active_sub );
	new_elem = my_getbyid( "user_"+id+"_"+time );
	
	color = cur_elem.style.backgroundColor;
		
	cur_elem.style.backgroundColor = "transparent";
	new_elem.style.backgroundColor = color;
	
	//--------------------------------------------
	// Записываем номер нового активного
	// пользователя и нового активного интервала
	//--------------------------------------------
	
	active_user = id;
	active_sub = time;
	
	cookie = my_getcookie( "list_active" );
	
	list_active = cookie ? cookie.split( "," ) : new Array();
	list_new = new Array();
	
	for( i = 0; i < list_active.length; i++ )
	{
		if( list_active[i].match( /schedule=-?\d+:\w+/ ) )
		{
			list_new[ list_new.length ] = "schedule="+id+":"+time;
			var got_it = true;
		}
		else
		{
			list_new[ list_new.length ] = list_active[i];
		}
	}
	
	if( !got_it ) list_new[list_new.length] = "schedule="+id+":"+time;
	
	my_setcookie( "list_active", list_new.join( "," ), 1 );
	
	//--------------------------------------------
	// Выводим содержимое
	//--------------------------------------------
	
	active_events = "";
	
	ajax_window( "schedule", "show_events&sub="+time, id );
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
		my_show_div( my_getbyid( 'pall_'+id ) );
		my_show_div( my_getbyid( 'pmonth_'+id ) );
		my_show_div( my_getbyid( 'pweek_'+id ) );
		my_show_div( my_getbyid( 'pday_'+id ) );
		my_show_div( my_getbyid( 'today_'+id ) );
		my_show_div( my_getbyid( 'nday_'+id ) );
		my_show_div( my_getbyid( 'nweek_'+id ) );
		my_show_div( my_getbyid( 'nmonth_'+id ) );
		my_show_div( my_getbyid( 'nall_'+id ) );
		
		ajax_hide_user( id, "schedule", false );
		
		span.innerHTML = "<a href=\"javascript:ajax_toggle_user('"+id+"',false);\"><img src='images/minus3.gif' alt='[-]-' /></a>";
	}
	else
	{
		my_hide_div( my_getbyid( 'pall_'+id ) );
		my_hide_div( my_getbyid( 'pmonth_'+id ) );
		my_hide_div( my_getbyid( 'pweek_'+id ) );
		my_hide_div( my_getbyid( 'pday_'+id ) );
		my_hide_div( my_getbyid( 'today_'+id ) );
		my_hide_div( my_getbyid( 'nday_'+id ) );
		my_hide_div( my_getbyid( 'nweek_'+id ) );
		my_hide_div( my_getbyid( 'nmonth_'+id ) );
		my_hide_div( my_getbyid( 'nall_'+id ) );
		
		ajax_hide_user( id, "schedule", true );
		
		span.innerHTML = "<a href=\"javascript:ajax_toggle_user('"+id+"',true);\"><img src='images/plus4.gif' alt='[+]-' /></a>";
		
		if( active_user == id && active_sub != 'all' ) ajax_change_user( id, 'all' );
	}
}

/**
* Просмотр списка временных ограничений
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_show_limits()
{
	if( active_user == -1 )
	{
		alert( lang_error_select_user );
		return;
	}
	
	ajax_window( "schedule", "show_limits", active_user );
}

/**
* Изменить состояние строки
*
* В зависимости от переданного параметра
* включает или выключает подсветку строки.
*
* @param	bool	Включить подсветку
* @param	int		Идентификатор события
*
* @return	void
*/

function ajax_toggle_event_row( on, id )
{
	//--------------------------------------------
	// Проверяем, не зажат ли Ctrl
	//--------------------------------------------
	
	if( ctrl_enabled )
	{
		if( on || temp_id )
		{
			ajax_toggle_event_selection( id );
			temp_id = 0;
		}
		
		return;
	}
	
	//--------------------------------------------
	// Изменяем состояние строки
	//--------------------------------------------
	
	temp_id = on ? id : 0;
	
	events_list = active_events.split( "," );
	
	for( i = 0; i < events_list.length; i++ ) if( events_list[ i ] == id )
	{
		return;
	}
	
	num = my_getbyid( "event_"+id+"_row_5" ) ? ( on ? 7 : 5 ) : ( on ? 8 : 6 );
	
	if( user_cell = my_getbyid( "event_"+id+"_user"  ) ) user_cell.className = "row"+num;
		
	my_getbyid( "event_"+id+"_time"  ).className = "row"+num;
	my_getbyid( "event_"+id+"_type"  ).className = "row"+num;
	my_getbyid( "event_"+id+"_state" ).className = "row"+num;
}

/**
* Изменить состояние события
*
* Проверяет, внесено ли событие в список активных.
* Если да, то убирает его из списка; если нет,
* то вносит в список.
*
* @param	int		Идентификатор события
*
* @return	void
*/

function ajax_toggle_event_selection( id )
{
	new_list   = new Array();
	
	events_list = active_events.split( "," );
	
	var get_it = false;
	
	for( i = 0; i < events_list.length; i++ )
	{
		if( events_list[ i ] == id )
		{
			get_it = true;
		}
		else
		{
			new_list[ new_list.length ] = events_list[i];
		}
	}
	
	if( !get_it ) new_list[ new_list.length ] = id;
	
	num = my_getbyid( "event_"+id+"_row_5" ) ? ( get_it ? 5 : 7 ) : ( get_it ? 6 : 8 );
	
	if( user_cell = my_getbyid( "event_"+id+"_user"  ) ) user_cell.className = "row"+num;
		
	my_getbyid( "event_"+id+"_time"  ).className = "row"+num;
	my_getbyid( "event_"+id+"_type"  ).className = "row"+num;
	my_getbyid( "event_"+id+"_state" ).className = "row"+num;
	
	active_events = new_list.join( "," );
}

/**
* Добавление события
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_event_add()
{
	if( active_user == -1 )
	{
		alert( lang_error_wrong_sub );
		return;
	}
	
	ajax_window( "schedule", "event_add&auser="+active_user+"&asub="+active_sub, 0 );
}

/**
* Применение параметров события
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @param	int		Идентификатор события
* @param	string	Тип действия
*
* @return	void
*/

function ajax_event_apply( id, type )
{
	query = my_parse_form( "ajax_form" );
	
	ajax_window( "schedule", "event_"+type+"&apply=1&auser="+active_user+"&asub="+active_sub+query, id );
}

/**
* Информация о событии
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_event_edit()
{
	events_list = active_events.split( "," );
	
	if( events_list.length != 2 )
	{
		alert( lang_error_select_event_edit );
		return;
	}
	
	ajax_window( "schedule", "event_edit&auser="+active_user+"&asub="+active_sub, events_list[1] );
}

/**
* Удаление событий
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_event_delete()
{
	events_list = active_events.split( "," );
	
	if( events_list.length < 2 )
	{
		alert( lang_error_select_event_delete );
		return;
	}
	else if( confirm( lang_confirm_event_delete ) )
	{
		ajax_window( "schedule", "event_delete&auser="+active_user+"&asub="+active_sub, active_events );
	}
}

/**
* Показ (скрытие) свойств события
*
* В зависимости от переданного параметра показывает
* и скрывает свойства события, зависящие от его типа.
*
* @param	bool	Показать свойства чередования
*
* @return	void
*/

function ajax_toggle_params( show )
{
	if( show )
	{
		my_hide_div( my_getbyid( "date_standard" ) );
		my_show_div( my_getbyid( "date_interlaced" ) );
	}
	else
	{
		my_hide_div( my_getbyid( "date_interlaced" ) );
		my_show_div( my_getbyid( "date_standard" ) );
	}
}

/**
* Повторное выделение событий
*
* Заново выделяет ранее выделенные события, которые
* все еще находятся в списке.
*
* @return	void
*/

function ajax_reselect_events()
{
	//--------------------------------------------
	// Выделяем уже выделенные ранее события
	//--------------------------------------------
	
	events = active_events.split( "," );
	
	active_events = "";
	
	for( i = 0; i < events.length; i++ )
	{
		if( events[i] ) ajax_toggle_event_selection( events[i] );
	}
}

/**
* Сортировка событий
*
* Записывает в cookie параметры сортировки
* событий в списках для указанного промежутка
* времени.
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @param	string	Название поля
* @param	string	Тип сортировки
*
* @return	void
*/

function ajax_sort_events( id, type )
{
	sorting = new Array();
	new_sorting = new Array();
	
	sort_params = my_getcookie( "sort_params" );
	
	if( sort_params ) sorting = sort_params.split( "," );
	
	for( i = 0; i < sorting.length; i ++ )
	{
		if( sorting[i] && !sorting[i].match( /tab_schedule_(user|time|type|state)=(asc|desc)/ ) ) new_sorting[ new_sorting.length ] = sorting[i];
	}
	
	new_sorting[ new_sorting.length ] = "tab_schedule_"+id+"="+type;
	
	my_setcookie( "sort_params", new_sorting.join( "," ), 1 );
	
	ajax_window( "schedule", "show_events&sub="+active_sub, active_user );
}