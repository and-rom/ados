<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Шаблон для секции "Настройки"
*/

/**
* Класс, содержащий функции
* для секции настроек системы.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class template_settings
{
	/**
	* Ссылка для открытия списка параметров
	* авторизации для доменов.
	* 
	* @param 	string	Идентификатор ссылки
	*/
	
	function misc_list_link( $type="lang" )
	{
		$return[] = <<<EOF
		<a href="javascript:ajax_view_list('{$type}');">{$this->engine->lang['view_list']}</a>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Текущая версия программы, обслуживаемой
	* модулем, со ссылкой на обновление
	* информации
	* 
	* @param 	string	Версия программы
	* @param 	int		ID модуля
	*/
	
	function module_version( $version="", $id=0 )
	{
		$version = $version ? $version : "-".$this->engine->lang['module_no_version_info']."-";
		
		$return[] = <<<EOF
		{$version}&nbsp;<a href="javascript:ajax_module_version('{$id}')"><img src='images/module_update.png' alt='{$this->engine->lang['module_update_version']}' title='{$this->engine->lang['module_update_version']}' /></a>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Меню со вкладками для навигации по
	* группам настроек
	* 
	* @param 	string	Идентификатор вкладки
	* @param 	string	Активность вкладки
	*/
	
	function menu_tab( $id="", $active="" )
	{
		$display['active']   = $active == "active" ? "" : "style='display:none'";
		$display['inactive'] = $active == "active" ? "style='display:none'" : "";
		
		$return[] = <<<EOF
<div id="tab_{$id}_active" {$display['active']} class="settings_tab_active">{$this->engine->lang['setting_group_'.$id ]}</div>
<div id="tab_{$id}_inactive" {$display['inactive']} class="settings_tab_inactive"><a href="javascript:ajax_set_tab('{$id}');">{$this->engine->lang['setting_group_'.$id ]}</a></div>
EOF;
		
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Версия системы
	*/
	
	function ados_version()
	{
		$return[] = <<<EOF
		<b>{$this->engine->config['__engine__']['name']} {$this->engine->config['__engine__']['version']}</b> {$this->engine->lang['build']} {$this->engine->config['__engine__']['build']}
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Версия системы
	*/
	
	function ados_update()
	{
		$return[] = <<<EOF
		<div id="update_container_15">
			<div style="margin: 8px 0;">
			<a href="javascript:ajax_check_for_update();">{$this->engine->lang['info_ados_check_for_update']}</a>
			</div>
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}

	/**
	* Информация об авторских правах
	*/
	
	function ados_copyright()
	{
		$return[] = <<<EOF
		{$this->engine->config['__engine__']['copyright']} <a href="{$this->engine->config['__engine__']['url']}">{$this->engine->config['__engine__']['author']}</a>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Лицензионное соглашение
	*/
	
	function ados_eula()
	{
		$return[] = <<<EOF
		<a href="http://www.gnu.org/licenses/gpl.html">{$this->engine->lang['info_ados_get_eula']}</a>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* E-mail для связи
	*/
	
	function ados_contact()
	{
		$return[] = <<<EOF
		<a href="mailto:ados@dini.su">ados@dini.su</a>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Пожертвования
	*/
	
	function ados_donate()
	{
		$return[] = <<<EOF
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<div>
			<input type="hidden" name="cmd" value="_donations" />
			<input type="hidden" name="business" value="infodini@yandex.ru" />
			<input type="hidden" name="item_name" value="{$this->engine->config['__engine__']['full_name']} ({$this->engine->config['__engine__']['name']})" />
			<input type="hidden" name="no_shipping" value="0" />
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="currency_code" value="USD" />
			<input type="hidden" name="tax" value="0" />
			<input type="hidden" name="lc" value="RU" />
			<input type="hidden" name="bn" value="PP-DonationsBF" />
			<input type="text" name="amount"class="text_small" style="text-align:right;" value="" />
			<input type="image" src="images/donate_paypal.gif" border="0" name="submit" alt="{$this->engine->lang['info_ados_donate_paypal']}" /> (USD)
		</div>
		</form>
		<form action="https://money.yandex.ru/charity.xml" method="post">
		<div style="margin-top:5px;">
			<input type="hidden" name="to" value="4100134910215" />
			<input type="hidden" name="CompanyName" value="{$this->engine->config['__engine__']['full_name']} ({$this->engine->config['__engine__']['name']})" />
			<input type="hidden" name="CompanyLink" value="http://ados.dini.su" />
			<input type="text" name="CompanySum" class="text_small" style="text-align:right;" value="" />
			<input type="image" src="images/donate_yandex.gif" border="0" name="submit" alt="{$this->engine->lang['info_ados_donate_yandex']}" /> (RUB)
		</div>
		</form>
		<form method="post" action="https://merchant.webmoney.ru/lmi/payment.asp">
		<div style="margin-top:5px;">
			<input type="hidden" name="LMI_PAYMENT_DESC" value="{$this->engine->config['__engine__']['full_name']} ({$this->engine->config['__engine__']['name']})" />
			<input type="hidden" name="LMI_PAYMENT_NO" value="1" />
			<input type="hidden" name="LMI_PAYEE_PURSE" value="Z980539689299" />
			<input type="hidden" name="LMI_SIM_MODE" value="0" />
			<input type="text" name="LMI_PAYMENT_AMOUNT" class="text_small" style="text-align:right;" value="" />
			<input type="image" src="images/donate_wmz.gif" border="0" name="submit" alt="{$this->engine->lang['info_ados_donate_wmz']}" /> (USD)
		</div>
		</form>
		<form method="post" action="https://merchant.webmoney.ru/lmi/payment.asp">
		<div style="margin-top:5px;">
			<input type="hidden" name="LMI_PAYMENT_DESC" value="{$this->engine->config['__engine__']['full_name']} ({$this->engine->config['__engine__']['name']})" />
			<input type="hidden" name="LMI_PAYMENT_NO" value="1" />
			<input type="hidden" name="LMI_PAYEE_PURSE" value="R501247196211" />
			<input type="hidden" name="LMI_SIM_MODE" value="0" />
			<input type="text" name="LMI_PAYMENT_AMOUNT" class="text_small" style="text-align:right;" value="" />
			<input type="image" src="images/donate_wmr.gif" border="0" name="submit" alt="{$this->engine->lang['info_ados_donate_wmr']}" /> (RUB)
		</div>
		</form>
EOF;
		return implode( "\n", $return )."\n";
	}
	
	/**
	* Нижняя часть окна
	*/
	
	function ados_window_bottom()
	{
		$return[] = <<<EOF
		<div class="form_submit" style="padding: 7px;">
			<a href="javascript:ajax_window_close();" class="button">{$this->engine->lang['ok']}</a>
		</div>
EOF;
		return implode( "\n", $return )."\n";
	}
	
}

?>