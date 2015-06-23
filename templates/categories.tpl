<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Шаблон для секции "Категории"
*/

/**
* Класс, содержащий функции
* для секции категорий системы.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class template_categories
{
	/**
	* Начало страницы - левая часть
	* таблицы
	*/
	
	function page_top()
	{
		$return[] = <<<EOF
		<table width="100%" cellspacing="0" cellpadding="0">
		<tr>
		<td class="treeview">
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Середина страницы - правая часть
	* таблицы
	*/
	
	function page_middle()
	{
		$return[] = <<<EOF
		</td>
		<td class="listview">
		<div class="inner_menu" id="dummy_right" style="display:none;"></div>
		<div class="inner_menu" id="menu_right">
		<div class="attach_menu" id="update_container_11">
		<!--PAGE_MENU-->
		</div>
		<div class="inner_button"><a href="javascript:ajax_file_edit();" title='{$this->engine->lang['file_edit']}'><img src='images/file_edit.png' alt='{$this->engine->lang['file_edit']}' /> {$this->engine->lang['file_edit_button']}</a></div>
		<div class="inner_button"><a href="javascript:ajax_file_delete();" title='{$this->engine->lang['file_delete']}'><img src='images/file_delete.png' alt='{$this->engine->lang['file_delete']}' /> {$this->engine->lang['file_delete_button']}</a></div>
		<div class="inner_button"><a href="javascript:ajax_toggle_file_move();" title='{$this->engine->lang['file_move']}' id="file_moving"><img src='images/file_move.png' alt='{$this->engine->lang['file_move']}' /> {$this->engine->lang['file_move_button']}</a></div>
		</div>
		<div id="list">
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Конец страницы - конец таблицы
	*/
	
	function page_bottom()
	{
		$return[] = <<<EOF
		</td>
		</tr>
		</table>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Начало списка категорий - меню
	*/
	
	function cat_list_top()
	{
		$return[] = <<<EOF
		<div class="inner_menu" id="dummy_left" style="display:none;"></div>
		<div class="inner_menu" id="menu_left">
		<div class="inner_button"><a href="javascript:ajax_category_add();" title='{$this->engine->lang['category_add']}'><img src='images/category_add.png' alt='{$this->engine->lang['category_add']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_category_edit();" title='{$this->engine->lang['category_edit']}'><img src='images/category_edit.png' alt='{$this->engine->lang['category_edit']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_category_delete();" title='{$this->engine->lang['category_delete']}'><img src='images/category_delete.png' alt='{$this->engine->lang['category_delete']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_toggle_cat_move();" title='{$this->engine->lang['category_move']}' id="cat_moving"><img src='images/category_move.png' alt='{$this->engine->lang['category_move']}' /></a></div>
		</div>
		<div id="update_container_0" style="padding:5px;">
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Корневой каталог
	* 
	* @param 	string	Название каталога
	* @param 	int		ID пользователя - владельца каталога
	* @param 	string	Изображение для текущего состояния
	*/
	
	function cat_list_root( $name="", $id=0, $active=FALSE, $img="" )
	{
		$background = $active ? "#ededed" : "transparent";
		$src = $id ? "cat_user" : "cat_share";
		
		$return[] = <<<EOF
		<div id="root_{$id}" style="height: 18px;">
			{$img}<a href="javascript:ajax_show_contents('root_{$id}');" style="text-decoration:none;background-color:{$background};padding:2px;" id="link_root_{$id}"><img src="images/{$src}.png" alt="" />&nbsp;<b>{$name}</b></a>
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Изображение для структуры
	* 
	* @param 	string	Параметры изображения
	*/
	
	function cat_list_image( $params=array() )
	{
	
	if( $params['jsc'] )
	{
		$return[] = <<<EOF
<span id="itm_{$params['cat']}_{$params['num']}"><a href="javascript:ajax_cat_{$params['jsc']};">
EOF;
	}
		
		$return[] = <<<EOF
<img src="images/{$params['src']}.gif" alt="{$params['alt']}" />
EOF;
	
	if( $params['jsc'] )
	{
		$return[] = <<<EOF
</a></span>
EOF;
	}
		
		return implode( "", $return );
	}
	
	/**
	* Элемент списка
	* 
	* @param 	string	Параметры элемента
	*/
	
	function cat_list_element( $cat=array() )
	{	
		$background = $cat['cat_active'] ? "#ededed" : "transparent";
		$display = $cat['cat_up_hidden'] ? "none" : "";
		
		$return[] = <<<EOF
		<div id="cat_{$cat['cat_id']}_level_{$cat['cat_level']}" style="height:18px;display:{$display};">
EOF;

	if( $cat['cat_active'] )
	{
		$return[] = <<<EOF
		<span id="active_cat">
EOF;
	}
		$return[] = <<<EOF
			{$cat['cat_img']}<a href="javascript:ajax_show_contents('{$cat['cat_id']}');" style="text-decoration:none;background-color:{$background};padding:2px;" id="link_{$cat['cat_id']}"><img src="images/icon_{$cat['cat_icon']}.png" alt="" />&nbsp;{$cat['cat_name']}</a>
EOF;

	if( $cat['cat_active'] )
	{
		$return[] = <<<EOF
		</span>
EOF;
	}
	
	$return[] = <<<EOF
		</div>
EOF;
		
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Конец списка категорий
	*/
	
	function cat_list_bottom()
	{
		$return[] = <<<EOF
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Заголовки столбцов списка файлов
	* 
	* @param 	array	Параметры сортировки для столбцов
	*/
	
	function cat_content_headers( $active=array() )
	{
		$return[] = <<<EOF
		<table class="files_list">
		<tr>
			<th class="sort_mode"><a href="javascript:ajax_sort_files('state','{$active['state']['sort']}');">{$active['state']['img']}&nbsp;</a></th>
			<th class="sort_name"><a href="javascript:ajax_sort_files('name','{$active['name']['sort']}');">{$active['name']['img']}&nbsp;{$this->engine->lang['content_sort_name']}</a></th>
			<th class="sort_date"><a href="javascript:ajax_sort_files('added','{$active['added']['sort']}');">{$active['added']['img']}&nbsp;{$this->engine->lang['content_sort_added']}</a></th>
			<th class="sort_size" style="border:0;"><a href="javascript:ajax_sort_files('size','{$active['size']['sort']}');">{$active['size']['img']}&nbsp;{$this->engine->lang['content_sort_size']}</a></th>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Значок сортировки
	* 
	* @param 	string	Тип сортировки
	*/
	
	function cat_content_sort( $type="asc" )
	{
		$return[] = <<<EOF
<img src="images/sort_{$type}.gif" alt="{$this->engine->lang['content_sort_'.$type ]}" />
EOF;
		return implode( "", $return );
	}
	
	/**
	* Информационное сообщение
	* 
	* @param 	string	Текст сообщения
	*/
	
	function cat_content_message( $message )
	{
		$return[] = <<<EOF
		<tr>
			<td colspan="3" id="message_string">({$message})</td>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Строка с информацией о файле
	* 
	* @param 	array	Параметры файла
	* @param 	int		Номер четности ряда
	*/
	
	function cat_file_row( $file=array(), $row=5 )
	{
		$return[] = <<<EOF
		<tr id="file_{$file['file_id']}_row_{$row}">
			<td id="file_{$file['file_id']}_state" class="row{$row}" style="text-align:center" onclick="ajax_toggle_file_selection('{$file['file_id']}');" onmouseover="ajax_toggle_file_row(1,'{$file['file_id']}');" onmouseout="ajax_toggle_file_row(0,'{$file['file_id']}');">{$file['file_state']}</td>
			<td id="file_{$file['file_id']}_name" class="row{$row}" onclick="ajax_toggle_file_selection('{$file['file_id']}');" onmouseover="ajax_toggle_file_row(1,'{$file['file_id']}');" onmouseout="ajax_toggle_file_row(0,'{$file['file_id']}');">{$file['file_name']}</td>
			<td id="file_{$file['file_id']}_added" class="row{$row}" onclick="ajax_toggle_file_selection('{$file['file_id']}');" onmouseover="ajax_toggle_file_row(1,'{$file['file_id']}');" onmouseout="ajax_toggle_file_row(0,'{$file['file_id']}');">{$file['file_added']}</td>
			<td id="file_{$file['file_id']}_size" class="row{$row}" onclick="ajax_toggle_file_selection('{$file['file_id']}');" onmouseover="ajax_toggle_file_row(1,'{$file['file_id']}');" onmouseout="ajax_toggle_file_row(0,'{$file['file_id']}');" style="text-align:right;padding-right:5px;">{$file['file_size']}</td>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Окончание списка файлов
	*/
	
	function cat_content_footer()
	{
		$return[] = <<<EOF
		</table>
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Меню перемещения по страницам.
	*/
	
	function page_menu()
	{
		$return[] = <<<EOF
		<div class="inner_button"><a href="javascript:ajax_set_page('first',{$this->engine->input['page']},'categories',active_user,0);" title='{$this->engine->lang['page_first']}'><img src='images/page_first.png' alt='{$this->engine->lang['page_first']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_set_page('prev',{$this->engine->input['page']},'categories',active_user,0);" title='{$this->engine->lang['page_previous']}'><img src='images/page_prev.png' alt='{$this->engine->lang['page_previous']}' /></a></div>
		<form action="" method="post" onsubmit="ajax_set_page(this.page.value,{$this->engine->input['page']},'categories',active_user,0);return false;">
			<div style="float:left;"><input class="text_tiny" style="margin:7px 2px 0 2px;" name="page" value="{$this->engine->input['page']}" type="text" /></div>
		</form>
		<div class="inner_button"><a href="javascript:ajax_set_page('next',{$this->engine->input['page']},'categories',active_user,0);" title='{$this->engine->lang['page_next']}'><img src='images/page_next.png' alt='{$this->engine->lang['page_next']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_set_page('last',{$this->engine->input['page']},'categories',active_user,0);" title='{$this->engine->lang['page_last']}'><img src='images/page_last.png' alt='{$this->engine->lang['page_last']}' /></a></div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
}

?>