/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			AJAX - Функции для страницы настроек
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
* Просмотр списка языков системы или
* параметров авторизации
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* 
* @param	string		Идентификатор настройки
*
* @return	void
*/

function ajax_view_list( type )
{
	ajax_window( "settings", "view_"+type+"_list", 0 );
}

/**
* Удаление домена
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* 
* @param	int		Идентификатор домена
*
* @return	void
*/

function ajax_list_auth_delete( id )
{
	if( id == 0 )
	{
		alert( lang_error_auth_not_saved );
		return;
	}
	
	if( confirm( lang_confirm_list_delete ) )
	{
		ajax_window( "settings", "delete_auth", id );
	}
}

/**
* Добавление ряда для домена
*
* Добавляет в таблицу новый ряд для заполнения
* параметров домена, если текущий ряд уже заполнен.
* 
* @param	obj		Текущее поле имени домена
*
* @return	void
*/

function ajax_add_list_auth_row( obj, num )
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
	
	tbl = my_getbyid( "ajax_table" );
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
	tr.insertCell(0).innerHTML = "<input class='text_small' name='domain_" + num + "_name' value='' style='width: 164px;' onblur='ajax_add_list_auth_row(this," + (num+1) + ");' type='text' />\n";
	tr.insertCell(1).innerHTML = "<input class='text_small' name='domain_" + num + "_user' value='' style='width: 133px;' type='text' />\n";
	tr.insertCell(2).innerHTML = "<input class='checkbox' name='domain_" + num + "_use_pass' onclick=\"ajax_toggle_pass_state('" + num + "',this)\" type='checkbox' />\n "+
								 "<input class='text_small' name='domain_" + num + "_pass' value='' style='width: 133px;' type='password' disabled='disabled' />\n";
	tr.insertCell(3).innerHTML = "<input class='checkbox' name='domain_" + num + "_share' type='checkbox' />\n";
	tr.insertCell(4).innerHTML = "<a href=\"javascript:ajax_list_auth_delete('0')\" title='" + lang_list_auth_delete + "'><img src='images/button_delete.png' alt='delete' /></a>\n";
	
	tr.cells[0].className = "row1";
	tr.cells[1].className = "row2";
	tr.cells[2].className = "row2";
	tr.cells[3].className = "row1";
	tr.cells[4].className = "row1";
	
	tr.cells[3].style.textAlign = "center";
	tr.cells[4].style.textAlign = "center";
	
	my_getbyid( "ajax_window" ).style.height = ( parseInt( my_getbyid( "ajax_window" ).style.height ) + 27 ) + "px";
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
		ajax_change_input_type( obj, "text", lang_auth_click_to_edit, dofocus );
	}
	else if( obj.value == lang_auth_click_to_edit )
	{
		ajax_change_input_type( obj, "password", "", dofocus );
	}
}

/**
* Обновление списка авторизации для
* доменов
*
* Выбирает поля формы со списком доменов и
* передает их значения на обработку.
*
* @return	void
*/

function ajax_update_list_auth()
{
	form = my_getbyid( "auth_list" );
	
	//--------------------------------------------
	// Определяем количество рядов в таблице
	//--------------------------------------------
	
	tbl = my_getbyid( "ajax_table" );
	num = tbl.rows.length - 1;
	
	//--------------------------------------------
	// Получаем настройки для доменов
	//--------------------------------------------
	
	var query = "";
	
	var re = /domain_(\d+)_name/;
	
	for( i = 1; i < num; i++ ) if( id = tbl.rows[ i ].cells[0].getElementsByTagName('INPUT').item(0).name.match( re ) )
	{
		var domain_name  = eval( "form.domain_" + id[1] +"_name.value"			);
		var domain_user  = eval( "form.domain_" + id[1] +"_user.value"			);
		var domain_share = eval( "form.domain_" + id[1] +"_share.checked"		);
		var use_pass  	 = eval( "form.domain_" + id[1] +"_use_pass.checked"	);
		var domain_pass  = eval( "form.domain_" + id[1] +"_pass.value"			);
		
		if( domain_name && domain_user )
		{
			domain_user  = encodeURI( escape( domain_user ) );
			domain_pass  = ( domain_pass == lang_auth_click_to_edit ) ? "[********]" : encodeURI( escape( domain_pass ) );
			domain_share = ( domain_share == true ) ? 1 : 0;
			
			var domain_info = use_pass ? domain_name + "," + domain_user + "," + domain_share + "," + domain_pass : domain_name + "," + domain_user + "," + domain_share;
			
			query += "&domain_" + id[1] + "=" + domain_info;
		}
		else if( domain_name || domain_user || domain_pass )
		{
			alert( lang_error_auth_not_correct );
			return;
		}
	}
	
	ajax_window( "settings", "update_auth_list"+query, 0 );
}

/**
* Удаление домена
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* 
* @param	int		Идентификатор домена
*
* @return	void
*/

function ajax_list_lang_delete( id )
{
	if( id == 0 )
	{
		alert( lang_error_lang_not_saved );
		return;
	}
	
	if( id == 1 )
	{
		alert( lang_error_lang_is_basic );
		return;
	}
	
	if( confirm( lang_confirm_list_delete ) )
	{
		ajax_window( "settings", "delete_lang", id );
	}
}

/**
* Добавление ряда для языка
*
* Добавляет в таблицу новый ряд для заполнения
* параметров языка, если текущий ряд уже заполнен.
* 
* @param	obj		Текущее поле имени языка
*
* @return	void
*/

function ajax_add_list_lang_row( obj, num )
{
	form = my_getbyid( "lang_list" );
	
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
	
	tbl = my_getbyid( "ajax_table" );
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
	tr.insertCell(0).innerHTML = "<input class='text_small' name='lang_" + num + "_name' value='' style='width: 145px;' onblur='ajax_add_list_lang_row(this," + (num+1) + ");' type='text' />\n";
	tr.insertCell(1).innerHTML = "<input class='text_small' name='lang_" + num + "_key' value='' style='width: 80px;' type='text' />\n";
	tr.insertCell(2).innerHTML = "<input class='checkbox' name='lang_default' value='"+num+"' type='radio' />\n";
	tr.insertCell(3).innerHTML = "&nbsp;\n";
	tr.insertCell(4).innerHTML = "<a href=\"javascript:ajax_list_lang_delete('0')\" title='" + lang_list_lang_delete + "'><img src='images/button_delete.png' alt='delete' /></a>\n";
	
	tr.cells[0].className = "row1";
	tr.cells[1].className = "row2";
	tr.cells[2].className = "row2";
	tr.cells[3].className = "row2";
	tr.cells[4].className = "row1";
	
	tr.cells[2].style.textAlign = "center";
	tr.cells[4].style.textAlign = "center";
	
	my_getbyid( "ajax_window" ).style.height = ( parseInt( my_getbyid( "ajax_window" ).style.height ) + 27 ) + "px";
}

/**
* Обновление списка языков системы
*
* Выбирает поля формы со списком языков и
* передает их значения на обработку.
*
* @return	void
*/

function ajax_update_list_lang()
{
	form = my_getbyid( "lang_list" );
	
	//--------------------------------------------
	// Определяем количество рядов в таблице
	//--------------------------------------------
	
	tbl = my_getbyid( "ajax_table" );
	num = tbl.rows.length - 1;
	
	//--------------------------------------------
	// Получаем настройки для языков
	//--------------------------------------------
	
	var query = "";
	var lang_default = 0;
	
	var re = /lang_(\d+)_name/;
	
	for( i = 1; i < num; i++ ) if( id = tbl.rows[ i ].cells[0].getElementsByTagName('INPUT').item(0).name.match( re ) )
	{
		var lang_name = eval( "form.lang_" + id[1] +"_name.value" );
		var lang_key  = eval( "form.lang_" + id[1] +"_key.value"  );
		
		if( lang_name && lang_key )
		{
			query += "&lang_" + id[1] + "=" + lang_name + "," + lang_key;
		}
		else if( lang_name || lang_key )
		{
			alert( lang_error_lang_not_correct );
			return;
		}
		
		lang_default = form.lang_default[i-1].checked == true ? id[1] : lang_default;
	}
	
	ajax_window( "settings", "update_lang_list"+query+"&lang_default="+lang_default, 0 );
}

/**
* Переключение состояния поля ввода пароля
*
* Включает или выключает поле ввода пароля
* в зависимости от того, поставлена ли
* "галочка" перед этим полем.
*
* @param	int		Идентификатор пользователя
* @param	object	Объект поля выбора
*
* @return	void
*/

function ajax_toggle_pass_state( id, obj )
{
	form = my_getbyid( "auth_list" );
	
	if( obj.checked == true )
	{
		eval( "form.domain_"+ id +"_pass.disabled = false" );
	}
	else
	{
		eval( "form.domain_"+ id +"_pass.disabled = true" );
	}
}

/**
* Переключение вкладки меню
*
* Изменяет активную вкладку меню и состояние
* блока, соответствующего этой вкладке.
*
* @param	int		Идентификатор вкладки
*
* @return	void
*/

function ajax_set_tab( tab )
{
	my_getbyid( "tab_main_active"   ).style.display = tab == "main" ? "" : "none";
	my_getbyid( "tab_main_inactive" ).style.display = tab == "main" ? "none" : "";
	my_getbyid( "group_main" ).style.display = tab == "main" ? "" : "none";
	
	my_getbyid( "tab_download_active"   ).style.display = tab == "download" ? "" : "none";
	my_getbyid( "tab_download_inactive" ).style.display = tab == "download" ? "none" : "";
	my_getbyid( "group_download" ).style.display = tab == "download" ? "" : "none";
	
	my_getbyid( "tab_categories_active"   ).style.display = tab == "categories" ? "" : "none";
	my_getbyid( "tab_categories_inactive" ).style.display = tab == "categories" ? "none" : "";
	my_getbyid( "group_categories" ).style.display = tab == "categories" ? "" : "none";
	
	my_getbyid( "tab_shared_files_active"   ).style.display = tab == "shared_files" ? "" : "none";
	my_getbyid( "tab_shared_files_inactive" ).style.display = tab == "shared_files" ? "none" : "";
	my_getbyid( "group_shared_files" ).style.display = tab == "shared_files" ? "" : "none";
	
	my_getbyid( "tab_paths_change_active"   ).style.display = tab == "paths_change" ? "" : "none";
	my_getbyid( "tab_paths_change_inactive" ).style.display = tab == "paths_change" ? "none" : "";
	my_getbyid( "group_paths_change" ).style.display = tab == "paths_change" ? "" : "none";
	
	my_getbyid( "tab_schedule_active"   ).style.display = tab == "schedule" ? "" : "none";
	my_getbyid( "tab_schedule_inactive" ).style.display = tab == "schedule" ? "none" : "";
	my_getbyid( "group_schedule" ).style.display = tab == "schedule" ? "" : "none";
	
	my_getbyid( "tab_log_active"   ).style.display = tab == "log" ? "" : "none";
	my_getbyid( "tab_log_inactive" ).style.display = tab == "log" ? "none" : "";
	my_getbyid( "group_log" ).style.display = tab == "log" ? "" : "none";
	
	my_getbyid( "tab_misc_active"   ).style.display = tab == "misc" ? "" : "none";
	my_getbyid( "tab_misc_inactive" ).style.display = tab == "misc" ? "none" : "";
	my_getbyid( "group_misc" ).style.display = tab == "misc" ? "" : "none";
	
	my_setcookie( "settings_tab", tab, 1 );
}