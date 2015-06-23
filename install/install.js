/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			AJAX - Установочные функции
*/

/**
* Выбор элемента по ID
*
* Производит выбор элемента по его
* идентификатору в DOM модели страницы.
* 
* @param		mixed	ID элеметна
*
* @return		object	Найденный элемент
*/

function my_getbyid( id )
{
	if( document.getElementById )
	{
		return document.getElementById( id );
	}
	else if( document.all )
	{
		return document.all[id];
	}
	else if( document.layers )
	{
		return document.layers[id];
	}

	return null;
}

/**
* Переключение состояния полей
* 
* @param		object	Элемент
* @param		object	Включить поля
*
* @return		void
*/

function toggle_params( elem, on )
{
	form = my_getbyid( "db_form" );
	
	if( elem.checked != true ) return;
	
	if( on )
	{
		form.db_host.disabled = false;
		form.db_database.disabled = false;
		form.db_user.disabled = false;
		form.db_pass.disabled = false;
		form.db_pass_confirm.disabled = false;
	}
	else
	{
		form.db_host.disabled = true;
		form.db_database.disabled = true;
		form.db_user.disabled = true;
		form.db_pass.disabled = true;
		form.db_pass_confirm.disabled = true;
	}
}