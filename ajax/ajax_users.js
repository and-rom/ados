/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			AJAX - Функции для страницы пользователей
*/

/**
* Применение настроек
*
* Выбирает поля настроек системы и передает
* их значения на обработку.
*
* @return	void
*/

function ajax_apply_settings()
{
	ajax_window( "settings", "settings_apply"+my_parse_form( "settings_form" ), 0 );
}

/**
* Удаление пользователя
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* 
* @param	int		Идентификатор пользователя
*
* @return	void
*/

function ajax_user_delete( id )
{
	if( id == 0 )
	{
		alert( lang_error_user_not_saved );
		return;
	}

	if( id == 1 )
	{
		alert( lang_error_user_is_root );
		return;
	}
	
	if( confirm( lang_confirm_user_delete ) )
	{
		ajax_window( "users", "delete_user", id );
	}
}

/**
* Добавление ряда для пользователя
*
* Добавляет в таблицу новый ряд для заполнения
* параметров пользователя, если текущий ряд уже заполнен.
* 
* @param	obj		Текущее поле имени пользователя
*
* @return	void
*/

function ajax_add_list_row( obj, num )
{
	//--------------------------------------------
	// Проверяем значение поля
	//--------------------------------------------
	
	if( obj.value == "" )
	{
		return;
	}
	
	//--------------------------------------------
	// Определяем количество рядов в таблице
	//--------------------------------------------
	
	tbl = my_getbyid( "users_table" );
	row = tbl.rows.length - 1;
	
	//--------------------------------------------
	// Проверяем значение поля в последнем ряду
	//--------------------------------------------
	
	if( !tbl.rows[row-1].cells[0].getElementsByTagName('INPUT').item(0).value )
	{
		return;
	}
	
	//--------------------------------------------
	// Вставляем новый ряд
	//--------------------------------------------
	
	tr = tbl.insertRow( row );
	tr.insertCell(0).innerHTML = "<input type='text' class='text_small' name='user_" + num + "_name' value='' style='width:219px;' onblur='ajax_add_list_row(this," + (num+1) + ");' />\n";
	tr.insertCell(1).innerHTML = "<input class='checkbox' name='user_" + num + "_use_pass' onclick=\"ajax_toggle_field_state('" + num + "',this,'pass')\" type='checkbox' /> "+
								 "<input type='password' class='text_small' name='user_" + num + "_pass' value='' id='user_" + num + "_pass' style='width:195px;' disabled='disabled' />\n";
	tr.insertCell(2).innerHTML = "<input type='checkbox' class='checkbox' name='user_" + num + "_admin' />\n";
	tr.insertCell(3).innerHTML = "<select class='select_small' name='user_" + num + "_lang' style='width:105px;'>" + my_getbyid( "langs_list" ).innerHTML + "</select>\n";
	tr.insertCell(4).innerHTML = "<input class='checkbox' name='user_" + num + "_use_speed' checked='checked' onclick=\"ajax_toggle_field_state('" + num + "',this,'max_speed')\" type='checkbox' />\n "+
								 "<input type='text' class='text_small' name='user_" + num + "_max_speed' value='128' id='user_" + num + "_max_speed' style='width:80px;' />\n";
	tr.insertCell(5).innerHTML = "<input class='checkbox' name='user_" + num + "_use_amount' checked='checked' onclick=\"ajax_toggle_field_state('" + num + "',this,'max_amount')\" type='checkbox' />\n "+
								 "<input type='text' class='text_small' name='user_" + num + "_max_amount' value='10' id='user_" + num + "_max_amount' style='width:80px;' />\n";
	tr.insertCell(6).innerHTML = "<a href=\"javascript:ajax_user_delete('0')\" title='" + lang_user_delete + "'><img src='images/button_delete.png' alt='delete' /></a>\n";
	
	tr.cells[0].className = "row2";
	tr.cells[1].className = "row2";
	tr.cells[2].className = "row1";
	tr.cells[3].className = "row2";
	tr.cells[4].className = "row2";
	tr.cells[5].className = "row2";
	tr.cells[6].className = "row1";
	
	tr.cells[2].style.textAlign = "center";
	tr.cells[6].style.textAlign = "center";
}

/**
* Изменение типа поля
*
* Вызывает функцию изменения типа поля и
* передает ей необходимые параметры.
* 
* @param	obj		Поле ввода пароля
* @param	bool	Передать фокус полю
*
* @return	void
*/

function ajax_change_type( obj, dofocus )
{
	if( obj.value == "" )
	{		
		ajax_change_input_type( obj, "text", lang_pass_click_to_edit, dofocus );
	}
	else if( obj.value == lang_pass_click_to_edit )
	{
		ajax_change_input_type( obj, "password", "", dofocus );
	}
}

/**
* Обновление списка пользователей системы
*
* Выбирает поля формы со списком пользователей и
* передает их значения на обработку.
*
* @return	void
*/

function ajax_update_list()
{
	form = my_getbyid( "users_form" );
	
	//--------------------------------------------
	// Определяем количество рядов в таблице
	//--------------------------------------------
	
	var tbl = my_getbyid( "users_table" );
	var num = tbl.rows.length - 1;
	
	//--------------------------------------------
	// Получаем настройки для доменов
	//--------------------------------------------
	
	var query = "";
	
	var re = /user_(\d+)_name/;
	
	for( i = 1; i < num; i++ ) if( id = tbl.rows[ i ].cells[0].getElementsByTagName('INPUT').item(0).name.match( re ) )
	{
		var user_name   = eval( "form.user_" + id[1] +"_name.value"			);
		var use_pass    = eval( "form.user_" + id[1] +"_use_pass.checked"	);
		var user_pass   = eval( "form.user_" + id[1] +"_pass.value"			);
		var user_admin  = eval( "form.user_" + id[1] +"_admin.checked"		);
		var user_lang   = eval( "form.user_" + id[1] +"_lang.value"			);
		var use_speed   = eval( "form.user_" + id[1] +"_use_speed.checked"	);
		var user_speed  = eval( "form.user_" + id[1] +"_max_speed.value"	);
		var use_amount  = eval( "form.user_" + id[1] +"_use_amount.checked"	);
		var user_amount = eval( "form.user_" + id[1] +"_max_amount.value"	);
		
		if( !user_name && ( use_pass || user_admin || !use_speed || !use_amount || user_speed != 128 || user_amount != 10 ) )
		{
			alert( lang_error_user_no_name );
			return;
		}
		else if( user_name )
		{
			user_pass   = ( user_pass == lang_pass_click_to_edit ) ? "[********]" : user_pass;
			user_admin  = ( user_admin == true ) ? 1 : 0;
			user_amount = use_amount ? parseFloat( user_amount ) : -1;
			user_speed  = use_speed  ? parseInt( user_speed )    : -1;
				
			var user_info = use_pass ? user_name + "," + user_admin + "," + user_speed + "," + user_amount + "," + user_lang + "," + user_pass
									 : user_name + "," + user_admin + "," + user_speed + "," + user_amount + "," + user_lang;
				
			query += "&user_" + id[1] + "=" + user_info;
		}
	}
	
	ajax_window( "users", "update_users_list"+query, 0 );
}

/**
* Переключение состояния поля ввода
*
* Включает или выключает поле ввода в зависимости от того,
* поставлена ли "галочка" перед этим полем.
*
* @param	int		Идентификатор пользователя
* @param	object	Объект поля выбора
* @param	string	Идентификатор поля ввода
*
* @return	void
*/

function ajax_toggle_field_state( id, obj, type )
{
	if( obj.checked == true ) my_getbyid( "user_"+id+"_"+type ).disabled = false;
	else my_getbyid( "user_"+id+"_"+type ).disabled = true;
}