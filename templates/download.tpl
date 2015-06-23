<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Шаблон для секции "Расписание"
*/

/**
* Класс, содержащий функции
* для секции расписания закачек.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class template_download
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
		<div class="inner_button"><a href="javascript:ajax_download_add();" title='{$this->engine->lang['download_add']}'><img src='images/download_add.png' alt='{$this->engine->lang['download_add']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_download_edit();" title='{$this->engine->lang['download_edit']}'><img src='images/file_edit.png' alt='{$this->engine->lang['download_edit']}' /></a></div>
		<div class="inner_button" style="padding-left:20px;"><a href="javascript:ajax_download_change_state('run');" title='{$this->engine->lang['download_run']}'><img src='images/download_run.png' alt='{$this->engine->lang['download_run']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_download_change_state('pause');" title='{$this->engine->lang['download_pause']}'><img src='images/download_pause.png' alt='{$this->engine->lang['download_pause']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_download_change_state('stop');" title='{$this->engine->lang['download_stop']}'><img src='images/download_stop.png' alt='{$this->engine->lang['download_stop']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_download_change_state('delete');" title='{$this->engine->lang['download_delete']}'><img src='images/download_delete.png' alt='{$this->engine->lang['download_delete']}' /></a></div>
		<div class="inner_button" style="padding-left:20px;"><a href="javascript:ajax_download_change_state('run',1);" title='{$this->engine->lang['download_run_all']}'><img src='images/download_run_all.png' alt='{$this->engine->lang['download_run_all']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_download_change_state('pause',1);" title='{$this->engine->lang['download_pause_all']}'><img src='images/download_pause_all.png' alt='{$this->engine->lang['download_pause_all']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_download_change_state('stop',1);" title='{$this->engine->lang['download_stop_all']}'><img src='images/download_stop_all.png' alt='{$this->engine->lang['download_stop_all']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_download_change_state('delete',1);" title='{$this->engine->lang['download_delete_all']}'><img src='images/download_delete_all.png' alt='{$this->engine->lang['download_delete_all']}' /></a></div>
		<div class="inner_button" style="padding-left:20px;"><a href="javascript:ajax_download_refresh();" title='{$this->engine->lang['download_refresh']}'><img src='images/download_refresh.png' alt='{$this->engine->lang['download_refresh']}' /></a></div>
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
	* Начало списка пользователей - меню
	*/
	
	function users_list_top()
	{
		$return[] = <<<EOF
		<div class="inner_menu" id="dummy_left" style="display:none;"></div>
		<div class="inner_menu" id="menu_left"></div>
		<div id="update_container_0" style="padding:5px;">
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Значок пользователя
	* 
	* @param 	array	Параметры пользователя
	* @param 	string	Тип пиктограммы
	* @param 	bool	Активный пользователь
	* @param 	bool	Скрытый пользователь
	*/
	
	function users_list_item( $user=array(), $icon='single', $active=FALSE, $hidden=FALSE, $sub='all' )
	{
		$background['all']		= ( $active and $sub == 'all'		) ? "#ededed" : "transparent";
		$background['idle']		= ( $active and $sub == 'idle'		) ? "#ededed" : "transparent";
		$background['running']	= ( $active and $sub == 'running'	) ? "#ededed" : "transparent";
		$background['paused']	= ( $active and $sub == 'paused'	) ? "#ededed" : "transparent";
		$background['query']	= ( $active and $sub == 'query'		) ? "#ededed" : "transparent";
		$background['schedule']	= ( $active and $sub == 'schedule'	) ? "#ededed" : "transparent";
		$background['continue']	= ( $active and $sub == 'continue'	) ? "#ededed" : "transparent";
		$background['stopped']	= ( $active and $sub == 'stopped'	) ? "#ededed" : "transparent";
		$background['blocked']	= ( $active and $sub == 'blocked'	) ? "#ededed" : "transparent";
		$background['error']	= ( $active and $sub == 'error'		) ? "#ededed" : "transparent";
		$background['done']		= ( $active and $sub == 'done'		) ? "#ededed" : "transparent";
		
		$display = $hidden ? "display:none;" : "";
		
		$script = $hidden ? "<a href=\"javascript:ajax_toggle_user('{$user['user_id']}',true);\"><img src='images/plus4.gif' alt='[+]-' /></a>"
						  : "<a href=\"javascript:ajax_toggle_user('{$user['user_id']}',false);\"><img src='images/minus3.gif' alt='[-]-' /></a>";
		
		$return[] = <<<EOF
		<div style="padding:2px;">
			<div style="height:18px;"><span id="root_{$user['user_id']}">{$script}</span> <a href="javascript:ajax_change_user('{$user['user_id']}','all');" style="text-decoration:none;background-color:{$background['all']};padding:1px;" id="user_{$user['user_id']}_all"><img src="images/user_{$icon}.png" alt="" />&nbsp;<b>{$user['user_name']}</b></a></div>
			<div style="height:18px;{$display}" id="running_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','running');" style="text-decoration:none;background-color:{$background['running']};padding:1px;" id="user_{$user['user_id']}_running"><img src="images/download_running.png" alt="" />&nbsp;{$this->engine->lang['download_running']}</a></div>
			<div style="height:18px;{$display}" id="paused_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','paused');" style="text-decoration:none;background-color:{$background['paused']};padding:1px;" id="user_{$user['user_id']}_paused"><img src="images/download_paused.png" alt="" />&nbsp;{$this->engine->lang['download_paused']}</a></div>
			<div style="height:18px;{$display}" id="idle_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','idle');" style="text-decoration:none;background-color:{$background['idle']};padding:1px;" id="user_{$user['user_id']}_idle"><img src="images/download_idle.png" alt="" />&nbsp;{$this->engine->lang['download_idle']}</a></div>
			<div style="height:18px;{$display}" id="query_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','query');" style="text-decoration:none;background-color:{$background['query']};padding:1px;" id="user_{$user['user_id']}_query"><img src="images/download_query.png" alt="" />&nbsp;{$this->engine->lang['download_query']}</a></div>
			<div style="height:18px;{$display}" id="schedule_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','schedule');" style="text-decoration:none;background-color:{$background['schedule']};padding:1px;" id="user_{$user['user_id']}_schedule"><img src="images/download_schedule.png" alt="" />&nbsp;{$this->engine->lang['download_schedule']}</a></div>
			<div style="height:18px;{$display}" id="continue_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','continue');" style="text-decoration:none;background-color:{$background['continue']};padding:1px;" id="user_{$user['user_id']}_continue"><img src="images/download_continue.png" alt="" />&nbsp;{$this->engine->lang['download_continue']}</a></div>
			<div style="height:18px;{$display}" id="stopped_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','stopped');" style="text-decoration:none;background-color:{$background['stopped']};padding:1px;" id="user_{$user['user_id']}_stopped"><img src="images/download_stopped.png" alt="" />&nbsp;{$this->engine->lang['download_stopped']}</a></div>
			<div style="height:18px;{$display}" id="blocked_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','blocked');" style="text-decoration:none;background-color:{$background['blocked']};padding:1px;" id="user_{$user['user_id']}_blocked"><img src="images/download_blocked.png" alt="" />&nbsp;{$this->engine->lang['download_blocked']}</a></div>
			<div style="height:18px;{$display}" id="error_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','error');" style="text-decoration:none;background-color:{$background['error']};padding:1px;" id="user_{$user['user_id']}_error"><img src="images/download_error.png" alt="" />&nbsp;{$this->engine->lang['download_error']}</a></div>
			<div style="height:18px;{$display}" id="done_{$user['user_id']}"><img src="images/line2.gif" alt="`-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','done');" style="text-decoration:none;background-color:{$background['done']};padding:1px;" id="user_{$user['user_id']}_done"><img src="images/download_done.png" alt="" />&nbsp;{$this->engine->lang['download_done']}</a></div>
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Конец списка пользователей
	*/
	
	function users_list_bottom()
	{
		$return[] = <<<EOF
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Заголовки столбцов списка закачек
	* 
	* @param 	array	Параметры сортировки для столбцов
	*/
	
	function downloads_list_headers( $active=array() )
	{
		$return[] = <<<EOF
		<table class="files_list">
		<tr>
			<th class="sort_user"><a href="javascript:ajax_sort_items('user','{$active['user']['sort']}');">{$active['user']['img']}&nbsp;{$this->engine->lang['items_sort_user']}</a></th>
			<th class="sort_mode"><a href="javascript:ajax_sort_items('state','{$active['state']['sort']}');">{$active['state']['img']}&nbsp;</a></th>
			<th class="sort_priority"><a href="javascript:ajax_sort_items('priority','{$active['priority']['sort']}');">{$active['priority']['img']}&nbsp;</a></th>
			<th class="sort_name"><a href="javascript:ajax_sort_items('name','{$active['name']['sort']}');">{$active['name']['img']}&nbsp;{$this->engine->lang['items_sort_name']}</a></th>
			<th class="sort_size"><a href="javascript:ajax_sort_items('size','{$active['size']['sort']}');">{$active['size']['img']}&nbsp;{$this->engine->lang['items_sort_size']}</a></th>
			<th class="sort_left"><a href="javascript:ajax_sort_items('left','{$active['left']['sort']}');">{$active['left']['img']}&nbsp;{$this->engine->lang['items_sort_left']}</a></th>
			<th class="sort_time" style="border:0;"><a href="javascript:ajax_sort_items('time','{$active['time']['sort']}');">{$active['time']['img']}&nbsp;{$this->engine->lang['items_sort_time']}</a></th>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Значок сортировки
	* 
	* @param 	string	Тип сортировки
	*/
	
	function downloads_list_sort( $type="asc" )
	{
		$return[] = <<<EOF
<img src="images/sort_{$type}.gif" alt="{$this->engine->lang['events_sort_'.$type ]}" />
EOF;
		return implode( "", $return );
	}
	
	/**
	* Информационное сообщение
	* 
	* @param 	string	Текст сообщения
	*/
	
	function downloads_list_message( $message )
	{
		$return[] = <<<EOF
		<tr>
			<td colspan="6" id="message_string">({$message})</td>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Строка с информацией о закачке
	* 
	* @param 	array	Параметры закачки
	* @param 	int		Номер четности ряда
	*/
	
	function downloads_item_row( $item=array(), $row=5 )
	{
		$return[] = <<<EOF
		<tr id="item_{$item['file_id']}_row_{$row}">
			<td id="item_{$item['file_id']}_user" class="row{$row}" onclick="ajax_toggle_item_selection('{$item['file_id']}');" onmouseover="ajax_toggle_item_row(1,'{$item['file_id']}');" onmouseout="ajax_toggle_item_row(0,'{$item['file_id']}');">{$item['user_name']}</td>
			<td id="item_{$item['file_id']}_state" class="row{$row}" style="text-align:center" onclick="ajax_toggle_item_selection('{$item['file_id']}');" onmouseover="ajax_toggle_item_row(1,'{$item['file_id']}');" onmouseout="ajax_toggle_item_row(0,'{$item['file_id']}');">{$item['file_state']}</td>
			<td id="item_{$item['file_id']}_priority" class="row{$row}" style="text-align:center" onclick="ajax_toggle_item_selection('{$item['file_id']}');" onmouseover="ajax_toggle_item_row(1,'{$item['file_id']}');" onmouseout="ajax_toggle_item_row(0,'{$item['file_id']}');">{$item['file_priority']}</td>
			<td id="item_{$item['file_id']}_name" class="row{$row}" onclick="ajax_toggle_item_selection('{$item['file_id']}');" onmouseover="ajax_toggle_item_row(1,'{$item['file_id']}');" onmouseout="ajax_toggle_item_row(0,'{$item['file_id']}');">{$item['file_name']}</td>
			<td id="item_{$item['file_id']}_size" class="row{$row}" onclick="ajax_toggle_item_selection('{$item['file_id']}');" onmouseover="ajax_toggle_item_row(1,'{$item['file_id']}');" onmouseout="ajax_toggle_item_row(0,'{$item['file_id']}');" style="text-align:right;padding-right:5px;">{$item['file_size']}</td>
			<td id="item_{$item['file_id']}_left" class="row{$row}" onclick="ajax_toggle_item_selection('{$item['file_id']}');" onmouseover="ajax_toggle_item_row(1,'{$item['file_id']}');" onmouseout="ajax_toggle_item_row(0,'{$item['file_id']}');" style="text-align:right;padding-right:5px;">{$item['file_dl_left']}</td>
			<td id="item_{$item['file_id']}_time" class="row{$row}" onclick="ajax_toggle_item_selection('{$item['file_id']}');" onmouseover="ajax_toggle_item_row(1,'{$item['file_id']}');" onmouseout="ajax_toggle_item_row(0,'{$item['file_id']}');" style="text-align:right;padding-right:5px;">{$item['file_dl_time']}</td>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Окончание списка событий
	*/
	
	function downloads_list_footer()
	{
		$return[] = <<<EOF
		</table>
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Текущее состояние закачки
	* 
	* @param 	string	Идентификатор состояния
	*/
	
	function item_state( $state="blocked" )
	{
		$return[] = <<<EOF
<img src="images/file_{$state}.png" alt="{$this->engine->lang['download_'.$state ]}" title="{$this->engine->lang['download_'.$state ]}" />
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Текуший приоритет закачки
	* 
	* @param 	string	Идентификатор приоритета
	*/
	
	function item_priority( $priority="med" )
	{
		$return[] = <<<EOF
<img src="images/priority_{$priority}.png" alt="{$this->engine->lang['priority_'.$priority ]}" title="{$this->engine->lang['priority_'.$priority ]}" />
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Кнопка открытия и скрытия поля с описанием
	* и само поле
	* 
	* @param 	string	Описание файла
	* @param 	int		Идентификатор ссылки
	*/
	
	function desc_button( $desc, $id )
	{
		$display = $desc ? "" : "style='display:none'";
	
	if( $desc )
	{
		$return[] = <<<EOF
<a href="javascript:ajax_toggle_desc_field(0,'{$id}')" id="desc_link_{$id}"><img src="images/desc_close.png" alt="{$this->engine->lang['js_desc_field_close']}" title="{$this->engine->lang['js_desc_field_close']}" /></a>
EOF;
	}
	else 
	{
		$return[] = <<<EOF
<a href="javascript:ajax_toggle_desc_field(1,'{$id}')" id="desc_link_{$id}"><img src="images/desc_open.png" alt="{$this->engine->lang['js_desc_field_open']}" title="{$this->engine->lang['js_desc_field_open']}" /></a>
EOF;
	}
	
		return implode( "\n", $return )."\n";
	
	}
	
	/**
	* Меню перемещения по страницам.
	*/
	
	function page_menu()
	{
		$return[] = <<<EOF
		<div class="inner_button"><a href="javascript:ajax_set_page('first',{$this->engine->input['page']},'download',active_user,active_sub);" title='{$this->engine->lang['page_first']}'><img src='images/page_first.png' alt='{$this->engine->lang['page_first']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_set_page('prev',{$this->engine->input['page']},'download',active_user,active_sub);" title='{$this->engine->lang['page_previous']}'><img src='images/page_prev.png' alt='{$this->engine->lang['page_previous']}' /></a></div>
		<form action="" method="post" onsubmit="ajax_set_page(this.page.value,{$this->engine->input['page']},'download',active_user,active_sub);return false;">
			<div style="float:left;"><input class="text_tiny" style="margin:7px 2px 0 2px;" name="page" value="{$this->engine->input['page']}" type="text" /></div>
		</form>
		<div class="inner_button"><a href="javascript:ajax_set_page('next',{$this->engine->input['page']},'download',active_user,active_sub);" title='{$this->engine->lang['page_next']}'><img src='images/page_next.png' alt='{$this->engine->lang['page_next']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_set_page('last',{$this->engine->input['page']},'download',active_user,active_sub);" title='{$this->engine->lang['page_last']}'><img src='images/page_last.png' alt='{$this->engine->lang['page_last']}' /></a></div>
EOF;
		return implode( "\n", $return )."\n";
	}

	
}

?>