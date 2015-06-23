/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			AJAX - Основные функции
*/

/**
* Проверка нажатия клавиши Ctrl
* 
* @var	bool
*/

document.onkeydown	= ajax_enable_ctrl;
document.onkeyup	= ajax_disable_ctrl;

/**
* Состояние клавиши Ctrl
* 
* @var	bool
*/

var ctrl_enabled	= false;

/**
* Состояние соединения
* 
* @var	bool
*/

var http_request	= false;

/**
* Состояние инициализации AJAX окна
* 
* @var	bool
*/

var ajax_window_loaded = false;

/**
* Обозреватель пользователя
* 
* @var	string
*/

var uagent		= navigator.userAgent.toLowerCase();
var is_safari	= ( ( uagent.indexOf( 'safari' ) != -1 ) || ( navigator.vendor == "Apple Computer, Inc." ) );
var is_ie_7		= ( ( uagent.indexOf( 'msie 7' ) != -1 ) && ( !is_opera ) && ( !is_safari ) );
var is_ie		= ( ( uagent.indexOf( 'msie' ) != -1 ) && ( !is_opera ) && ( !is_safari ) && ( !is_ie_7 ) );
var is_moz		= ( navigator.product == 'Gecko' );
var is_opera	= ( uagent.indexOf( 'opera' ) != -1 );

/**
* Операционная система пользователя
* 
* @var	string
*/

var is_win		= ( ( uagent.indexOf( "win" ) != -1 ) || ( uagent.indexOf( "16bit" ) !=- 1 ) );
var is_mac		= ( ( uagent.indexOf( "mac" ) != -1 ) || ( navigator.vendor == "Apple Computer, Inc." ) );

/**
* Текущее состояние меню
* 
* @var	bool
*/

var menu_active = false;

/**
* Текст ошибки
* 
* @var	string
*/

var error		= "";

/**
* Перемещение AJAX окна
* 
* @var	object
*
* @copyright	09.25.2001
*				www.youngpup.net
*/

var Drag = {

	obj		    : null,
	fx		    : null,
	fy		    : null,
	keeponscreen: true,
	
	init : function( o, oRoot, minX, maxX, minY, maxY, bSwapHorzRef, bSwapVertRef, fXMapper, fYMapper)
	{
		o.onmousedown	= Drag.start;
		o.onmouseover   = Drag.cursorchange;
		
		o.hmode			= bSwapHorzRef ? false : true ;
		o.vmode			= bSwapVertRef ? false : true ;

		o.root = oRoot && oRoot != null ? oRoot : o ;

		if (o.hmode  && isNaN(parseInt(o.root.style.left  ))) o.root.style.left   = "0px";
		if (o.vmode  && isNaN(parseInt(o.root.style.top   ))) o.root.style.top    = "0px";
		if (!o.hmode && isNaN(parseInt(o.root.style.right ))) o.root.style.right  = "0px";
		if (!o.vmode && isNaN(parseInt(o.root.style.bottom))) o.root.style.bottom = "0px";

		o.minX	= typeof minX != 'undefined' ? minX : null;
		o.minY	= typeof minY != 'undefined' ? minY : null;
		o.maxX	= typeof maxX != 'undefined' ? maxX : null;
		o.maxY	= typeof maxY != 'undefined' ? maxY : null;

		o.xMapper = fXMapper ? fXMapper : null;
		o.yMapper = fYMapper ? fYMapper : null;
		
		//--------------------------------------------
		// Figure width and height
		//--------------------------------------------
		
		if ( Drag.keeponscreen )
		{
			Drag.my_width  = 0;
			Drag.my_height = 0;
			
			if ( typeof( window.innerWidth ) == 'number' )
			{
				//--------------------------------------------
				// Non IE
				//--------------------------------------------
			  
				Drag.my_width  = window.innerWidth;
				Drag.my_height = window.innerHeight;
			}
			else if ( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) )
			{
				//--------------------------------------------
				// IE 6+
				//--------------------------------------------
				
				Drag.my_width  = document.documentElement.clientWidth;
				Drag.my_height = document.documentElement.clientHeight;
			}
			else if ( document.body && ( document.body.clientWidth || document.body.clientHeight ) )
			{
				//--------------------------------------------
				// Old IE
				//--------------------------------------------
				
				Drag.my_width  = document.body.clientWidth;
				Drag.my_height = document.body.clientHeight;
			}
		}
		
		o.root.onDragStart	= new Function();
		o.root.onDragEnd	= new Function();
		o.root.onDrag		= new Function();
	},
	
	cursorchange : function(e)
	{
		var o = Drag.obj = this;
		o.style.cursor = 'move';
	},

	start : function(e)
	{
		var o = Drag.obj = this;
		e = Drag.fixE(e);
		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		o.root.onDragStart(x, y);

		o.lastMouseX	= e.clientX;
		o.lastMouseY	= e.clientY;

		if (o.hmode) {
			if (o.minX != null)	o.minMouseX	= e.clientX - x + o.minX;
			if (o.maxX != null)	o.maxMouseX	= o.minMouseX + o.maxX - o.minX;
		} else {
			if (o.minX != null) o.maxMouseX = -o.minX + e.clientX + x;
			if (o.maxX != null) o.minMouseX = -o.maxX + e.clientX + x;
		}

		if (o.vmode) {
			if (o.minY != null)	o.minMouseY	= e.clientY - y + o.minY;
			if (o.maxY != null)	o.maxMouseY	= o.minMouseY + o.maxY - o.minY;
		} else {
			if (o.minY != null) o.maxMouseY = -o.minY + e.clientY + y;
			if (o.maxY != null) o.minMouseY = -o.maxY + e.clientY + y;
		}

		document.onmousemove	= Drag.drag;
		document.onmouseup		= Drag.end;

		return false;
	},

	drag : function(e)
	{
		e = Drag.fixE(e);
		var o = Drag.obj;

		var ey	= e.clientY;
		var ex	= e.clientX;
		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		var nx, ny;

		if (o.minX != null) ex = o.hmode ? Math.max(ex, o.minMouseX) : Math.min(ex, o.maxMouseX);
		if (o.maxX != null) ex = o.hmode ? Math.min(ex, o.maxMouseX) : Math.max(ex, o.minMouseX);
		if (o.minY != null) ey = o.vmode ? Math.max(ey, o.minMouseY) : Math.min(ey, o.maxMouseY);
		if (o.maxY != null) ey = o.vmode ? Math.min(ey, o.maxMouseY) : Math.max(ey, o.minMouseY);

		nx = x + ((ex - o.lastMouseX) * (o.hmode ? 1 : -1));
		ny = y + ((ey - o.lastMouseY) * (o.vmode ? 1 : -1));

		if (o.xMapper)		nx = o.xMapper(y)
		else if (o.yMapper)	ny = o.yMapper(x)
		
		//--------------------------------------------
		// Keep on screen?
		//--------------------------------------------
		
		if ( Drag.keeponscreen )
		{
			ny = ny < 0 ? 0 : ny;
			nx = nx < 0 ? 0 : nx;
			
			if ( Drag.my_width )
			{
				nx = nx > ( Drag.my_width - parseInt(o.root.style.width) - 19 ) ? Drag.my_width - parseInt(o.root.style.width) - 19 : nx;
			}
			
			if ( Drag.my_height )
			{
				ny = ny > ( Drag.my_height - parseInt(o.root.style.height) - 19 ) ? Drag.my_height - parseInt(o.root.style.height) - 19 : ny;
			}
		}
		
		Drag.obj.root.style[o.hmode ? "left" : "right"] = nx + "px";
		Drag.obj.root.style[o.vmode ? "top" : "bottom"] = ny + "px";
		Drag.obj.lastMouseX	= ex;
		Drag.obj.lastMouseY	= ey;

		Drag.obj.root.onDrag(nx, ny);
		return false;
	},

	end : function()
	{
		document.onmousemove = null;
		document.onmouseup   = null;
		Drag.obj.root.onDragEnd(	parseInt(Drag.obj.root.style[Drag.obj.hmode ? "left" : "right"]), 
									parseInt(Drag.obj.root.style[Drag.obj.vmode ? "top" : "bottom"]));
		
		var o = Drag.obj;
		
		fy = parseInt( o.root.style.top );
		fx = parseInt( o.root.style.left );
		
		Drag.obj = null;
		
	},

	fixE : function(e)
	{
		if (typeof e == 'undefined') e = window.event;
		if (typeof e.layerX == 'undefined') e.layerX = e.offsetX;
		if (typeof e.layerY == 'undefined') e.layerY = e.offsetY;
		return e;
	}
};

/**
* Загрузка файла
*
* Выполняет загрузку файла через скрытый iframe.
* Считывает ответ сервера и возвращает его в
* качестве параметра указанной функции, если
* необходимо.
* 
* @var	object
*
* @copyright	http://www.webtoolkit.info/
*
*/

Upload = {

	frame : function(c)
	{
		var n = 'f' + Math.floor(Math.random() * 99999);
		var d = document.createElement( 'DIV' );
		
		d.innerHTML = '<iframe style="display:none" src="about:blank" id="'+n+'" name="'+n+'" onload="Upload.loaded(\''+n+'\')"></iframe>';
		
		document.body.appendChild(d);

		var i = document.getElementById(n);
		
		if( c && typeof( c.onComplete ) == 'function' )
		{
			i.onComplete = c.onComplete;
		}

		return n;
	},

	form : function(f, name)
	{
		f.setAttribute( 'target', name );
	},

	submit : function(f, c)
	{
		if( c && typeof( c.onLoad ) == 'function' )
		{
			if( c.onLoad() == false ) return false;
		}
		
		Upload.form( f, Upload.frame(c) );
		
		if( c && typeof( c.onStart ) == 'function' )
		{
			return c.onStart();
		}
		else
		{
			return true;
		}
	},

	loaded : function(id)
	{
		var i = document.getElementById(id);
		
		if( i.contentDocument )
		{
			var d = i.contentDocument;
		}
		else if( i.contentWindow )
		{
			var d = i.contentWindow.document;
		}
		else
		{
			var d = window.frames[id].document;
		}
		
		if( d.location.href == "about:blank" )
		{
			return;
		}

		if( typeof( i.onComplete ) == 'function' )
		{
			i.onComplete( d );
		}
	}
}

/**
* Работа с Base64
*
* Выполняет преобразование текста в Base64 и обратно,
* предварительно конвертируя текст в Unicode.
* 
* @var	object
*
* @copyright	http://www.webtoolkit.info/
*
*/

Base64 = {

	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

	// public method for encoding
	encode : function (input)
	{
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;

		input = Base64._utf8_encode(input);

		while (i < input.length)
		{
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);

			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;

			if (isNaN(chr2))
			{
				enc3 = enc4 = 64;
			}
			else if (isNaN(chr3))
			{
				enc4 = 64;
			}

			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
		}

		return output;
	},

	// public method for decoding
	decode : function (input)
	{
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;

		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

		while (i < input.length)
		{
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));

			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;

			output = output + String.fromCharCode(chr1);

			if (enc3 != 64)
			{
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64)
			{
				output = output + String.fromCharCode(chr3);
			}

		}

		output = Base64._utf8_decode(output);

		return output;

	},

	// private method for UTF-8 encoding
	_utf8_encode : function (string)
	{
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";

		for (var n = 0; n < string.length; n++)
		{
			var c = string.charCodeAt(n);

			if (c < 128)
			{
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048))
			{
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else
			{
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}

		}

		return utftext;
	},

	// private method for UTF-8 decoding
	_utf8_decode : function (utftext)
	{
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;

		while ( i < utftext.length )
		{
			c = utftext.charCodeAt(i);

			if (c < 128)
			{
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224))
			{
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else
			{
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}

		}

		return string;
	}

}

/**
* Изменение прозрачности слоя
*
* .
* 
* @var	object
*
* @copyright	http://www.webtoolkit.info/
*
*/

/**
* Получение cookie
*
* Пытается прочитать cookie с указанным
* именем.
* 
* @param		string	Имя cookie
*
* @return		null
*/

function my_getcookie( name )
{
	name   = name + '=';
	loaded = document.cookie.indexOf( name );

	if( loaded == -1 ) return null;
	
	start = loaded + name.length;
	end   = document.cookie.indexOf( ";", start );

	if( end == -1 ) end = document.cookie.length;

	value = unescape( document.cookie.substring( start, end ) );
		
	value = value.replace( /&#032;/g	 , " "			 );
	value = value.replace( /&/g          , "&amp;"        );
	value = value.replace( /<!--/g       , "&#60;&#33;--" );
	value = value.replace( /-->/g        , "--&#62;"      );
	value = value.replace( /<script/gi   , "&#60;script"  );
	value = value.replace( />/g          , "&gt;"         );
	value = value.replace( /</g          , "&lt;"         );
	value = value.replace( /\"/g         , "&quot;"       );
	value = value.replace( /\\\$/g       , "&#036;"       );
	value = value.replace( /\r/g         , ""             );
	value = value.replace( /!/g          , "&#33;"        );
	value = value.replace( /'/g          , "&#39;"        );

	value = value.replace( /&amp;#([0-9]+);/g, "&#\\1;"   );
	value = value.replace( /\\(?!&amp;#|\?#)/g, "&#092;"  );

	return value;
}

/**
* Запись cookie
*
* Пытается выполнить запись cookie с
* указанным именем.
* 
* @param		string	Имя cookie
* @param		string	Значение cookie
* @param		bool	Время действия неограничено
*
* @return		null
*/

function my_setcookie( name, value, unlimited )
{
	expire = "";
	domain = "";

	if( unlimited )
	{
		expire = "; expires=Thu, 31 Dec 2099 23:59:59 GMT";
	}
	
	domain = cookie_domain ? "; domain=" + cookie_domain : domain;
	path = cookie_path ? "; path=" + cookie_path : "; path=/";
	
	document.cookie = name + "=" + value + path + expire + domain + ';';
}

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
* Включение отображения элемента
* 
* @param		object	Элемент
*
* @return		void
*/

function my_hide_div( obj )
{
	if( !obj ) return;

	obj.style.display = "none";
}

/**
* Отключение отображения элемента
* 
* @param		object	Элемент
*
* @return		void
*/

function my_show_div( obj )
{
	if ( !obj ) return;

	obj.style.display = "";
}

/**
* Обработка значений полей формы
* 
* @param		string	Идентификатор формы
* @param		bool	Возвращать значения в виде массива
*
* @return		array или string Значения полей формы
*/

function my_parse_form( fid, arr )
{
	var vars = new Array();
	
	if( !( form = my_getbyid( fid ) ) || !( num = form.elements.length ) ) return vars;
	
	for( i = 0; i < num; i++ )
	{
		//--------------------------------------------
		// Кнопка отправки запроса
		//--------------------------------------------
		
		if( form.elements[ i ].type == 'submit' );
		
		//--------------------------------------------
		// Переключатель или флажок
		//--------------------------------------------
		
		else if( ( form.elements[ i ].type == 'radio' || form.elements[ i ].type == 'checkbox' ) && form.elements[ i ].checked == false );
		
		//--------------------------------------------
		// Ниспадающее меню или поле мультивыбора
		//--------------------------------------------
		
		else if( form.elements[ i ].type == 'select' || form.elements[ i ].type == 'select-multiple' )
		{
			len = form.elements[ i ].options.length;
			
			for( j = 0; j < len; j++ )
			{
				if( form.elements[ i ].options[ j ].selected == true ) vars[ vars.length ] = form.elements[ i ].name + "=" + encodeURI( escape( form.elements[ i ].options[ j ].value ) );
			}
		}
							   
		//--------------------------------------------
		// Текстовое или скрытое поле
		//--------------------------------------------
		
		else vars[ vars.length ] = form.elements[ i ].name + "=" + encodeURI( escape( form.elements[ i ].value ) );
	}
	
	return arr ? vars : "&" + vars.join( "&" ).replace( /\+/g, "%2B" );
}

/**
* Инициализация соединения с сервером
* 
* @return	void
*/

function ajax_init_request()
{
	if( window.XMLHttpRequest )
	{
		http_request = new XMLHttpRequest();
	}
	else if( window.ActiveXObject )
	{
		try
		{
			http_request = new ActiveXObject( "Msxml2.XMLHTTP" );
		}
		catch (e)
		{
			try
			{
				http_request = new ActiveXObject( "Microsoft.XMLHTTP" );
			}
			catch (e) {}
		}
	}

	if( !http_request )
	{
		error = lang_error_init_request;

		return false;
	}
	else if( http_request.overrideMimeType )
	{
		http_request.overrideMimeType( 'text/xml' );
	}
}

/**
* Вывод AJX окна
*
* Делает запрос с указанными параметрами и
* помещает полученный результат в AJAX окно.
* 
* @param	string	Ключ секции
* @param	string	Значение запроса
* @param	int		Идентификатор
*
*/

function ajax_window( section, query, id )
{	
	//--------------------------------------------
	// Определяем контейнер
	//--------------------------------------------
	
	var ajax_container = my_getbyid( "ajax_container" );
	
	//--------------------------------------------
	// Выводим заглушку
	//--------------------------------------------
		
	content_height = my_getbyid( "main" ).offsetHeight;
			
	if ( typeof( window.innerWidth ) == 'number' )
	{
		my_width  = window.innerWidth;
		my_height = window.innerHeight;
	}
	else if ( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) )
	{
		my_width  = document.documentElement.clientWidth;
		my_height = document.documentElement.clientHeight;
	}
	else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) )
	{
		my_width  = document.body.clientWidth;
		my_height = document.body.clientHeight;
	}
			
	content_height = ( content_height > my_height ) ? content_height : my_height;
		
	my_getbyid( "please_wait" ).innerHTML = "<img src='images/none.gif' width='"+( my_width - 20 )+"' height='"+content_height+"' alt='' />";
	
	//--------------------------------------------
		
	var wait_bar = my_getbyid( "wait_bar" );
	
	wait_bar.style.top = parseInt( my_height / 2 - 10 ) + "px";
	wait_bar.style.left = parseInt( my_width / 2 - 37 ) + "px";
		
	my_show_div( wait_bar );
	
	//--------------------------------------------
	// Инициализируем и обрабатываем запрос
	//--------------------------------------------
	
	ajax_init_request();
	
	//--------------------------------------------
	// Обрабатываем ответ сервера
	//--------------------------------------------
	
	http_request.onreadystatechange = function()
	{	
		var xmldoc = http_request.responseXML;

		if( http_request.readyState == 4 && http_request.status == 200 )
		{
			my_getbyid( "please_wait" ).innerHTML = "";
			my_hide_div( my_getbyid( "wait_bar" ) );
			
			content = xmldoc.getElementsByTagName( 'HTML' )[0];
			itm_lst	= xmldoc.getElementsByTagName( 'List' )[0];
			message = xmldoc.getElementsByTagName( 'Message' )[0];
			close_w = xmldoc.getElementsByTagName( 'CloseWindow' )[0];
			cookies = xmldoc.getElementsByTagName( 'SetCookie' )[0];
			
			update = new Array();
			
			for( i = 0; i <= 19; i++ ) update[i] = xmldoc.getElementsByTagName( 'Update_'+i )[0];
			
			func = new Array();
			
			for( i = 0; i <= 9; i++ ) func[i] = xmldoc.getElementsByTagName( 'Function_'+i )[0];
			
			//--------------------------------------------
			// Обновляем сookie
			//--------------------------------------------
			
			i = 0;
			
			if( cookies ) while( cookie = xmldoc.getElementsByTagName( 'SetCookie' )[i] )
			{
				my_setcookie( cookie.attributes.getNamedItem("name").value, cookie.firstChild.data, cookie.attributes.getNamedItem("expire").value );
				
				i++;
			}
			
			//--------------------------------------------
			// Сообщение
			//--------------------------------------------
			
			if( message && !content && !itm_lst && !update.length )
			{
				alert( message.firstChild.data );
				return;
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
			// Закрываем окно
			//--------------------------------------------
			
			else if( close_w )
			{
				my_getbyid( "ajax_window" ).style.display = "none";
				ajax_window_loaded = null;
			}
			
			//--------------------------------------------
			// Обновляем список
			//--------------------------------------------
			
			if( itm_lst )
			{
				my_getbyid( "list" ).innerHTML = itm_lst.firstChild.data;
			}
			
			//--------------------------------------------
			// Обновляем элементы страницы
			//--------------------------------------------
			
			if( update.length ) for( i = 0; i <= 19; i++ )
			{
				if( update[i] ) my_getbyid( "update_container_"+i ).innerHTML = update[i].firstChild.data;
			}
			
			//--------------------------------------------
			// Запускаем на выполнение функции
			//--------------------------------------------
			
			if( func.length ) for( i = 0; i <= 9; i++ )
			{
				if( func[i] ) eval( func[i].firstChild.data );
			}
			
			//--------------------------------------------
			// Сообщение
			//--------------------------------------------
			
			if( message )
			{
				alert( message.firstChild.data );
			}
		}
		else if( http_request.readyState == 4 && http_request.status != 200 )
		{
			my_getbyid( "please_wait" ).innerHTML = "";
			my_hide_div( my_getbyid( "wait_bar" ) );
			
			alert( lang_error_xml_response );
		}
	};
	
	//--------------------------------------------
	// Открываем соединение и отправляем запрос
	//--------------------------------------------
	
	var st = ( section == 'download' || section == 'log' ||
			   section == 'categories' || section == 'schedule' ) ? "&st="+pages_st : "";
	
	http_request.open( "POST", base_url, true );
	http_request.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
	http_request.send( "tab="+section+"&ajax=yes"+st+"&id="+id+"&type="+query );
}

/**
* Инициализация AJAX окна
* 
* @return		void
*/

function ajax_window_initiate()
{
	var ajax_window	 = document.getElementById( 'ajax_window' );
	var ajax_caption = document.getElementById( 'ajax_caption' );
	var ajax_content = document.getElementById( 'ajax_content' );
	
	ajax_window.style.position = ( is_ie && !is_ie_7 ) ? 'absolute' : 'fixed';
	ajax_window.style.zIndex   = 97;
	
	//--------------------------------------------
	// Figure width and height
	//--------------------------------------------
		
	var my_width  = 0;
	var my_height = 0;
		
	if ( typeof( window.innerWidth ) == 'number' )
	{
		//--------------------------------------------
		// Non IE
		//--------------------------------------------
		  
		my_width  = window.innerWidth;
		my_height = window.innerHeight;
	}
	else if ( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) )
	{
		//--------------------------------------------
		// IE 6+
		//--------------------------------------------
			
		my_width  = document.documentElement.clientWidth;
		my_height = document.documentElement.clientHeight;
	}
	else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) )
	{
		//--------------------------------------------
		// Old IE
		//--------------------------------------------
			
		my_width  = document.body.clientWidth;
		my_height = document.body.clientHeight;
	}
	
	//--------------------------------------------
	// Got it stored in a cookie?
	//--------------------------------------------
		
	var co_ords;
	var indent;
	
	co_ords = new Array();
		
	//--------------------------------------------
	// Get div height and width
	//--------------------------------------------
	
	var can_overflow = false;
	var difference = 0;
	
	if( can_overflow = my_getbyid( 'can_overflow' ) )
	{
		ajax_content.style.overflow = 'visible';
		
		difference = ajax_content.offsetHeight - can_overflow.offsetHeight;
	}
	else
	{
		can_overflow = false;
	}
	
	var main_height = my_getbyid( 'main' ).offsetHeight;
	
	if( main_height > my_height ) main_height = my_height;
	
	var max_height = main_height - 20;
	var max_width  = my_width  - 20;
	
	// Constant dimensions
	
	if( ajax_window.currentStyle )
	{
		var divheight = parseInt( ajax_window.currentStyle['height'] );
		var divwidth  = parseInt( ajax_window.currentStyle['width']  );
	}
	else
	{
		var divheight = parseInt( document.defaultView.getComputedStyle( ajax_window, null ).getPropertyValue( 'height' ) );
		var divwidth  = parseInt( document.defaultView.getComputedStyle( ajax_window, null ).getPropertyValue( 'width' )  );
	}
	
	// Dynamic dimensions
	
	divheight = ajax_caption.offsetHeight + ajax_content.offsetHeight;
	divwidth += divheight > max_height ? 17 : 0;
	
	if( ajax_window.currentStyle )
	{
		ajax_window.style['height']  = divheight > max_height ? max_height+'px' : divheight+'px';
		ajax_content.style['height'] = divheight > max_height ? ( max_height - ajax_caption.offsetHeight ) + 'px' : ajax_content.style['height'];
		
		if( typeof( can_overflow ) == "object" ) can_overflow.style['height'] = ( ajax_content.offsetHeight - difference ) + 'px';
		
		ajax_window.style['width']  = divwidth > max_width ? max_width+'px' : divwidth+'px';
		ajax_content.style['width'] = divwidth > max_width ? ( max_width - ajax_caption.offsetWidth ) + 'px' : ajax_content.style['width'];
		
		//--------------------------------------------
		// IE scrollbar position fix for
		// inner table with 100% width
		//--------------------------------------------
		
		var ajax_table = my_getbyid( "ajax_table" );
		
		if( ajax_table && divheight > max_height )
		{
			ajax_table.style['width'] = ajax_content.offsetWidth - 17 + 'px';
		}
	}
	else
	{
		ajax_window.style.setProperty( 'height', divheight > max_height ? max_height+'px' : divheight+'px', null );
		if( divheight > max_height ) ajax_content.style.setProperty( 'height', ( max_height - ajax_caption.offsetHeight ) + 'px', null );
		
		if( typeof( can_overflow ) == "object" ) can_overflow.style.setProperty( 'height', ( ajax_content.offsetHeight - difference ) + 'px', null );
		
		ajax_window.style.setProperty( 'width', divwidth > max_width ? max_width+'px' : divwidth+'px', null );
		if( divwidth > max_width ) ajax_content.style.setProperty( 'width', ( max_width - ajax_caption.offsetWidth ) + 'px', null );
	}
	
	divheight = divheight ? divheight : max_height;
	divwidth  = divwidth  ? divwidth  : max_width;
	
	if( divheight > max_height ) divheight = max_height;
	
	//--------------------------------------------
	// Reposition DIV roughly centered
	//--------------------------------------------
			
	ajax_window.style.left = my_width  / 2  - (divwidth / 2)  + 'px';
	ajax_window.style.top  = my_height / 2 - (divheight / 2 ) + 'px';
		
	Drag.keeponscreen = true;
	Drag.init( ajax_caption, ajax_window );
	
	ajax_window.style.visibility = "visible";
		
	ajax_window_loaded = true;
}

/**
* Закрытие AJAX окна
*
* Скрывает окно и сбрасывает флаг с результатом
* загрузки окна.
*
* @return	void
*/

function ajax_window_close()
{
	my_getbyid( "ajax_window" ).style.display = "none";
	ajax_window_loaded = null;
}

/**
* Добавление пользователя в список скрытых
*
* Добавляет или убирает идентификатор пользователя
* из списка скрытых пользователей указанной категории.
* 
* @param	int		Идентификатор пользователя
* @param	string	Идентификатор категории
* @param	bool	Добавить в список
*
* @return	void
*/

function ajax_hide_user( uid, tab, add )
{
	cookie = my_getcookie( "list_hidden" );
	
	list_hidden = cookie ? cookie.split( ":" ) : new Array();
	list_tabs = new Array();
	list_new = new Array();
	
	var got_it = false;
	
	//--------------------------------------------
	// Получаем список скрытых пользователей
	// данной категории
	//--------------------------------------------
	
	for( i = 0; i < list_hidden.length; i++ )
	{
		if( items = list_hidden[i].match( /(download|cat|log|schedule)=((\d+,?)*)/ ) )
		{
			if( items[1] == tab ) 
			{
				list_items = items[2] ? items[2].split( "," ) : new Array();
				
				got_it = true;
			}
			else
			{
				list_tabs[ list_tabs.length ] = items[1] + "=" + items[2];
			}
		}
	}
	
	if( !list_hidden.length || !got_it ) list_items = new Array();
	
	//--------------------------------------------
	// Удаляем пользователя из списка
	//--------------------------------------------
	
	for( i = 0; i < list_items.length; i++ )
	{
		if( list_items[i] != uid ) list_new[ list_new.length ] = list_items[i];
	}
	
	//--------------------------------------------
	// Добавляем пользователя в список
	//--------------------------------------------
	
	if( add ) list_new[ list_new.length ] = uid;
	
	//--------------------------------------------
	// Формируем окончательный текст cookie
	//--------------------------------------------
	
	list_tabs[ list_tabs.length ] = list_new.length ? tab + "=" + list_new.join( "," ) : tab + "=";
	
	my_setcookie( "list_hidden", list_tabs.join( ":" ), 1 );
}

/**
* Изменение типа поля
*
* Изменяет тип поля для ввода нового пароля.
* 
* @param	obj		Поле ввода пароля
* @param	string	Новый тип поля
* @param	string	Новое значение поля
* @param	bool	Передать фокус полю
*
* @return	void
*/

function ajax_change_input_type( oldElm, type, value, dofocus )
{
	/*	
	Думаете, можно было все сделать проще: oldElm.type = "password"; ?
	По стандарту можно. Но в IE нельзя. Потому что IE - это говно редкосное.
	*/
	
	if( !oldElm || !oldElm.parentNode || !document.createElement ) return;

	//--------------------------------------------
	// Создаем новое поле и передаем ему параметры
	// старого
	//--------------------------------------------
	
	var newElm = document.createElement('input');
	
	newElm.type = type;
	newElm.name = oldElm.name;
	newElm.className = oldElm.className;
	
	newElm.value = value;
	
	if( oldElm.currentStyle ) newElm.style['width'] = oldElm.currentStyle['width'];
	else newElm.style.setProperty( 'width', document.defaultView.getComputedStyle( oldElm, null ).getPropertyValue( 'width' ), null );
	
	//--------------------------------------------
	// Назначаем функции для onfocus и onblur
	//--------------------------------------------

	newElm.onfocus = function(){ return function(){
		if( this.hasFocus ) return;
		ajax_change_type( this, true );
	}}();
		
	newElm.onblur  = function(){return function(){
		if( this.hasFocus ) ajax_change_type( this, false );
	}}();
	
	//--------------------------------------------
	// Меняем поля
	//--------------------------------------------

	oldElm.parentNode.replaceChild( newElm,oldElm );
	
	//--------------------------------------------
	// Передаем фокус, если требуется
	//--------------------------------------------
	
	/*	
	Думаете, можно было все сделать проще: newElm.focus(); ?
	По стандарту можно. Но в IE нельзя. Потому что IE - это говно редкосное.
	*/
	
	if( dofocus && typeof( dofocus ) != 'undefined' )
	{
		window.tempElm = newElm;
    	setTimeout( "tempElm.hasFocus=true;tempElm.focus();", 1 );
	}
	
	return true;
}

/**
* Включение флажка о нажатой клавише Ctrl
*
* Проверяет код нажатой клавиши, и если клавиша - это
* Ctrl, то меняет состояние соответствующего флажка.
*
* @return	void
*/

function ajax_enable_ctrl( event )
{
	e = window.event ? window.event : event;
	
	switch( e.keyCode ? e.keyCode : e.which ? e.which : null )
	{
		case 17:
			ctrl_enabled = true;
			break;
	}
}

/**
* Выключение флажка о нажатой клавише Ctrl
*
* Проверяет код нажатой клавиши, и если клавиша - это
* Ctrl, то меняет состояние соответствующего флажка.
*
* @return	void
*/

function ajax_disable_ctrl( event )
{
	e = window.event ? window.event : event;
	
	switch( e.keyCode ? e.keyCode : e.which ? e.which : null )
	{
		case 17:
			ctrl_enabled = false;
			break;
	}
}

/**
* Проверка позиционирования окна
*
* Прикрепляет меню к верхней границе окна,
* если меню пропадает из видимой области.
*
* @return	void
*/

function ajax_check_position()
{
	var height = 153, scrollY = 0;
	
	if( typeof( window.pageYOffset ) == 'number' ) scrollY = window.pageYOffset;
	else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) scrollY = document.body.scrollTop;
	else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) scrollY = document.documentElement.scrollTop;	
	
	var warn_install   =  my_getbyid( "warn_install"   );
	var warn_update    =  my_getbyid( "warn_update"    );
	var warn_task_lock =  my_getbyid( "warn_task_lock" );
	var warn_cron_lock =  my_getbyid( "warn_cron_lock" );
	var info_update    =  my_getbyid( "info_update"    );
	
	if( warn_install   && warn_install.style.display   != "none" ) height += 38;
	if( warn_update    && warn_update.style.display    != "none" ) height += 38;
	if( warn_task_lock && warn_task_lock.style.display != "none" ) height += 70;
	if( warn_cron_lock && warn_cron_lock.style.display != "none" ) height += 70;
	if( info_update    && info_update.style.display    != "none" ) height += 38;
	
	if( scrollY < height )
	{
		if( menu_active == false || is_ie ) return;
		
		my_getbyid( "menu_left" ).style.position = "static";
		my_getbyid( "menu_right" ).style.position = "static";
		
		my_hide_div( my_getbyid( "dummy_left" ) );
		my_hide_div( my_getbyid( "dummy_right" ) );
		
		menu_active = false;
	}
	else
	{
		if( menu_active == true || is_ie ) return;
		
		var menu_left = my_getbyid( "menu_left" );
		
		menu_left.style.position = "fixed";
		menu_left.style.top = 0;
		menu_left.style.width = "235px";
		
		var menu_right = my_getbyid( "menu_right" );
		
		menu_right.style.position = "fixed";
		menu_right.style.top = 0;
		menu_right.style.width = "742px";
		
		my_show_div( my_getbyid( "dummy_left" ) );
		my_show_div( my_getbyid( "dummy_right" ) );
		
		menu_active = true;
	}
}

/**
* Переход по страницам
*
* Выполняет запрос на сервер, отсылая в качестве
* переменной номер необходимой для показа страницы.
*
* @param	string	Идентификатор требуемой страницы
* @param	string	Номер текущей страницы
* @param	string	Идентификатор текущей секции
* @param	int		Идентификатор активного элемента
* @param	string	Идентификатор активного подэлемента
*
* @return	void
*/

function ajax_set_page( to, current, section, id, sid )
{
	switch( to )
	{
		case 'first':
			if( current <= 1 ) return;
			pagenum = 1;
			break;
			
		case 'prev':
			if( current <= 1 ) return;
			pagenum = current - 1;
			break;
			
		case 'next':
			if( current >= pages_total ) return;
			pagenum = current + 1;
			break;
			
		case 'last':
			if( current >= pages_total ) return;
			pagenum = pages_total;
			break;
			
		default:
			if( to <= 1 || to >= pages_total || to == current ) return;
			pagenum = to;
			break;
	}
	
	pagenum = ( pagenum - 1 ) * 100;
	
	pages_st = pagenum;
	
	ajax_window( section, "set_page&sub="+sid+"&st="+pagenum, id );
}

/**
* Обновление информации о количестве страниц
*
* Обновляет значение переменных с номером текущей
* страницы и общим числом доступных страниц.
*
* @param	int		Новый номер текущей страницы
* @param	int		Новое общее число страниц
*
* @return	void
*/

function update_pages_number( new_total, new_st )
{
	pages_total = new_total;
	pages_st = new_st;
}

/**
* Вывод информации о системе
*
* Выводит окно с информацией о системе.
*
* @return	void
*/

function ajax_about_ados()
{
	ajax_window( "settings", "about_ados", 0 );
}

/**
* Проверка наличия обновлений
*
* Вызывает функцию передачи запроса для проверки
* наличия новых версий системы.
*
* @return	void
*/

function ajax_check_for_update()
{
	ajax_window( "settings", "check_update", 0 );
}

/**
* Удаление блокировочного файла
*
* Выводит запрос на удаление блокировочного файла.
*
* @param	string	Идентификатор файла
*
* @return	void
*/

function ajax_delete_lock_file( name )
{
	if( !confirm( lang_confirm_delete_lock_file ) ) return;
	
	ajax_window( "settings", "delete_lock_file", name );
}

/**
* Выход из системы
*
* Очищает cookie со сведениями о пользователе и
* обновляет текущую страницу.
*
* @return	void
*/

function ajax_logout()
{
	if( !confirm( lang_confirm_logout ) ) return;
	
	my_setcookie( "user_id", 0 );
	my_setcookie( "pass_hash", "" );
	my_setcookie( "list_active", "" );
	
	window.location = base_url;
}

/**
* Изменение языка системы для пользователя
*
* Запускает функцию вывода AJAX окна,
* передавая ей соответствующий параметр.
* 
* @param	object	Объект формы выбора языка
*
* @return	void
*/

function ajax_change_lang( form )
{
	if( form.tab.value == "auth" ) form.submit();
	else ajax_window( "users", "change_user_lang", form.lang_selector.value );
}

/**
* Обработка PNG для MSIE 5.5+
*
* Применяет фильтр прозрачности для всех рисунков на странице.
*
* @return	void
*/

function parse_png()
{
	count_img = document.images.length;
	
	for( var i = 0; i < count_img; i++ )
	{
		if( !document.images[i] ) break;
		
		var img = document.images[i];
		var imgName = img.src.toUpperCase();
		
		if( imgName.substring( imgName.length-3, imgName.length ) == "PNG" )
		{
			var imgID = ( img.id ) ? "id='" + img.id + "' " : "";
			var imgClass = ( img.className ) ? "class='" + img.className + "' " : "";
			var imgTitle = ( img.title ) ? "title='" + img.title + "' " : "title='" + img.alt + "' ";
			var imgStyle = "display:inline-block;vertical-align:middle;" + img.style.cssText;
			
			if( img.align == "left" ) imgStyle = "float:left;" + imgStyle;
			if( img.align == "right" ) imgStyle = "float:right;" + imgStyle;
			if( img.parentElement.href ) imgStyle = "cursor:hand;" + imgStyle;           

			img.outerHTML = "<span " + imgID + imgClass + imgTitle
						  + " style=\"" + "width:" + img.width + "px; height:" + img.height + "px;" + imgStyle + ";"
						  + "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader"
						  + "(src=\'" + img.src + "\', sizingMethod='scale');\"></span>";
						  
			i--;
		}
	}
}