/**
* @package		ADOS - Automatic File Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			AJAX - Функции для страницы категорий
*/

/**
* Режим перемещения категории
* 
* @var	bool
*/

var cat_moving		= false;

/**
* Режим перемещения файлов
* 
* @var	bool
*/

var file_moving		= false;

/**
* Идентификаторы выделенных файлов
* 
* @var	string
*/

var active_files	= "";

/**
* Вывод дочерних категорий
*
* Выводит скрытые дочерние категории данной
* категории.
* 
* @param	int		Идентификатор текущей категории
* @param	int		Идентификатор пользователя - владельца категории
*
* @return	void
*/

function ajax_cat_show( cid, uid )
{
	elem = my_getbyid( "root_"+uid );
	
	var re = /cat_(\d+)_level_(\d+)/;
	
	//--------------------------------------------
	// Ищем текущую категорию
	//--------------------------------------------
	
	if( cid != 0 )
	{
		while( elem = elem.nextSibling )
		{
			if( elem.nodeType == 1 && elem.id && ( params = elem.id.match( re ) ) && params[1] == cid ) break;
		}
		
		if( !params ) return;
		
		current = elem;
		level = params[2];
	}
	else
	{		
		current = elem;
		level = 0;
	}
	
	//--------------------------------------------
	// Показываем дочерние категории
	//--------------------------------------------
	
	hidden = ajax_get_hidden_cats();
	hide_down = 0;
	
	var got_it = false;
	
	while( elem = elem.nextSibling )
	{
		if( elem.nodeType == 1 && elem.id && ( params = elem.id.match( re ) ) )
		{
			got_it = true;
			
			//--------------------------------------------
			// Проверяем, нужно ли показывать дочерние категории
			//--------------------------------------------
			
			if( params[2] == hide_down ) hide_down = 0;
		
			if( hide_down == 0 ) for( i = 0 ; i < hidden.length; i++ )
			{
				if( hidden[i] == params[1] )
				{
					hide_down = params[2];
					break;
				}
			}
			
			//--------------------------------------------
			// Показываем категорию, если позволяют условия
			//--------------------------------------------
			
			if( params[2] > level && ( hide_down == 0 || params[2] <= hide_down ) ) my_show_div( my_getbyid( "cat_"+params[1]+"_level_"+params[2] ) );
			else if( params[2] <= level ) break;
		}
		else if( elem.nodeType == 1 && elem.id && cid == 0 && elem.id.match( /root_\d+/ ) )
		{
			break;
		}
	}
	
	//--------------------------------------------
	// Меняем состояние текущей категории
	//--------------------------------------------
	
	if( cid != 0 )
	{
		children = current.childNodes;
		
		if( children[0].id && children[0].id == 'active_cat' )
		{
			children = children[0].childNodes;
		}
		
		if( children[1].id && children[1].id == 'active_cat' )
		{
			children = children[1].childNodes;
		}
		
		re = /itm_(\d+)_(\d+)/;
		
		for( var i = 0; i < children.length; i++ )
		{		
			if( children[i].id && ( params = children[i].id.match( re ) ) && params[1] == cid ) break;
		}
		
		if( !params ) return;
	}
	else
	{
		if( !got_it ) return;
		
		var i = 0
		
		children = new Array();
		children[0] = my_getbyid( "itm_"+uid+"_4" );
		
		params = new Array();
		params[0] = params[1] = null;
		params[2] = 3;
		
		ajax_hide_user( uid, "cat", false );
	}
	
	children[i].innerHTML = "<a href=\"javascript:ajax_cat_hide('"+cid+"','"+uid+"');\"><img src='images/minus"+params[2]+".gif' alt='[+]-' /></a>";
	
	ajax_toggle_category( cid, uid, false );
}

/**
* Скрытие дочерних категорий
*
* Скрывает отображенные дочерние категории
* данной категории.
* 
* @param	int		Идентификатор текущей категории
* @param	int		Идентификатор пользователя - владельца категории
*
* @return	void
*/

function ajax_cat_hide( cid, uid )
{
	elem = my_getbyid( "root_"+uid );
	
	var re = /cat_(\d+)_level_(\d+)/;
	
	//--------------------------------------------
	// Ищем текущую категорию
	//--------------------------------------------
	
	if( cid != 0 )
	{
		while( elem = elem.nextSibling )
		{
			if( elem.nodeType == 1 && elem.id && ( params = elem.id.match( re ) ) && params[1] == cid ) break;
		}
		
		if( !params ) return;
		
		current = elem;
		level = params[2];
	}
	else
	{		
		current = elem;
		level = 0;
	}
	
	//--------------------------------------------
	// Скрываем дочерние категории
	//--------------------------------------------
	
	while( elem = elem.nextSibling )
	{
		if( elem.nodeType == 1 && elem.id && ( params = elem.id.match( re ) ) )
		{
			if( params[2] > level )
			{
				my_hide_div( my_getbyid( "cat_"+params[1]+"_level_"+params[2] ) );
				
				if( active_cat == params[1] ) ajax_show_contents( cid, uid );
			}
			else break;
		}
		else if( elem.nodeType == 1 && elem.id && cid == 0 && elem.id.match( /root_\d+/ ) )
		{
			break;
		}
	}
	
	//--------------------------------------------
	// Меняем состояние текущей категории
	//--------------------------------------------
	
	if( cid != 0 )
	{
		children = current.childNodes;
		
		if( children[0].id && children[0].id == 'active_cat' )
		{
			children = children[0].childNodes;
		}
		
		if( children[1].id && children[1].id == 'active_cat' )
		{
			children = children[1].childNodes;
		}
		
		re = /itm_(\d+)_(\d+)/;
		
		for( var i = 0; i < children.length; i++ )
		{		
			if( children[i].id && ( params = children[i].id.match( re ) ) && params[1] == cid ) break;
		}
		
		if( !params ) return;
	}
	else
	{
		var i = 0
		
		children = new Array();
		children[0] = my_getbyid( "itm_"+uid+"_4" );
		
		params = new Array();
		params[0] = params[1] = null;
		params[2] = 4;
		
		ajax_hide_user( uid, "cat", true );
	}
	
	children[i].innerHTML = "<a href=\"javascript:ajax_cat_show('"+cid+"','"+uid+"');\"><img src='images/plus"+params[2]+".gif' alt='[+]-' /></a>";
	
	ajax_toggle_category( cid, uid, true );
}

/**
* Запись состояния категории.
*
* Добавляет или обновляет запись о состоянии
* категории в cookie и в локальную переменную.
* 
* @param	int		Идентификатор текущей категории
* @param	int		Идентификатор пользователя
* @param	bool	Добавить в список
*
* @return	void
*/

function ajax_toggle_category( cid, uid, add )
{
	if( cid == 0 ) cid = "root_" + uid;
	
	parsed = new Array();
	
	saved = ajax_get_hidden_cats();
	
	//--------------------------------------------
	// Удаляем запись, если она есть
	//--------------------------------------------
	
	for( i = 0; i < saved.length; i++ )
	{
		if ( saved[i] != cid && saved[i] != "" )
		{
			parsed[parsed.length] = saved[i];
		}
	}
	
	//--------------------------------------------
	// Добавляем запись заново, если необходимо
	//--------------------------------------------
	
	if( add )
	{
		parsed[ parsed.length ] = cid;
	}
	
	my_setcookie( 'hidden_cats', parsed.join( ',' ), 1 );
	
	hidden_cats = clean.join( ',' );
}

/**
* Получение списка скрытых категорий.
*
* Пытается считать из cookie и из локальной переменной
* список идентификаторов скрытых категорий, который
* затем преобразует в массив.
*
* @return	array	Список идентификаторов скрытых категорий
*/

function ajax_get_hidden_cats()
{
	saved = new Array();
	
	//--------------------------------------------
	// Считываем сохраненную информацию
	//--------------------------------------------
	
	if( ( tmp = my_getcookie( 'hidden_cats' ) ) || ( tmp = hidden_cats ) )
	{
		saved = tmp.split( "," );
	}
	
	return saved;
}

/**
* Просмотр содержимого категории
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* Изменияет визуальное выделение активной
* категории.
* 
* @param	int		Идентификатор текущей категории
* @param	int		Идентификатор пользователя
*
* @return	void
*/

function ajax_show_contents( id, uid )
{
	if( id == 0 ) id = "root_" + uid;
	
	//--------------------------------------------
	// Проверяем, не хотим ли мы увидеть то, что уже видно
	//--------------------------------------------
	
	if( active_cat == id )
	{
		return;
	}
	
	//--------------------------------------------
	// Проверяем, не хотим ли мы переместить категорию
	//--------------------------------------------
	
	if( cat_moving )
	{
		ajax_category_move( id );
		return;
	}
	
	//--------------------------------------------
	// Проверяем, не хотим ли мы переместить файлы
	//--------------------------------------------
	
	if( file_moving )
	{
		ajax_file_move( id );
		return;
	}
	
	//--------------------------------------------
	// Изменяем стили текущей и новой активной категории
	//--------------------------------------------
	
	cur_dir = my_getbyid( "link_"+active_cat );
	new_dir = my_getbyid( "link_"+id );
	
	color = cur_dir.style.backgroundColor;
		
	cur_dir.style.backgroundColor = "transparent";
	new_dir.style.backgroundColor = color;
	
	//--------------------------------------------
	// Записываем номер новой активной категории
	//--------------------------------------------
	
	active_cat = id;
	
	cookie = my_getcookie( "list_active" );
	
	list_active = cookie ? cookie.split( "," ) : new Array();
	list_new = new Array();
	
	for( i = 0; i < list_active.length; i++ )
	{
		if( list_active[i].match( /cat=\w+/ ) )
		{
			list_new[ list_new.length ] = "cat="+id;
			var got_it = true;
		}
		else
		{
			list_new[ list_new.length ] = list_active[i];
		}
	}
	
	if( !got_it ) list_new[list_new.length] = "cat="+id;
	
	my_setcookie( "list_active", list_new.join( "," ), 1 );
	
	//--------------------------------------------
	// Выводим содержимое
	//--------------------------------------------
	
	ajax_window( "categories", "show_contents", id );
}

/**
* Добавление категории
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_category_add()
{
	if( my_getbyid( "cat_"+active_cat+"_level_5" ) )
	{
		alert( lang_error_last_level );
		return;
	}
	
	ajax_window( "categories", "category_add", active_cat );
}

/**
* Свойства категории
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_category_edit()
{
	re = /root_\d+/;
	
	if( active_cat.match( re ) )
	{
		alert( lang_error_cant_edit_root );
	}
	else
	{
		ajax_window( "categories", "category_edit", active_cat );
	}
}

/**
* Применение свойств
*
* Обновляет свойства текущей категории.
*
* @param	int		Идентификатор категории
* @param	string	Тип действия
*
* @return	void
*/

function ajax_apply_params( cid, action )
{
	var query = "&apply=1";
	
	query += my_parse_form( "ajax_form" );
	
	ajax_window( "categories", "category_"+action+query, cid );
}

/**
* Удаление категории
*
* Выводит запрос на удаление текущей
* категории и всех ее подкатегорий.
* В случае подтверждения запроса
* запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_category_delete()
{
	re = /root_\d+/;
	
	if( active_cat.match( re ) )
	{
		alert( lang_error_cant_delete_root );
	}
	else if( confirm( lang_confirm_delete_cat ) )
	{
		re = /cat_(\d+)_level_\d+/;
		
		current_cat = active_cat;
		
		//--------------------------------------------
		// Выходим на текущую категорию
		//--------------------------------------------
		
		parent_cat = my_getbyid( "link_"+current_cat );
		
		while( parent_cat = parent_cat.parentNode )
		{
			if( parent_cat.id && ( cid = parent_cat.id.match( re ) ) && cid[1] == current_cat ) break;
		}
		
		//--------------------------------------------
		// Получаем номер родительской категории
		//--------------------------------------------
		
		re_root = /root_(\d+)/;
		re_cat  = /cat_(\d+)_level_\d+/;
		
		var root = "";
		var cat  = "";
		
		while( parent_cat = parent_cat.previousSibling )
		{
			if( parent_cat.nodeType == 1 && parent_cat.id && ( ( root = parent_cat.id.match( re_root ) ) || ( cat = parent_cat.id.match( re_cat ) ) ) ) break;
		}
		
		//--------------------------------------------
		// Обновляем идентификатор активной категории
		//--------------------------------------------
		
		active_cat = parent_cat.id;
		
		if( root )
		{
			active_cat = "root_" + root[1];
		}
		else if( cat )
		{
			active_cat = cat[1];
		}
	
		cookie = my_getcookie( "list_active" );
		
		list_active = cookie ? cookie.split( "," ) : new Array();
		list_new = new Array();
		
		for( i = 0; i < list_active.length; i++ )
		{
			if( list_active[i].match( /cat=\w+/ ) )
			{
				list_new[ list_new.length ] = "cat="+active_cat;
				var got_it = true;
			}
			else
			{
				list_new[ list_new.length ] = list_active[i];
			}
		}
		
		if( !got_it ) list_new[list_new.length] = "cat="+active_cat;
		
		my_setcookie( "list_active", list_new.join( "," ), 1 );
		
		//--------------------------------------------
		// Удаляем категорию
		//--------------------------------------------
		
		ajax_window( "categories", "category_delete", current_cat );
	}
	
	active_files = "";
	
	cat_moving = false;
	
	my_getbyid( "cat_moving" ).style.backgroundColor = "";
}

/**
* Изменение статуса перемещения категории
*
* Переключает текущий статус активности
* перемещения категории.
*
* @return	void
*/

function ajax_toggle_cat_move()
{
	if( file_moving )
	{
		alert( lang_error_cant_move_cat );
		return;
	}
	
	if( cat_moving )
	{
		cat_moving = false;
	
		my_getbyid( "cat_moving" ).style.backgroundColor = "";
	}
	else
	{
		cat_moving = true;
		
		my_getbyid( "cat_moving" ).style.backgroundColor = "#808080";
	}
}

/**
* Перемещение категории
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @param	int		Идентификатор родительской категории
*
* @return	void
*/

function ajax_category_move( id )
{
	if( active_cat.match( /root_\d+/ ) )
	{
		alert( lang_error_cant_move_root );
		return;
	}
	
	if( active_cat == id )
	{
		return;
	}
	
	if( !id.match( /root_\d+/ ) )
	{
		re = /cat_(\d+)_level_(\d+)/;
			
		//--------------------------------------------
		// Выходим на родительскую категорию
		//--------------------------------------------
		
		parent_cat = my_getbyid( "link_"+id );
		
		while( parent_cat = parent_cat.parentNode )
		{
			if( parent_cat.id && ( cat = parent_cat.id.match( re ) ) && cat[1] == id ) break;
		}
		
		//--------------------------------------------
		// Проверяем уровень
		//--------------------------------------------
		
		if( cat[2] == 5 )
		{
			alert( lang_error_last_level_move );
			return;
		}
	}
	
	//--------------------------------------------
	// Выполняем запрос на добавление
	//--------------------------------------------
	
	ajax_window( "categories", "category_move"+"&to="+id, active_cat );
}

/**
* Изменить состояние строки
*
* В зависимости от переданного параметра
* включает или выключает подсветку строки.
*
* @param	bool	Включить подсветку
* @param	int		Идентификатор файла
*
* @return	void
*/

function ajax_toggle_file_row( on, id )
{
	//--------------------------------------------
	// Проверяем, не зажат ли Ctrl
	//--------------------------------------------
	
	if( ctrl_enabled )
	{
		if( on || temp_id )
		{
			ajax_toggle_file_selection( id );
			temp_id = 0;
		}
		
		return;
	}
	
	//--------------------------------------------
	// Изменяем состояние строки
	//--------------------------------------------
	
	temp_id = on ? id : 0;
	
	files_list = active_files.split( "," );
	
	for( i = 0; i < files_list.length; i++ ) if( files_list[ i ] == id )
	{
		return;
	}
	
	num = my_getbyid( "file_"+id+"_row_5" ) ? ( on ? 7 : 5 ) : ( on ? 8 : 6 );
	
	my_getbyid( "file_"+id+"_state" ).className = "row"+num;
	my_getbyid( "file_"+id+"_name"  ).className = "row"+num;
	my_getbyid( "file_"+id+"_added" ).className = "row"+num;
	my_getbyid( "file_"+id+"_size"  ).className = "row"+num;
}

/**
* Изменить состояние файла
*
* Проверяет, внесен ли файл в список активных.
* Если да, то убирает его из списка; если нет,
* то вносит в список.
*
* @param	int		Идентификатор файла
*
* @return	void
*/

function ajax_toggle_file_selection( id )
{
	if( !my_getbyid( "file_"+id+"_state" ) ) return;
	
	new_list   = new Array();
	
	files_list = active_files.split( "," );
	
	var get_it = false;
	
	for( i = 0; i < files_list.length; i++ )
	{
		if( files_list[ i ] == id )
		{
			get_it = true;
		}
		else
		{
			new_list[ new_list.length ] = files_list[i];
		}
	}
	
	if( !get_it ) new_list[ new_list.length ] = id;
	
	num = my_getbyid( "file_"+id+"_row_5" ) ? ( get_it ? 5 : 7 ) : ( get_it ? 6 : 8 );
	
	my_getbyid( "file_"+id+"_state" ).className = "row"+num;
	my_getbyid( "file_"+id+"_name"  ).className = "row"+num;
	my_getbyid( "file_"+id+"_added" ).className = "row"+num;
	my_getbyid( "file_"+id+"_size"  ).className = "row"+num;
	
	active_files = new_list.join( "," );
	
	if( new_list.length == 1 && file_moving )
	{
		file_moving = false;
	
		my_getbyid( "file_moving" ).style.backgroundColor = "";
	}
}

/**
* Информация о файле
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_file_edit()
{
	files_list = active_files.split( "," );
	
	if( files_list.length != 2 )
	{
		alert( lang_error_select_file_edit );
		return;
	}
	
	ajax_window( "categories", "file_info", files_list[1] );
}

/**
* Удаление файлов
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_file_delete()
{
	files_list = active_files.split( "," );
	
	if( files_list.length < 2 )
	{
		alert( lang_error_select_file_delete );
		return;
	}
	else if( confirm( lang_confirm_file_delete ) )
	{
		ajax_window( "categories", "file_delete&cat="+active_cat, active_files );
	}
}

/**
* Изменение статуса перемещения файлов
*
* Переключает текущий статус активности
* перемещения файлов.
*
* @return	void
*/

function ajax_toggle_file_move()
{
	files_list = active_files.split( "," );
	
	if( files_list.length < 2 )
	{
		alert( lang_error_select_file_move );
		return;
	}
	else if( cat_moving )
	{
		alert( lang_error_cant_move_file );
		return;
	}
	
	if( file_moving )
	{
		file_moving = false;
	
		my_getbyid( "file_moving" ).style.backgroundColor = "";
	}
	else
	{
		file_moving = true;
		
		my_getbyid( "file_moving" ).style.backgroundColor = "#808080";
	}
}

/**
* Перемещение файлов
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @param	int		Идентификатор родительской категории
*
* @return	void
*/

function ajax_file_move( id )
{
	if( active_cat == id )
	{
		return;
	}
	
	ajax_window( "categories", "file_move"+"&to="+id+"&from="+active_cat, active_files );
}

/**
* Повторное выделение файлов
*
* Заново выделяет ранее выделенные файлы, которые
* все еще находятся в списке.
*
* @return	void
*/

function ajax_reselect_files()
{
	//--------------------------------------------
	// Выделяем уже выделенные ранее файлы
	//--------------------------------------------
	
	files = active_files.split( "," );
	
	active_files = "";
	
	for( i = 0; i < files.length; i++ )
	{
		if( files[i] ) ajax_toggle_file_selection( files[i] );
	}
	
	//--------------------------------------------
	// Отключаем режим перемещения файлов
	//--------------------------------------------
	
	if( active_files == "" || active_files == "," )
	{
		file_moving = false;
	
		my_getbyid( "file_moving" ).style.backgroundColor = "";
	}
}

/**
* Сортировка файлов
*
* Записывает в cookie параметры сортировки
* файлов в списках для указанной секции.
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @param	string	Название поля
* @param	string	Тип сортировки
*
* @return	void
*/

function ajax_sort_files( id, type )
{
	sorting = new Array();
	new_sorting = new Array();
	
	sort_params = my_getcookie( "sort_params" );
	
	if( sort_params ) sorting = sort_params.split( "," );
	
	for( i = 0; i < sorting.length; i ++ )
	{
		if( sorting[i] && !sorting[i].match( /tab_categories_(state|name|added|size)=(asc|desc)/ ) ) new_sorting[ new_sorting.length ] = sorting[i];
	}
	
	new_sorting[ new_sorting.length ] = "tab_categories_"+id+"="+type;
	
	my_setcookie( "sort_params", new_sorting.join( "," ), 1 );
	
	ajax_window( "categories", "show_contents", active_cat );
}