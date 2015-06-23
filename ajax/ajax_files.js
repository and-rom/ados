/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			AJAX - Функции для работы с файлами
*/

/**
* Изменение параметров файла
*
* Обновляет параметры выбранного файла.
*
* @param	int		Идентификатор файла
* @param	string	Тип действия
* @param	string	Идентификатор текущей секции
*
* @return	void
*/

function ajax_apply_file( id, action, section, control )
{
	var query = "&apply=1";
	
	query += my_parse_form( "ajax_form" );
	
	if( section == 'categories' )
	{
		query += "&cat="+active_cat;
	}
	else if( section == 'download' )
	{
		query += "&auser="+active_user+"&asub="+active_sub;
	}
	
	if( control )
	{
		if( action == 'add' && control != 'run' ) return;
		
		if( control == 'stop' && !confirm( lang_confirm_download_stop ) ) return;
		else if( control == 'delete' && !confirm( lang_confirm_download_delete ) ) return;
		
		query += "&control="+control;
	}
	
	var active_info = section == 'download' ? "&auser="+active_user+"&asub="+active_sub : "";
	
	ajax_window( section, "file_"+action+query+active_info, id );
}

/**
* Обновление информации о закачке
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
*
* @return	void
*/

function ajax_update_state( id, section )
{
	time = Date.parse( new Date() );
	
	if( ( time - last_reload ) < 1000 ) return;
	
	if( my_getbyid( "wait_bar" ).style.display != "none" ) return;
	
	last_reload = time;
	
	var active_info = section == 'download' ? "&auser="+active_user+"&asub="+active_sub : "";
	
	ajax_window( section, "file_refresh"+active_info, id );
}