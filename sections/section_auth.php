<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Страница авторизации
*/

/**
* Класс, содержащий функции для
* страницы авторизации.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class auth
{
	/**
	* HTML код для вывода на экран
	*
	* @var string
	*/

	var $html 			= "";
	
	/**
	* Информация о текущей странице
	*
	* @var array
	*/

	var $page_info		= array(	'title'	=> "",
									'desc'	=> "",
									);
	
	/**
    * Форма авторизации
    * 
    * Выводит форму авторизации.
    *
    * @return	void
    */
	
	function get_auth_form()
	{
		$this->page_info['title']	= $this->engine->lang['authorization'];
		$this->page_info['desc']	= $this->engine->lang['authorization_desc'];
		
		$this->message = array(	'text'	=> $this->engine->classes['session']->authorize_error,
								'type'	=> "red",
								);
		
		$this->html = $this->engine->classes['output']->form_start( array(	'login'		=> 1,
																			) );
		
		$this->engine->classes['output']->table_add_header( "", "30%" );
		$this->engine->classes['output']->table_add_header( "", "70%" );
		
		$this->html .= $this->engine->classes['output']->table_start();
		
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['user_name']																								, "row1" ),
								array(	$this->engine->skin['global']->form_text( "user_name", "{$this->engine->input['user_name']}", "large", "text", "tabindex='1'" )	, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['user_pass']																								, "row1" ),
								array(	$this->engine->skin['global']->form_text( "user_pass", "", "large", "password", "tabindex='2'" )								, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['use_port']																									, "row1" ),
								array(	$this->engine->skin['global']->form_text( "use_port", "{$this->engine->input['use_port']}", "small", "text", "tabindex='3'" )	, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['auth_go'] );
		
		$this->html .= $this->engine->classes['output']->table_end();
		
		$this->html .= $this->engine->classes['output']->form_end();
	}


}

?>