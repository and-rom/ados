<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Работа с сессией пользователя
*/

/**
* Класс, содержащий функции для проведения авторизации
* пользователя и создания сессий.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class session
{
	/**
	* Массив с параметрами текущей сессии
	*
	* @var array
	*/
	
	var $session			= array( 'confirmed' => FALSE );
	
	/**
	* Текст ошибки авторизации
	*
	* @var string
	*/
	
	var $authorize_error	= "";
	
	/**
	* Текущее время
	*
	* @var int
	*/

	var $time_now			= 0;
	
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Запускает функцию авторизации по cookie.
	* Всегда возвращает TRUE.
	* 
	* @return	bool			Отметка об успешном выполнении
	*/
	
	function __class_construct()
	{
		$this->authorize_cookie();
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Авторизация
	/*-------------------------------------------------------------------------*/
	
	/**
	* Авторизация по cookie
	* 
	* Пытается выполнить авторизацию пользователя по cookie. Отметку об
	* успешной (неуспешной) авторизации записывает в переменную
	* $this->session['confirmed'].
	* 
	* @return	void
	*/
	
	function authorize_cookie()
	{
		//-----------------------------------------------
		// Проверяем наличие ID пользователя
		//-----------------------------------------------
		
		$cookie['user_id'] = $this->engine->my_getcookie( 'user_id' );
		
		if( !$cookie['user_id'] )
		{
			$this->session['confirmed'] = FALSE;
			return;
		}
		
		//-----------------------------------------------
		// Проверяем наличие хэша пароля
		//-----------------------------------------------
		
		$cookie['pass_hash'] = $this->engine->my_getcookie( 'pass_hash' );
		
		//-----------------------------------------------
		// Загружаем информацию о пользователе
		//-----------------------------------------------
		
		if( $this->load_member( $cookie ) === TRUE )
		{
			$this->session['confirmed'] = TRUE;
		}
		else
		{
			$this->session['confirmed'] = FALSE;
		}
	}
	
	/**
	* Авторизация по данным из формы
	* 
	* Пытается выполнить авторизацию пользователя по данным, полученным
	* из формы на странице авторизации.
	* 
	* @return	void
	*/
	
	function authorize_manual()
	{
		$form['user_name'] = $this->engine->input['user_name'];
		
		if( !$form['user_name'] )
		{
			$this->session['confirmed'] = FALSE;
			$this->authorize_error = $this->engine->lang['err_auth_no_username'];
		}
		
		$form['pass_hash'] = $this->engine->input['user_pass'];
		$form['pass_hash'] = $form['pass_hash'] ? md5( sha1( $form['pass_hash'] ) ) : "";
		
		if( $this->load_member( $form ) === TRUE )
		{
			$this->engine->my_setcookie( "user_id" 	 , $this->engine->member['user_id']		, -1  );
			$this->engine->my_setcookie( "pass_hash" , $this->engine->member['user_pass']	, -1  );
			
			$this->session['confirmed'] = TRUE;
		}
		else
		{
			$this->session['confirmed'] = FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Пользователь
	/*-------------------------------------------------------------------------*/
	
	/**
	* Загрузка информации о пользователе
	* 
	* Загружает информацию об указанном пользователе и проверяет
	* указанный пароль.
	* В случае, если пользователь существует, а указанный пароль
	* верен, помещает в переменную $this->session['confirmed']
	* значение TRUE.
	* 
	* @param	array	[or]	'user_id'	=> ID пользователя,
	* 					[or]	'user_name'	=> Имя пользователя
	* 							'pass_hash'	=> Хэш пароля
	* 
	* @return	bool	Результат выполнения операции
	*/
	
	function load_member( $info )
	{
		$info['user_id'] = intval( $info['user_id'] );
		
		if( !$info['user_id'] and !$info['user_name'] )
		{
			$this->authorize_error = &$this->engine->lang['err_auth_no_uid_nor_username'];
			
			return FALSE;
		}
		
		$where = $info['user_id'] ? "user_id='{$info['user_id']}'" : "user_name='{$info['user_name']}'";
		
		//-----------------------------------------------
		// Загружаем информацию о пользователе
		//-----------------------------------------------
		
		$member = $this->engine->DB->simple_exec_query( array(	'select'	=> '*',
													 			'from'		=> 'users_list',
													 			'where'		=> $where
													 			)	);

		//-----------------------------------------------
		// Проверка на наличие ID
		//-----------------------------------------------
		
		if( !$member['user_id'] )
		{
			$this->unload_member();
			$this->authorize_error = &$this->engine->lang['err_auth_no_such_uid'];
			
			return FALSE;
		}
		
		//-----------------------------------------------
		// Проверяем пароль
		//-----------------------------------------------
		
		if( $member['user_pass'] != $info['pass_hash'] )
		{
			$this->authorize_error = &$this->engine->lang['err_auth_wrong_password'];
			
			return FALSE;
		}
		
		//-----------------------------------------------
		// Все в порядке, записываем данные о пользователе
		//-----------------------------------------------
		
		$this->engine->member = &$member;
		
		return TRUE;
	}
	
	/**
	* Удаление информации о пользователе
	* 
	* Удаляет текущую информацию, записанную в cookie пользователя,
	* т.к. она оказалась неверной.
	* 
	* @return	void
	*/
	
	function unload_member()
	{
		$this->engine->my_setcookie( "user_id" 	, "0", -1  );
		$this->engine->my_setcookie( "pass_hash", "0", -1  );
	}
	
}

?>
