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
* Вывод информации о модуле
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* 
* @param	int		Идентификатор модуля
*
* @return	void
*/

function ajax_module_info( id )
{
	ajax_window( "modules", "info", id );
}

/**
* Вывод настроек модуля
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* 
* @param	int		Идентификатор модуля
*
* @return	void
*/

function ajax_module_settings( id )
{
	ajax_window( "modules", "settings", id );
}

/**
* Выбор действия для выполнения
*
* В зависимости от сохраненного параметра
* вызывает либо функцию применения умолчаний,
* либо функцию запуска установки модуля.
*
* @return	void
*/

function ajax_module_choose()
{
	//--------------------------------------------
	// Установка модуля
	//--------------------------------------------
	
	if( my_getbyid( "hidden_action" ).value == "install" )
	{
		ajax_module_install(0);
	}
	
	//--------------------------------------------
	// Обновление умолчаний
	//--------------------------------------------
	
	else
	{
		ajax_module_default();
	}
}

/**
* Обновление модуля по умолчанию.
*
* Делает необходимую проверку и, если
* нет ошибок, устанавливает новые
* умолчания
*
* @return	void
*/

function ajax_module_default()
{
	ajax_window( "modules", "default"+my_parse_form( "ajax_form" ), 0 );
}

/**
* Установка модуля
*
* Выводит окно Мастера установки модулей,
* с основными инструкциями по установке.
*
* @param	int		Номер следующего шага
*
* @return	bool	FALSE
*/

function ajax_module_install( step )
{
	var query  = "";
	var button = null;
	
	parsed_form = my_parse_form( "step_form" );
	
	//--------------------------------------------
	// Обрабатываем значения элементов формы
	//--------------------------------------------
	
	if( parsed_form != false && ajax_window_loaded )
	{
		query += parsed_form;
		
		if( button = my_getbyid( "button_submit" ) ) button.disabled = true;
	}
	
	//--------------------------------------------
	// Выполняем запрос
	//--------------------------------------------
	
	ajax_window( "modules", "install"+query, step );
	
	if( button = my_getbyid( "button_submit" ) ) button.disabled = false;
	
	return false;
}

/**
* Проверка необходимости загрузки файла
*
* В зависимости от положения переключателя
* вызывает для перехода на следующий шаг
* либо функцию загрузки файла, либо
* стандартный AJAX метод.
*
* @param	int		Номер следующего шага
*
* @return	void
*/

function ajax_module_load()
{
	var form = my_getbyid( "step_form" );
	
	num = form.elements.length;
	
	if( button = my_getbyid( "button_submit" ) ) button.disabled = true;
		
	for( i = 0; i < num; i++ )
	{
		if( form.elements[ i ].name == "code" && form.elements[ i ].value == "upload"  )
		{
			if( form.elements[ i ].checked == true || form.elements[ i ].type == "hidden" )
			{
				return true;
			}
			else
			{
				ajax_module_install( 1 );
	
				return false;
			}
		}
	}
}

/**
* Результат загрузки файла
*
* Выводит результат обработки формы с загрузкой
* файла -- AJAX окно.
*
* @param	object		HTML с ответом сервера
*
* @return	void
*/

function ajax_module_upload( response )
{
	if( button = my_getbyid( "button_submit" ) ) button.disabled = false;
	
	//--------------------------------------------
	// Определяем контейнер
	//--------------------------------------------
	
	var ajax_container = my_getbyid( "ajax_container" );
	
	//--------------------------------------------
	// Обрабатываем XML
	//--------------------------------------------
	
	xml = Base64.decode( response.getElementById( 'EncodedData' ).innerHTML );
	
	if (window.ActiveXObject)
	{
		var doc = new ActiveXObject( "Microsoft.XMLDOM" );
		doc.async = "false";
		doc.loadXML( xml );
	}
	else
	{
		var parser = new DOMParser();
		var doc = parser.parseFromString( xml,"text/xml" );
	}
	
	xmldoc = doc.documentElement;
	
	content = xmldoc.getElementsByTagName( 'HTML' )[0];
	itmlist = xmldoc.getElementsByTagName( 'List' )[0];
	
	//--------------------------------------------
	// Сообщение
	//--------------------------------------------
			
	if( message = xmldoc.getElementsByTagName( 'Message' )[0] )
	{
		alert( message.firstChild.data );
				
		if( !content && !itmlist ) return;
	}
			
	//--------------------------------------------
	// Выводим окно
	//--------------------------------------------
			
	if( content )
	{
		ajax_container.innerHTML = content.firstChild.data;
			
		ajax_window_initiate();
	}
			
	//--------------------------------------------
	// Обновляем список модулей
	//--------------------------------------------
			
	if( itmlist )
	{
		my_getbyid( "list" ).innerHTML = itmlist.firstChild.data;
	}
}

/**
* Удаление модуля
*
* Проверяет, возможно ли удаление модуля
* и, если да, запрашивает подтверждение
* на удаление.
* Если оно получено, удаляет модуль и
* обновляет список модулей.
* 
* @param	int		Идентификатор модуля
*
* @return	void
*/

function ajax_module_delete( id )
{
	var module_state = my_getbyid( "module_default_" + id );
	var modules_list = my_getbyid( "modules_list" );
	
	if( module_state.checked == true )
	{
		alert( lang_error_module_is_active );
		return;
	}
	
	//--------------------------------------------
	// Запрашиваем подтверждение
	//--------------------------------------------
	
	if( !confirm( lang_confirm_module_delete ) )
	{
		return;
	}
	
	//--------------------------------------------
	// Выполняем запрос
	//--------------------------------------------
	
	ajax_window( "modules", "delete", id );
}

/**
* Включение модуля
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* 
* @param	int		Идентификатор модуля
*
* @return	void
*/

function ajax_module_enable( id )
{
	ajax_window( "modules", "enable", id );
}

/**
* Обновление информации о версии программы
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* 
* @param	int		Идентификатор модуля
*
* @return	void
*/

function ajax_module_version( id )
{
	ajax_window( "modules", "version", id );
}

/**
* Применение настроек
*
* Выбирает поля настроек модуля и передает
* их значения на обработку.
* 
* @param	int		Идентификатор модуля
*
* @return	void
*/

function ajax_apply_settings( id )
{
	ajax_window( "modules", "settings_apply"+my_parse_form( "settings_form" ), id );
}