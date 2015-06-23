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

class template_schedule
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
		<div class="inner_button"><a href="javascript:ajax_event_add();" title='{$this->engine->lang['event_add']}'><img src='images/event_add.png' alt='{$this->engine->lang['event_add']}' /> {$this->engine->lang['event_add_button']}</a></div>
		<div class="inner_button"><a href="javascript:ajax_event_edit();" title='{$this->engine->lang['event_edit']}'><img src='images/event_edit.png' alt='{$this->engine->lang['event_edit']}' /> {$this->engine->lang['event_edit_button']}</a></div>
		<div class="inner_button"><a href="javascript:ajax_event_delete();" title='{$this->engine->lang['event_delete']}'><img src='images/event_delete.png' alt='{$this->engine->lang['event_delete']}' /> {$this->engine->lang['event_delete_button']}</a></div>
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
		<div class="inner_menu" id="menu_left">
EOF;
	if( $this->engine->member['user_admin'] )
	{
		$return[] = <<<EOF
			<div class="inner_button"><a href="javascript:ajax_show_params();" title='{$this->engine->lang['time_edit']}'><img src='images/time_edit.png' alt='{$this->engine->lang['time_edit']}' /> {$this->engine->lang['time_edit_button']}</a></div>
EOF;
	}
	
		$return[] = <<<EOF
			<div class="inner_button"><a href="javascript:ajax_show_limits();" title='{$this->engine->lang['time_limit']}'><img src='images/time_limit.png' alt='{$this->engine->lang['time_limit']}' /> {$this->engine->lang['time_limit_button']}</a></div>
		</div>
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
	
	function users_list_item( $user=array(), $icon='single', $active=FALSE, $hidden=FALSE, $sub='today' )
	{
		$background['all']	  = ( $active and $sub == 'all'    ) ? "#ededed" : "transparent";
		$background['pall']   = ( $active and $sub == 'pall'   ) ? "#ededed" : "transparent";
		$background['pmonth'] = ( $active and $sub == 'pmonth' ) ? "#ededed" : "transparent";
		$background['pweek']  = ( $active and $sub == 'pweek'  ) ? "#ededed" : "transparent";
		$background['pday']   = ( $active and $sub == 'pday'   ) ? "#ededed" : "transparent";
		$background['today']  = ( $active and $sub == 'today'  ) ? "#ededed" : "transparent";
		$background['nday']   = ( $active and $sub == 'nday'   ) ? "#ededed" : "transparent";
		$background['nweek']  = ( $active and $sub == 'nweek'  ) ? "#ededed" : "transparent";
		$background['nmonth'] = ( $active and $sub == 'nmonth' ) ? "#ededed" : "transparent";
		$background['nall']   = ( $active and $sub == 'nall'   ) ? "#ededed" : "transparent";
		
		$display = $hidden ? "display:none;" : "";
		
		$script = $hidden ? "<a href=\"javascript:ajax_toggle_user('{$user['user_id']}',true);\"><img src='images/plus4.gif' alt='[+]-' /></a>"
						  : "<a href=\"javascript:ajax_toggle_user('{$user['user_id']}',false);\"><img src='images/minus3.gif' alt='[-]-' /></a>";
		
		$return[] = <<<EOF
		<div style="padding:2px;">
			<div style="height:18px;"><span id="root_{$user['user_id']}">{$script}</span> <a href="javascript:ajax_change_user('{$user['user_id']}','all');" style="text-decoration:none;background-color:{$background['all']};padding:1px;" id="user_{$user['user_id']}_all"><img src="images/user_{$icon}.png" alt="" />&nbsp;<b>{$user['user_name']}</b></a></div>
			<div style="height:18px;{$display}" id="pall_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','pall');" style="text-decoration:none;background-color:{$background['pall']};padding:1px;" id="user_{$user['user_id']}_pall"><img src="images/events.png" alt="" />&nbsp;{$this->engine->lang['events_pall']}</a></div>
			<div style="height:18px;{$display}" id="pmonth_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','pmonth');" style="text-decoration:none;background-color:{$background['pmonth']};padding:1px;" id="user_{$user['user_id']}_pmonth"><img src="images/events.png" alt="" />&nbsp;{$this->engine->lang['events_pmonth']}</a></div>
			<div style="height:18px;{$display}" id="pweek_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','pweek');" style="text-decoration:none;background-color:{$background['pweek']};padding:1px;" id="user_{$user['user_id']}_pweek"><img src="images/events.png" alt="" />&nbsp;{$this->engine->lang['events_pweek']}</a></div>
			<div style="height:18px;{$display}" id="pday_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','pday');" style="text-decoration:none;background-color:{$background['pday']};padding:1px;" id="user_{$user['user_id']}_pday"><img src="images/events.png" alt="" />&nbsp;{$this->engine->lang['events_pday']}</a></div>
			<div style="height:18px;{$display}" id="today_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','today');" style="text-decoration:none;background-color:{$background['today']};padding:1px;" id="user_{$user['user_id']}_today"><img src="images/events.png" alt="" />&nbsp;{$this->engine->lang['events_today']}</a></div>
			<div style="height:18px;{$display}" id="nday_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','nday');" style="text-decoration:none;background-color:{$background['nday']};padding:1px;" id="user_{$user['user_id']}_nday"><img src="images/events.png" alt="" />&nbsp;{$this->engine->lang['events_nday']}</a></div>
			<div style="height:18px;{$display}" id="nweek_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','nweek');" style="text-decoration:none;background-color:{$background['nweek']};padding:1px;" id="user_{$user['user_id']}_nweek"><img src="images/events.png" alt="" />&nbsp;{$this->engine->lang['events_nweek']}</a></div>
			<div style="height:18px;{$display}" id="nmonth_{$user['user_id']}"><img src="images/line3.gif" alt="|-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','nmonth');" style="text-decoration:none;background-color:{$background['nmonth']};padding:1px;" id="user_{$user['user_id']}_nmonth"><img src="images/events.png" alt="" />&nbsp;{$this->engine->lang['events_nmonth']}</a></div>
			<div style="height:18px;{$display}" id="nall_{$user['user_id']}"><img src="images/line2.gif" alt="`-" /> <a href="javascript:ajax_change_user('{$user['user_id']}','nall');" style="text-decoration:none;background-color:{$background['nall']};padding:1px;" id="user_{$user['user_id']}_nall"><img src="images/events.png" alt="" />&nbsp;{$this->engine->lang['events_nall']}</a></div>
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
			<th class="sort_time"><a href="javascript:ajax_sort_events('time','{$active['time']['sort']}');">{$active['time']['img']}&nbsp;{$this->engine->lang['events_sort_time']}</a></th>
			<th class="sort_type"><a href="javascript:ajax_sort_events('type','{$active['type']['sort']}');">{$active['type']['img']}&nbsp;{$this->engine->lang['events_sort_type']}</a></th>
			<th class="sort_state"><a href="javascript:ajax_sort_events('state','{$active['state']['sort']}');">{$active['state']['img']}&nbsp;{$this->engine->lang['events_sort_state']}</a></th>
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
			<td colspan="4" id="message_string">({$message})</td>
		</tr>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Строка с информацией о событии
	* 
	* @param 	array	Параметры события
	* @param 	int		Номер четности ряда
	*/
	
	function schedule_event_row( $event=array(), $row=5 )
	{
		$return[] = <<<EOF
		<tr id="event_{$event['event_id']}_row_{$row}">
			<td id="event_{$event['event_id']}_user" class="row{$row}" onclick="ajax_toggle_event_selection('{$event['event_id']}');" onmouseover="ajax_toggle_event_row(1,'{$event['event_id']}');" onmouseout="ajax_toggle_event_row(0,'{$event['event_id']}');">{$event['user_name']}</td>
			<td id="event_{$event['event_id']}_time" class="row{$row}" onclick="ajax_toggle_event_selection('{$event['event_id']}');" onmouseover="ajax_toggle_event_row(1,'{$event['event_id']}');" onmouseout="ajax_toggle_event_row(0,'{$event['event_id']}');">{$event['event_time']}</td>
			<td id="event_{$event['event_id']}_type" class="row{$row}" onclick="ajax_toggle_event_selection('{$event['event_id']}');" onmouseover="ajax_toggle_event_row(1,'{$event['event_id']}');" onmouseout="ajax_toggle_event_row(0,'{$event['event_id']}');">{$event['event_type']}</td>
			<td id="event_{$event['event_id']}_state" class="row{$row}" onclick="ajax_toggle_event_selection('{$event['event_id']}');" onmouseover="ajax_toggle_event_row(1,'{$event['event_id']}');" onmouseout="ajax_toggle_event_row(0,'{$event['event_id']}');">{$event['event_state']}</td>
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
		<div class="inner_button"><a href="javascript:ajax_set_page('first',{$this->engine->input['page']},'schedule',active_user,active_sub);" title='{$this->engine->lang['page_first']}'><img src='images/page_first.png' alt='{$this->engine->lang['page_first']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_set_page('prev',{$this->engine->input['page']},'schedule',active_user,active_sub);" title='{$this->engine->lang['page_previous']}'><img src='images/page_prev.png' alt='{$this->engine->lang['page_previous']}' /></a></div>
		<form action="" method="post" onsubmit="ajax_set_page(this.page.value,{$this->engine->input['page']},'schedule',active_user,active_sub);return false;">
			<div style="float:left;"><input class="text_tiny" style="margin:7px 2px 0 2px;" name="page" value="{$this->engine->input['page']}" type="text" /></div>
		</form>
		<div class="inner_button"><a href="javascript:ajax_set_page('next',{$this->engine->input['page']},'schedule',active_user,active_sub);" title='{$this->engine->lang['page_next']}'><img src='images/page_next.png' alt='{$this->engine->lang['page_next']}' /></a></div>
		<div class="inner_button"><a href="javascript:ajax_set_page('last',{$this->engine->input['page']},'schedule',active_user,active_sub);" title='{$this->engine->lang['page_last']}'><img src='images/page_last.png' alt='{$this->engine->lang['page_last']}' /></a></div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
}

?>