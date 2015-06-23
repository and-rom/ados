/**
* @package		ADOS - Automatic File Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			AJAX - Функции для страницы расписания (только для администраторов)
*/

/**
* Идентификатор следующего параметра
* 
* @var	int
*/

var next_id	= 0;

/**
* Изменение параметров расписания для пользователя
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_show_params()
{
	ajax_window( "schedule", "show_params", 0 );
}

/**
* Применение параметров расписания для пользователя
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_apply_params()
{
	query = my_parse_form( "ajax_form" );
	
	ajax_window( "schedule", "apply_params&auser="+active_user+"&asub="+active_sub+query, 0 );
}

/**
* Добавление ряда
*
* Добавляет в таблицу новый ряд для заполнения
* параметров ограничений, если текущий ряд уже заполнен.
* 
* @param	obj		Текущий список пользователей
* @param	int		Идентификатор для добавляемых полей
* @param	bool	Добавить единовременное ограничение
*
* @return	void
*/

function ajax_add_list_row( obj, num, std )
{
	//--------------------------------------------
	// Проверяем значения в списке
	//--------------------------------------------
	
	var get_it = false;
	var len = obj.options.length;
	
	for( i = 0; i < len; i++ ) if( obj.options[i].selected )
	{
		var get_it = true;
		break;
	}
	
	if( !get_it ) return;
	
	//--------------------------------------------
	// Определяем количество рядов в таблице
	//--------------------------------------------
	
	tbl = my_getbyid( "ajax_table_" + ( std ? "standard" : "interlaced" ) );
	row = std ? tbl.rows.length : ( tbl.rows.length - 1 );
	
	//--------------------------------------------
	// Проверяем значения в списке в последнем ряду
	//--------------------------------------------
	
	obj = tbl.rows[row-1].cells[0].getElementsByTagName('SELECT').item(0);
	
	get_it = false;
	len = obj.options.length;
	
	for( i = 0; i < len; i++ ) if( obj.options[i].selected )
	{
		var get_it = true;
		break;
	}
	
	if( !get_it ) return;
	
	//--------------------------------------------
	// Вставляем новый ряд
	//--------------------------------------------
	
	num = next_id ? next_id : num;
	next_id = next_id ? ( next_id + 1 ) : ( num + 1 );
	
	tr = tbl.insertRow( row );
	tr.insertCell(0).innerHTML = tbl.rows[row-1].cells[0].innerHTML;
	
	tr.insertCell(1).innerHTML = "<input class='checkbox' name='time_" + num + "_" + char + "_allow' value='1' id='time_" + num + "_" + char + "_allow_1' checked='checked' type='radio'>" +
								 "<label class='label' for='time_" + num + "_" + char + "_allow_1'>" + lang_time_allow + "</label><br/>\n" +
								 "<input class='checkbox' name='time_" + num + "_" + char + "_allow' value='0' id='time_" + num + "_" + char + "_allow_0' type='radio'>" +
								 "<label class='label' for='time_" + num + "_" + char + "_allow_0'>" + lang_time_disallow + "</label>\n";
	
	tr.insertCell(2).innerHTML = tbl.rows[row-1].cells[2].innerHTML;
	
	tr.insertCell(3).innerHTML = "<a href=\"javascript:ajax_time_delete('0')\" title='" + lang_time_delete + "'><img src='images/button_delete.png' alt='delete'></a>\n";
	
	tr.cells[0].className = "row1";
	tr.cells[1].className = "row1";
	tr.cells[2].className = "row1";
	tr.cells[3].className = "row1";
	
	tr.cells[3].style.textAlign = "center";
	
	//--------------------------------------------
	// Изменяем идентификаторы полей
	//--------------------------------------------
	
	var char = std ? "s" : "i";
	
	tr.cells[0].getElementsByTagName('SELECT').item(0).name = "time_" + num + "_" + char + "_users[]";
	tr.cells[0].getElementsByTagName('SELECT').item(0).onblur = function() { ajax_add_list_row( this, next_id, std ) };
	
	for( i = 0; i < len; i++ )
	{
		tr.cells[0].getElementsByTagName('SELECT').item(0).options[i].selected = false;
	}
	
	if( std )
	{
		tr.cells[2].getElementsByTagName('SELECT').item(0).name = "time_" + num + "_s_start_day";
		tr.cells[2].getElementsByTagName('SELECT').item(1).name = "time_" + num + "_s_start_month";
		tr.cells[2].getElementsByTagName('SELECT').item(2).name = "time_" + num + "_s_start_hour";
		tr.cells[2].getElementsByTagName('SELECT').item(3).name = "time_" + num + "_s_start_minute";
		tr.cells[2].getElementsByTagName('SELECT').item(4).name = "time_" + num + "_s_end_day";
		tr.cells[2].getElementsByTagName('SELECT').item(5).name = "time_" + num + "_s_end_month";
		tr.cells[2].getElementsByTagName('SELECT').item(6).name = "time_" + num + "_s_end_hour";
		tr.cells[2].getElementsByTagName('SELECT').item(7).name = "time_" + num + "_s_end_minute";
		
		tr.cells[2].getElementsByTagName('SELECT').item(0).value = 1;
		tr.cells[2].getElementsByTagName('SELECT').item(1).value = 1;
		tr.cells[2].getElementsByTagName('SELECT').item(2).value = 0;
		tr.cells[2].getElementsByTagName('SELECT').item(3).value = 0;
		tr.cells[2].getElementsByTagName('SELECT').item(4).value = 1;
		tr.cells[2].getElementsByTagName('SELECT').item(5).value = 1;
		tr.cells[2].getElementsByTagName('SELECT').item(6).value = 0;
		tr.cells[2].getElementsByTagName('SELECT').item(7).value = 0;
	}
	else
	{
		tr.cells[2].getElementsByTagName('SELECT').item(0).name = "time_" + num + "_i_start_hour";
		tr.cells[2].getElementsByTagName('SELECT').item(1).name = "time_" + num + "_i_start_minute";
		tr.cells[2].getElementsByTagName('SELECT').item(2).name = "time_" + num + "_i_wday";
		tr.cells[2].getElementsByTagName('SELECT').item(3).name = "time_" + num + "_i_end_hour";
		tr.cells[2].getElementsByTagName('SELECT').item(4).name = "time_" + num + "_i_end_minute";
		
		tr.cells[2].getElementsByTagName('SELECT').item(0).value = 0;
		tr.cells[2].getElementsByTagName('SELECT').item(1).value = 0;
		tr.cells[2].getElementsByTagName('SELECT').item(2).value = 1;
		tr.cells[2].getElementsByTagName('SELECT').item(3).value = 0;
		tr.cells[2].getElementsByTagName('SELECT').item(4).value = 0;
	}
	
	//--------------------------------------------
	// Увеличиваем высоту окна
	//--------------------------------------------
	
	my_getbyid( "ajax_window" ).style.height = ( parseInt( my_getbyid( "ajax_window" ).style.height ) + 53 ) + "px";
}

/**
* Удаление временного ограничения
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @param	int 	Идентификатор параметра
*
* @return	void
*/

function ajax_time_delete( id )
{
	if( !confirm( lang_confirm_delete_limit ) ) return;
	
	ajax_window( "schedule", "delete_limit&auser="+active_user+"&asub="+active_sub, id );
}

/**
* Отмена текущих закачек и блокировка текущих заданий
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_delete_running()
{
	if( !confirm( lang_confirm_delete_running ) ) return;
	
	ajax_window( "schedule", "delete_running&sub="+active_sub, active_user );
}