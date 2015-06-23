<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Шаблон для секции "Журнал"
*/

/**
* Класс, содержащий функции
* для секции журнала событий.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class template_log
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
		<div class="inner_button"><a href="javascript:ajax_log_info();" title='{$this->engine->lang['log_info']}'><img src='images/log_info.png' alt='{$this->engine->lang['log_info']}' /> {$this->engine->lang['log_info_button']}</a></div>
		<div class="inner_button"><a href="javascript:ajax_log_delete();" title='{$this->engine->lang['log_delete']}'><img src='images/log_delete.png' alt='{$this->engine->lang['log_delete']}' /> {$this->engine->lang['log_delete_button']}</a></div>
		<div class="inner_button"><a href="javascript:ajax_log_clear();" title="{$this->engine->lang['log_clear']}"><img src='images/log_clear.png' alt='{$this->engine->lang['log_clear']}' /> {$this->engine->lang['log_clear_button']}</a></div>
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
	* Начало списка типов событий - меню
	*/
	
	function groups_list_top()
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
	
	function groups_list_item( $user=array(), $icon='single', $active=FALSE, $hidden=FALSE, $sub='all' )
	{
		$background['all']	  = ( $active and $sub == 'all'    ) ? "#ededed" : "transparent";
		$background['system'] = ( $active and $sub == 'system' ) ? "#ededed" : "transparent";
		$background['error']  = ( $active and $sub == 'error'  ) ? "#ededed" : "transparent";
		$background['warn']   = ( $active and $sub == 'warn'   ) ? "#ededed" : "transparent";
		$background['info']   = ( $active and $sub == 'info'   ) ? "#ededed" : "transparent";
		
		$display = $hidden ? "display:none;" : "";
		
		$script = $hidden ? "<a href=\"javascript:ajax_toggle_user('{$user['user_id']}',true);\"><img src='images/plus4.gif' alt='[+]-' /></a>"
						  : "<a href=\"javascript:ajax_toggle_user('{$user['user_id']}',false);\"><img src='images/minus3.gif' alt='[-]-' /></a>";
		
		$return[] = <<<EOF
		<div style="padding:2px;">
			<div style="height:18px;"><span id="root_{$user['user_id']}">{$script}</span> <a href="javascript:ajax_change_group('{$user['user_id']}','all');" style="text-decoration:none;background-color:{$background['all']};padding:1px;" id="group_{$user['user_id']}_all"><img src="images/user_{$icon}.png" alt="" />&nbsp;<b>{$user['user_name']}</b></a></div>
			<div style="height:18px;{$display}" id="error_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_group('{$user['user_id']}','error');" style="text-decoration:none;background-color:{$background['error']};padding:1px;" id="group_{$user['user_id']}_error"><img src="images/group_error.png" alt="" />&nbsp;{$this->engine->lang['groups_error']}</a></div>
			<div style="height:18px;{$display}" id="warn_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_group('{$user['user_id']}','warn');" style="text-decoration:none;background-color:{$background['warn']};padding:1px;" id="group_{$user['user_id']}_warn"><img src="images/group_warn.png" alt="" />&nbsp;{$this->engine->lang['groups_warn']}</a></div>
			<div style="height:18px;{$display}" id="info_{$user['user_id']}"><img src="images/line2.gif" alt="`-" /> <a href="javascript:ajax_change_group('{$user['user_id']}','info');" style="text-decoration:none;background-color:{$background['info']};padding:1px;" id="group_{$user['user_id']}_info"><img src="images/group_info.png" alt="" />&nbsp;{$this->engine->lang['groups_info']}</a></div>
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Конец списка типов событий
	*/
	
	function groups_list_bottom()
	{
		$return[] = <<<EOF
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Заголовки столбцов списка событий
	* 
	* @param 	array	Параметры сортировки для столбцов
	*/
	
	function events_list_headers( $active=array() )
	{
		$return[] = <<<EOF
		<table class="files_list">
		<tr>
			<th class="sort_user"><a href="javascript:ajax_sort_events('user','{$active['user']['sort']}');">{$active['user']['img']}&nbsp;{$this->engine->lang['events_sort_user']}</a></th>
			<th class="sort_type"><a href="javascript:ajax_sort_events('type','{$active['type']['sort']}');">{$active['type']['img']}&nbsp;{$this->engine->lang['events_sort_type']}</a></th>
			<th class="sort_code"><a href="javascript:ajax_sort_events('code','{$active['code']['sort']}');">{$active['code']['img']}&nbsp;{$this->engine->lang['events_sort_code']}</a></th>
			<th class="sort_date"><a href="javascript:ajax_sort_events('time','{$active['time']['sort']}');">{$active['time']['img']}&nbsp;{$this->engine->lang['events_sort_time']}</a></th>
			<th class="sort_msg"><a href="javascript:ajax_sort_events('msg','{$active['msg']['sort']}');">{$active['msg']['img']}&nbsp;{$this->engine->lang['events_sort_msg']}</a></th>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Значок сортировки
	* 
	* @param 	string	Тип сортировки
	*/
	
	function events_list_sort( $type="asc" )
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
	
	function events_list_message( $message )
	{
		$return[] = <<<EOF
		<tr>
			<td colspan="5" id="message_string">({$message})</td>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Пиктограмма типа события
	* 
	* @param 	string	Тип события
	*/
	
	function log_type( $type='info' )
	{
	
		$return[] = <<<EOF
		<img src="images/group_{$type}.png" alt="{$this->engine->lang['log_'.$type ]}" title="{$this->engine->lang['log_'.$type ]}" />
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Строка с информацией о событии
	* 
	* @param 	array	Параметры события
	* @param 	int		Номер четности ряда
	*/
	
	function log_event_row( $event=array(), $row=5 )
	{
		$return[] = <<<EOF
		<tr id="event_{$event['log_id']}_row_{$row}">
			<td id="event_{$event['log_id']}_user" class="row{$row}" onclick="ajax_toggle_event_selection('{$event['log_id']}');" onmouseover="ajax_toggle_event_row(1,'{$event['log_id']}');" onmouseout="ajax_toggle_event_row(0,'{$event['log_id']}');">{$event['user_name']}</td>
			<td id="event_{$event['log_id']}_type" class="row{$row}" style="text-align:center" onclick="ajax_toggle_event_selection('{$event['log_id']}');" onmouseover="ajax_toggle_event_row(1,'{$event['log_id']}');" onmouseout="ajax_toggle_event_row(0,'{$event['log_id']}');">{$event['log_type']}</td>
			<td id="event_{$event['log_id']}_code" class="row{$row}" onclick="ajax_toggle_event_selection('{$event['log_id']}');" onmouseover="ajax_toggle_event_row(1,'{$event['log_id']}');" onmouseout="ajax_toggle_event_row(0,'{$event['log_id']}');">{$event['log_code']}</td>
			<td id="event_{$event['log_id']}_time" class="row{$row}" onclick="ajax_toggle_event_selection('{$event['log_id']}');" onmouseover="ajax_toggle_event_row(1,'{$event['log_id']}');" onmouseout="ajax_toggle_event_row(0,'{$event['log_id']}');">{$event['log_time']}</td>
			<td id="event_{$event['log_id']}_msg" class="row{$row}" onclick="ajax_toggle_event_selection('{$event['log_id']}');" onmouseover="ajax_toggle_event_row(1,'{$event['log_id']}');" onmouseout="ajax_toggle_event_row(0,'{$event['log_id']}');">{$event['log_msg']}</td>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Окончание списка событий
	*/
	
	function events_list_footer()
	{
		$return[] = <<<EOF
		</table>
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Текущее состояние события
	* 
	* @param 	string	Идентификатор состояния
	*/
	
	function event_state( $state="blocked" )
	{
		$return[] = <<<EOF
<span class="event_state_{$state}">{$this->engine->lang['event_'.$state]}</span>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Меню перемещения по страницам.
	*/
	
	function page_menu()
	{
		$return[] = <<<EOF
		<div class="inner_button"><a href="javascript:ajax_set_page('first',{$this->engine->input['page']},'log',active_group,active_sub);" title='{$this->engine->lang['page_first']}'><img src='images/page_first.png' alt='{$this->engine->lang['page_first']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_set_page('prev',{$this->engine->input['page']},'log',active_group,active_sub);" title='{$this->engine->lang['page_previous']}'><img src='images/page_prev.png' alt='{$this->engine->lang['page_previous']}' /></a></div>
		<form action="" method="post" onsubmit="ajax_set_page(this.page.value,{$this->engine->input['page']},'log',active_group,active_sub);return false;">
			<div style="float:left;"><input class="text_tiny" style="margin:7px 2px 0 2px;" name="page" value="{$this->engine->input['page']}" type="text" /></div>
		</form>
		<div class="inner_button"><a href="javascript:ajax_set_page('next',{$this->engine->input['page']},'log',active_group,active_sub);" title='{$this->engine->lang['page_next']}'><img src='images/page_next.png' alt='{$this->engine->lang['page_next']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_set_page('last',{$this->engine->input['page']},'log',active_group,active_sub);" title='{$this->engine->lang['page_last']}'><img src='images/page_last.png' alt='{$this->engine->lang['page_last']}' /></a></div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
}

?>