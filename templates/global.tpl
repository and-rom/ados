<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Глобальный шаблон
*/

/**
* Класс, содержащий функции
* глобального шаблона.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class template_global
{
	/**
	* Основной шаблон
	*/
	
	function main_template()
	{
		$rel_path = $this->engine->input['install_update'] === TRUE ? "../" : "";
		
		$return[] = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>

<title>{$this->engine->config['__engine__']['name']} {$this->engine->config['__engine__']['version']} - {$this->engine->lang[ "sect_".$this->engine->input['tab']."_lower" ]}</title>

<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<style type="text/css" media="all">@import url( "stylesheet.css" );</style>
<!--JAVA_SCRIPTS-->
{$this->css_png_behavior()}
<link rel="shortcut icon" href="{$rel_path}favicon.ico" />
</head>

<body>

<div id="wait_bar" style="display:none;"><img src='{$rel_path}images/please_wait.gif' style="border: 1px solid black;" alt='{$this->engine->lang['please_wait']}' /></div>
<div id="please_wait"></div>

<div class="main" id="main">

<div id="update_container_18">
<!--LANGUAGE-->
</div>

<div class="logo">
	<div id="logo"></div>
</div>
<!--MENU-->
<!--CONTENT-->
<div style="padding:0 0 10px 0;">
{$this->copyright( 2007, date( 'Y' ) )}
{$this->logout()}
<!--DEBUG_INFO-->
</div>
</div>

<div id="ajax_container">
</div>

</body>
</html>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Меню выбора языка системы
	* 
	* @param 	string	HTML код выпадающего списка
	*/
	
	function lang_menu( $dropdown="" )
	{
		$return[] = <<<EOF
<form action="{$this->engine->base_url}" method="post" onsubmit="ajax_change_lang(this); return false;">
<div style="float:right;padding-top:15px;">
	<input type="hidden" name="tab" value="{$this->engine->input['tab']}" />
{$dropdown}
	<input type="submit" class="button" value="{$this->engine->lang['ok']}" style="height:22px;" />
</div>
</form>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Обработка PNG для IE
	*/
	
	function css_png_behavior()
	{
		if( PARSE_PNG !== TRUE or $this->engine->config['parse_png'] === FALSE ) return "";
		
		$return[] = <<<EOF
<script type="text/javascript">window.attachEvent( "onload", parse_png );</script>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Java скрипт (ссылка)
	* 
	* @param 	string	Название скрипта
	*/
	
	function java_script_link( $script="" )
	{
		$return[] = <<<EOF
<script type="text/javascript" src="ajax/ajax_{$script}.js"></script>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Java скрипт (встроенный)
	* 
	* @param 	string	Код скрипта
	*/
	
	function java_script_embed( $script="" )
	{
		$return[] = <<<EOF
<script type="text/javascript">
	{$script}
</script>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Меню с закладками секций
	* 
	* @param 	string	Содержимое меню
	*/
	
	function menu( $content="" )
	{
		$return[] = <<<EOF
	<div class="menu">
{$content}
	</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Кнопка меню
	* 
	* @param 	string	Название секции
	* @param 	string	Состояние кнопки
	*/
	
	function menu_button( $name="", $state="off" )
	{
		$style = $this->engine->member['user_admin'] ? ( $state == "on" ? "style='width:130px;'" : "style='width:140px;'" ) : "";
		
		$return[] = <<<EOF
	<div class="menu_button_{$state}" {$style}>
EOF;
	if( $state == "on" )
	{
		$return[] = <<<EOF
		<img src="images/sect_{$name}.png" alt="{$this->engine->lang[ 'sect_'.$name ]}" /> {$this->engine->lang[ 'sect_'.$name ]}
	</div>
EOF;
	}
	else
	{
		$return[] = <<<EOF
		<a href="{$this->engine->base_url}tab={$name}"><img src="images/sect_{$name}.png" alt="{$this->engine->lang[ 'sect_'.$name ]}" /> {$this->engine->lang[ 'sect_'.$name ]}</a>
	</div>
EOF;
	}
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Информация о странице
	* 
	* @param 	string	Название страницы
	* @param 	string	Описание страницы
	*/
	
	function page_info( $title="", $desc="" )
	{
		$return[] = <<<EOF
		<div class="page_info">
			<div id="title">{$title}</div>
EOF;
	if( $desc )
	{
		$return[] = <<<EOF
			{$desc}
EOF;
	}
		$return[] = <<<EOF
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Ссылка на удаление блокировочного файла
	* 
	* @param 	string	Идентификатор файла
	*/
	
	function delete_lock_file( $name="" )
	{
		$return[] = <<<EOF
<a href="javascript:ajax_delete_lock_file('{$name}');">{$this->engine->lang['here']}</a>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Системное сообщение
	* 
	* @param 	string	Текст сообщения
	* @param 	string	Тип сообщения
	* @param 	string	Дополнительные параметры
	*/
	
	function system_message( $text="", $class="", $misc="" )
	{
		$return[] = <<<EOF
		<div class="system_message_{$class}" {$misc}>
			{$text}
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Авторские права
	* 
	* @param 	int		Начальный год
	* @param 	int		Текущий год
	*/
	
	function copyright( $start=2007, $current=2008 )
	{
	
	if( $this->engine->input['install_update'] === TRUE or !$this->engine->member['user_id'] )
	{
		$return[] = <<<EOF
	<div class="copyright">
		{$this->engine->config['__engine__']['name']} {$this->engine->config['__engine__']['version']}
EOF;
	}
	else 
	{
		$return[] = <<<EOF
	<div class="copyright">
		<a href="javascript:ajax_about_ados();" title="{$this->engine->lang['about_ados']}">{$this->engine->config['__engine__']['name']}</a> {$this->engine->config['__engine__']['version']}
EOF;
	}	

	if( $start == $current )
	{
		$return[] = <<<EOF
		&copy; {$start}
EOF;
	}
	else 
	{
		$return[] = <<<EOF
		&copy; {$start}&mdash;{$current}
EOF;
	}

		$return[] = <<<EOF
		<a href="http://dini.su">{$this->engine->config['__engine__']['author']}</a>
	</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Ссылка на выход из системы
	*/
	
	function logout()
	{
	
	if( ( $this->engine->input['install_update'] === TRUE ) or !$this->engine->member['user_id'] ) return "";
	
		$return[] = <<<EOF
<div class="logout"><img src="images/log_out.png" alt="{$this->engine->lang['log_out']}" /><a href="javascript:ajax_logout();">{$this->engine->lang['log_out']}</a></div>
EOF;
	
	return implode( "\n", $return )."\n";
	
	}
	
	/**
	* Кнопка управления элементом
	* 
	* @param 	int		Идентификатор элемента
	* @param 	string	Тип элемента
	* @param 	string	Код действия
	*/
	
	function element_button( $id=0, $type="", $code="" )
	{
		$return[] = <<<EOF
		<a href="javascript:ajax_{$type}_{$code}('{$id}')" title="{$this->engine->lang[ $type.'_'.$code ]}"><img src="images/button_{$code}.png" alt="{$code}" /></a>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Начало таблицы
	* 
	* @param 	string	Заголовок таблицы
	* @param 	string	Ширина таблицы
	* @param 	string	Дополнительные параметры
	* @param 	string	Дополнительный код
	* @param 	string	Дополнительные параметры обводки
	*/
	
	function table_start( $caption="", $width="100%", $misc="", $html="", $bmisc="" )
	{
		$width = is_numeric( $width ) ? $width."px" : $width;
		
		if( !preg_match( "#width:\s*\d+[px|%]#", $misc ) )
		{
			$misc = preg_match( "#style\s*=\s*['|\"]#", $misc ) ? preg_replace( "#(style\s*=\s*['|\"])#", "\\1width:{$width};", $misc ) : "style='width:{$width};' {$misc}";
		}
		
		$return[] = <<<EOF
		<div class='tableborder' {$bmisc}>
EOF;
	if( $html )
	{
		$return[] = <<<EOF
		<div style="float:right;text-align:right;padding:4px;">
{$html}
		</div>
EOF;
	}
		
	if( $caption )
	{
		$return[] = <<<EOF
		<div class='tablecaption'>
			{$caption}
		</div>
EOF;
	}
		$return[] = <<<EOF
		<table cellspacing="0" cellpadding="0" {$misc}>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Конец ряда - начало ряда
	* 
	* @param	bool	Конец ряда
	* @param 	bool	Начало ряда
	* @param 	string	Дополнительные параметры
	*/
	
	function table_tr( $end=1, $start=1, $misc="" )
	{
	
	if( $end == 1 )
	{
		$return[] = <<<EOF
		</tr>
EOF;
	}
		
	if( $start == 1 )
	{
		$return[] = <<<EOF
		<tr {$misc}>
EOF;
	}
		
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Заголовок столбца
	* 
	* @param 	string	Имя столбца
	* @param 	string	Стиль ячейки
	* @param 	string	Ширина столбца
	* @param 	string	Дополнительные параметры
	*/
	
	function table_th( $caption="&nbsp;", $style="row1", $width="50%", $misc="" )
	{
		$width = is_numeric( $width ) ? $width."px" : $width;
		
		if( !preg_match( "#width:\s*\d+[px|%]#", $misc ) )
		{
			$misc = preg_match( "#style\s*=\s*['|\"]#", $misc ) ? preg_replace( "#(style\s*=\s*['|\"])#", "\\1width:{$width};", $misc ) : "style='width:{$width};'  {$misc}";
		}
		
		$caption = $caption ? $caption : "&nbsp;";
		
		$return[] = <<<EOF
			<th class="{$style}" {$misc} >
				{$caption}
			</th>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Ячейка таблицы
	* 
	* @param 	string	Содержимое ячейки
	* @param 	string	Стиль ячейки
	* @param 	string	Ширина ячейки
	* @param 	string	Дополнительные параметры
	*/
	
	function table_td( $content="", $style="row1", $width="50%", $misc="" )
	{
		$width = is_numeric( $width ) ? $width."px" : $width;
		
		if( !preg_match( "#width:\s*\d+[px|%]#", $misc ) )
		{
			$misc = preg_match( "#style\s*=\s*['|\"]#", $misc ) ? preg_replace( "#(style\s*=\s*['|\"])#", "\\1width:{$width};", $misc ) : "style='width:{$width};' {$misc}";
		}
		
		$return[] = <<<EOF
			<td class="{$style}" {$misc} >
{$content}
			</td>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Конец таблицы
	*/
	
	function table_end()
	{
		$return[] = <<<EOF
		</table>
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Начало формы
	* 
	* @param 	string	Дополнительные параметры
	*/
	
	function form_start( $misc="" )
	{
		$return[] = <<<EOF
		<form action="" method="post" {$misc}>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Скрытое поле
	* 
	* @param 	string	Имя
	* @param 	string	Значение
	*/
	
	function form_hidden( $name="", $value="" )
	{
		$return[] = <<<EOF
			<div><input type="hidden" name="{$name}" id="hidden_{$name}" value="{$value}" /></div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Поле ввода - одна строка
	* 
	* @param 	string	Имя поля
	* @param 	string	Значение поля
	* @param 	string	Длина поля
	* @param 	string	Тип поля
	* @param 	string	Дополнительные параметры
	*/
	
	function form_text( $name="", $value="", $length="large", $type="text", $misc="" )
	{
		$return[] = <<<EOF
			<input type="{$type}" class="text_{$length}" name="{$name}" value="{$value}" {$misc} />
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Поле загрузки
	* 
	* @param 	string	Имя поля
	* @param 	string	Длина поля
	* @param 	string	Дополнительные параметры
	*/
	
	function form_upload( $name="", $length="large", $misc="" )
	{
		$return[] = <<<EOF
			<input type="file" class="text_upload_{$length}" size="34" name="{$name}" {$misc} />
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Поле ввода - многострочное
	* 
	* @param 	string	Имя поля
	* @param 	string	Значение поля
	* @param 	string	Дополнительные параметры
	*/
	
	function form_textarea( $name="", $value="", $length="large", $misc="" )
	{
		$return[] = <<<EOF
			<textarea class="textarea_{$length}" name="{$name}" {$misc}>{$value}</textarea>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Ниспадающий список
	* 
	* @param 	string	Имя списка
	* @param 	array	Значения спсика
	* @param 	string	ID значения по умолчанию
	* @param 	string	Длина поля
	* @param 	string	Дополнительные параметры
	*/
	
	function form_dropdown( $name="", $options=array(), $selected="", $length="large", $misc="" )
	{
		$return[] = <<<EOF
			<select class="select_{$length}" name="{$name}" {$misc}>
EOF;
	foreach( $options as $value => $text )
	{
		$checked = $selected == $value ? "selected='selected'" : "";
		
		$return[] = <<<EOF
				<option value="{$value}" {$checked}>{$text}</option>
EOF;
	}
		$return[] = <<<EOF
			</select>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Поле мультивыбора
	* 
	* @param 	string	Имя поля
	* @param 	array	Значения поля
	* @param 	array	Значения по умолчанию
	* @param 	string	Длина поля
	* @param 	string	Дополнительные параметры
	*/
	
	function form_multiselect( $name="", $options=array(), $selected=array(), $length="large", $misc="" )
	{
		$return[] = <<<EOF
			<select class="multiselect_{$length}" name="{$name}[]" multiple="multiple" {$misc}>
EOF;
	foreach( $options as $value => $text )
	{
		$checked = in_array( $value, $selected ) ? "selected='selected'" : "";
		
		$return[] = <<<EOF
				<option value="{$value}" {$checked}>{$text}</option>
EOF;
	}
		$return[] = <<<EOF
			</select>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Переключатель
	* 
	* @param 	string	Имя переключателя
	* @param 	string	Значение переключателя
	* @param 	bool	Состояние переключателя
	* @param 	string	Подпись к переключателю
	* @param 	string	Дополнительные параметры
	*/
	
	function form_radio( $name="", $value="", $checked=0, $label="", $misc="" )
	{
		$checked = $checked == 1 ? "checked='checked'" : "";
		
		$return[] = <<<EOF
			<input type="radio" class="checkbox" name="{$name}" value="{$value}" id="{$name}_{$value}" {$checked} {$misc} />
EOF;
	if( $label )
	{
		$return[] = <<<EOF
<label class="label" for="{$name}_{$value}">{$label}</label>
EOF;
	}
		return implode( "", $return )."\n";
	}
	
	/**
	* Флажок
	* 
	* @param 	string	Имя флажка
	* @param 	bool	Значение флажка
	* @param 	string	Подпись к флажку
	* @param 	string	Дополнительные параметры
	*/
	
	function form_checkbox( $name="", $checked=0, $label="", $misc="" )
	{
		$checked = $checked == 1 ? "checked='checked'" : "";
		
		$return[] = <<<EOF
			<input type="checkbox" class="checkbox" name="{$name}" {$checked} {$misc} />
EOF;
	if( $label )
	{
		$return[] = <<<EOF
			<label class="label" for="{$name}">{$label}</label>
EOF;
	}
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Переключатель "ДА" - "НЕТ"
	* 
	* @param 	string	Имя переключателя
	* @param 	bool	"ДА"
	* @param 	array	Дополнительные параметры
	*/
	
	function form_yes_no( $name="", $yes="", $misc=array() )
	{
		$selected['yes'] = $yes ? "checked='checked'" : "";
		$selected['no']  = $yes ? "" : "checked='checked'";
		
		$return[] = <<<EOF
			<input type="radio" name="{$name}" value="1" id="{$name}_yes" {$selected['yes']} {$misc['yes']['switch']}/><label for="{$name}_yes" class="label_yes" {$misc['yes']['label']}>{$this->engine->lang['yes']}</label>&nbsp;
			<input type="radio" name="{$name}" value="0" id="{$name}_no" {$selected['no']} {$misc['no']['switch']}/><label for="{$name}_no" class="label_no" {$misc['no']['label']}>{$this->engine->lang['no']}</label>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Кнопка - отправка запроса
	* 
	* @param 	string	Имя кнопки
	* @param 	string	Дополнительные параметры
	*/
	
	function form_button( $value="", $type="submit", $misc="" )
	{
		$return[] = <<<EOF
			<input type="{$type}" class="button" value="{$value}" {$misc} />
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Отдельный ряд с кнопкой
	* 
	* @param 	string	Кнопка
	* @param 	string	Дополнительные параметры для рамки
    * @param 	string	Дополнительные параметры для формы
	*/
	
	function form_row_submit( $button="", $tblmisc="", $divmisc="" )
	{
		$return[] = <<<EOF
			<div class="tableborder" {$tblmisc}>
			<div class="form_submit" {$divmisc}>
{$button}
			</div>
			</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Конец формы
	*/
	
	function form_end()
	{
		$return[] = <<<EOF
		</form>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Начало блокового элемента
	* 
	* @param 	string	Имя элемента
	* @param 	string	Идентификатор элемента
	* @param 	string	Имя класса
	*/
	
	function div_start( $id="", $class="" )
	{
		$return[] = <<<EOF
	<div id="{$id}" class="{$class}">
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Конец блокового элемента
	*/
	
	function div_end()
	{
		$return[] = <<<EOF
	</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Мини-окно AJAX
	* 
	* @param 	string	Заголовок окна
	* @param 	string	Содержимое окна
	* @param 	array(	ajax_window		=> Дополнительные параметры для блока "ajax_window",
	* 					ajax_caption	=> Дополнительные параметры для блока "ajax_caption",
	* 					ajax_content	=> Дополнительные параметры для блока "ajax_content",
	* 
	* 					)
	*/
	
	function ajax_window( $name="", $content="", $misc=array() )
	{
		$return[] = <<<EOF
	<div id="ajax_window" class="ajax_window" {$misc['ajax_window']}>
 		<div id="ajax_caption" class="tablecaption" style="cursor:move;" title="{$this->engine->lang['ajax_window_move']}" {$misc['ajax_caption']}>
  			<div style="float:right;"><a href="javascript:ajax_window_close();" style='text-decoration:none;' title="{$this->engine->lang['ajax_window_close']}">[X]</a></div>
  			{$name}
		</div>
		<div id="ajax_content" class="ajax_content" {$misc['ajax_content']}>
{$content}
  		</div>
	</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
}

?>