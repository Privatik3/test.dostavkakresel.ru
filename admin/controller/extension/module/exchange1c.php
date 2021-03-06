<?php
class ControllerExtensionModuleExchange1c extends Controller {
	private $error = array();
	private $module_name = 'Exchange 1C 8.x';


	/**
	 * Пишет информацию в файл журнала
	 *
	 * @param	int				Уровень сообщения
	 * @param	string,object	Сообщение или объект
	 */
	private function log($message, $level=1) {
		if ($this->config->get('exchange1c_log_level') >= $level) {

			if ($level == 1) {
				$this->log->write(print_r($message,true));

			} elseif ($level == 2) {
				$memory_usage = sprintf("%.3f", memory_get_usage() / 1024 / 1024);
				list ($di) = debug_backtrace();
				$line = sprintf("%04s",$di["line"]);

				if (is_array($message) || is_object($message)) {
					$this->log->write($memory_usage . " Mb | " . $line);
					$this->log->write(print_r($message, true));
				} else {
					$this->log->write($memory_usage . " Mb | " . $line . " | " . $message);
				}
			}
		}
	} // log()


	/**
	 * Выводит сообщение
	 */
	private function echo_message($ok, $message="") {
		if ($ok) {
			echo "success\n";
			$this->log("[ECHO] success",2);
			if ($message) {
				echo $message;
				$this->log("[ECHO] " . $message,2);
			}
		} else {
			echo "failure\n";
			$this->log("[ECHO] failure",2);
			if ($message) {
				echo $message;
				$this->log("[ECHO] " . $message,2);
			}
		};
	} // echo_message()


	/**
	 * Определяет значение переменной ошибки
	 */
	private function setParamError(&$data, $param) {
		if (isset($this->request->post[$param])) {
			$data['error_'.$param] = $this->request->post[$param];
		} else {
			$data['error_'.$param] = '';
		}
	} // setParamsError()


	/**
	 * ver 2
	 * update 2017-06-25
	 * Определяет значение переменной
	 */
	private function getParam($param, $default='') {

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			// Сохранение

//			var_dump("<pre>"); var_dump($this->request->post); var_dump("</pre>");

			if (isset($this->request->post['exchange1c_'.$param])) {
				return $this->request->post['exchange1c_'.$param];
			}

		} else {
			// Чтение

//			var_dump("<pre>"); var_dump($this->config->get('exchange1c_'.$param)); var_dump("</pre>");

			if ($this->config->get('exchange1c_'.$param)) {
				return $this->config->get('exchange1c_'.$param);
			}

		}
		return $default;

	} // getParam()


	/**
	 * Выводит форму текстового многострочного поля
	 */
	private function htmlTextarea($name, $param) {
		$value = $this->getParam($name);
		if (!$value && isset($param['default'])) $value = $param['default'];
		$tmpl = '<textarea class="form-control" id="exchange1c_'.$name.'" name="exchange1c_'.$name.'" rows="6">'.$value.'</textarea>';
		return $tmpl;
	} // htmlTextarea()


	/**
	 * Выводит форму выбора значений
	 */
	private function htmlSelect($name, $param) {
		$value = $this->getParam($name);
        if (!$value && isset($param['default'])) $value = $param['default'];
		$disabled = isset($param['disabled']) ? ' disabled="true"' : '';
		$tmpl = '<select name="exchange1c_'.$name.'" id="exchange1c_'.$name.'" class="form-control"'.$disabled.'>';
		foreach ($param['options'] as $option => $text) {
			$selected = ($option == $value ? ' selected="selected"' : '');
			$tmpl .= '<option value="'.$option.'"'.$selected.'>'.$text.'</option>';
		}
		$tmpl .= '</select>';
		return $tmpl;
	} // htmlSelect()


	/**
	 * ver 1
	 * update 2017-06-09
	 * Выводит форму выбора несколько значений checkbox
	 */
	private function htmlCheckbox($name, $param) {

		$value = $this->getParam($name);

		$tmpl = '<div name="exchange1c_' . $name . '" id="exchange1c_' . $name . '" class="well well-sm" style="height:150px; overflow:auto;">';

		foreach ($param['options'] as $option => $text) {
			$checked = '';
			if (is_array($value)) {
				$checked = (array_search($option, $value) !== false ? ' checked="checked"' : '');
			}
			$tmpl .= '<div class="checkbox"><label><input type="checkbox" name="exchange1c_' . $name . '[]" value="' . $option.'"' . $checked . '>&nbsp;' . $text . '</label></div>';
		}

		$tmpl .= '</div>';
		return $tmpl;

	} // htmlCheckbox()


	/**
	 * ver 2
	 * update 2017-07-09
	 * Выводит форму переключателя "Да"+"Нет" или "Вкл"+"Откл"
	 */
	private function htmlRadio($name, $param) {

		$value = $this->getParam($name);

		if (!$value) $value = "0";

		$disabled = isset($param['disabled']) ? ' disabled="true"' : '';

		if (isset($param['text'])) {

			if ($param['text'] == 'on_off') {
				$text1 = 'text_on';
				$text0 = 'text_off';
			} else {
				$text1 = 'text_yes';
				$text0 = 'text_no';
			}

		} else {

			$text1 = 'text_yes';
			$text0 = 'text_no';
		}

		//var_dump("<pre>"); var_dump($name); var_dump($value); var_dump("</pre>");
		$id = isset($param['id']) ? ' id="'.$param['id'].'"' : '';
		$tmpl = '<label class="radio-inline">';
		$tmpl .= '<input type="radio" name="exchange1c_'.$name.'" value="1"'.($value == "1" ? ' checked = "checked"' : '').$disabled.'>';
		$tmpl .= '&nbsp;'.$this->language->get($text1);
		$tmpl .= '</label>';
		$tmpl .= '<label class="radio-inline">';
		$tmpl .= '<input type="radio" name="exchange1c_'.$name.'" value="0"'.($value == "0" ? ' checked = "checked"' : '').$disabled.'>';
		$tmpl .= '&nbsp;'.$this->language->get($text0);
		$tmpl .= '</label>';

		return $tmpl;

	} // htmlRadio()


	/**
	 * Формирует форму кнопки
	 * 	onclick="$('#form-exchange1c').attr('action','<?php echo $refresh; ?>&refresh=1').submit()"
	 *	$('#form-exchange1c').attr('action','http://opencart2302.ptr-print.ru/admin/index.php?route=extension/module/exchange1c/refresh&token=fza09Op5TOrxudmiD58SDKDpWBr18mNi&refresh=1').submit()
	 */
	private function htmlButton($name, $param) {

		$onclick = '';
		if (!empty($param['ver'])) {
			$onclick = ' onclick="update(' . $param['ver'] . ')"';
			//$onclick = ' onclick="$(\'#form-exchange1c\').attr(\'action\',\'' . $this->url->link('extension/module/exchange1c/update', 'token=' . $this->session->data['token'], 'SSL') . '&version=' . $param['ver'] . '\').submit()"';
		}
		$tmpl = '<button' . $onclick . ' id="exchange1c-button-'.$name.'" class="btn btn-primary" type="button" data-loading-text="' . $this->language->get('entry_button_'.$name). '">';
		$tmpl .= '<i class="fa fa-trash-o fa-lg"></i> ' . $this->language->get('text_button_'.$name) . '</button>';

		return $tmpl;

	} // htmlButton()


	/**
	 * Формирует форму картинки
	 */
	private function htmlImage($name, $param) {

		$tmpl = '<a title="" class="img_thumbnail" id="thumb-image0" aria-describedby="popover" href="" data-original-title="" data-toggle="image">';
		$tmpl .= '<img src="' . $param['thumb'] . '" data-placeholder="' . $param['ph'] . '" alt="" />';
		$tmpl .= '<input name="exchange1c_' . $name . '" id="input_image0" value="' . $param['value'] . '" type="hidden" /></a>';

		return $tmpl;

	} // htmlImage()


	/**
	 * Формирует форму поля ввода
	 */
	private function htmlInput($name, $param, $type='text') {

		$value = $this->getParam($name);

		if (empty($value) && !empty($param['default'])) $value = $param['default'];

		$disabled = isset($param['disabled']) ? ' disabled="true"' : '';

		if ($this->language->get('ph_'.$name) != 'ph_'.$name) {
			$placeholder = ' placeholder="' . $this->language->get('ph_'.$name) . '"';
		} else {
			$placeholder = '';
		}

		$tmpl = '<input class="form-control"' . $placeholder . ' type="'.$type.'" id="exchange1c_'.$name.'" name="exchange1c_'.$name.'" value="'.$value.'"'.$disabled.'>';

		return $tmpl;

	} // htmlInput()


	/**
	 * ver 2
	 * update 2017-07-09
	 * Формирует форму ...
	 */
	private function htmlParam($name, $text, $param, $head=false) {

		$tmpl = '';

		if (isset($param['id'])) {
			$tmpl = '<div class="form-group" id="' . $param['id'] . '">';
		}

		if ($head) {
			$tmpl .= '<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-pencil"></i>' . $this->language->get('legend_'.$name) . '</h3></div>';
		}

		$label_width = isset($param['width'][0]) ? $param['width'][0] : 2;
		$entry_width = isset($param['width'][1]) ? $param['width'][1] : 3;
		$desc_width = isset($param['width'][2]) ? $param['width'][2] : 7;

		if ($label_width) {
			$tmpl .= '<label class="col-sm-'.$label_width.' control-label">'. $this->language->get('entry_'.$name) . '</label>';
		}

		$tmpl .= '<div class="col-sm-'.$entry_width.'">' . $text . '</div>';

		if ($desc_width) {
			$tmpl .= '<div class="col-sm-'.$desc_width.'"><div class="alert alert-info"><i class="fa fa-info-circle"></i>&nbsp;'. $this->language->get('desc_'.$name) . '</div></div>';
		}

		if (isset($param['id'])) {
			$tmpl .= '</div>';
		}

		return $tmpl;

	} // HtmlParam()


	/**
	 * ver 2
	 * update 2017-05-02
	 * Проверка разрешения на изменение
	 */
	private function validate() {
		//$data['lang'] = $this->load->language('extension/module/exchange1c');
		if (!$this->user->hasPermission('modify', 'extension/module/exchange1c')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	} // validate()


	/**
	 * ver 2
	 * update 2017-05-04
	 * Функция при нажатии кнопки "записать и обновить"
	 */
	public function refresh() {
		$this->index(true);
	}


	/**
	 * ver 7
	 * update 2017-11-24
	 * Основная функция
	 */
	public function index($refresh = false) {

		$data['lang'] = $this->load->language('extension/module/exchange1c');

		$this->load->model('tool/image');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$data['text_info'] = "";
		$this->load->model('extension/exchange1c');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			// При нажатии кнопки сохранить
			$settings = $this->request->post;
			$settings['exchange1c_version'] = $this->config->get('exchange1c_version');
			$settings['exchange_date'] = $this->config->get('exchange_date');
			$settings['exchange_statistics'] = $this->config->get('exchange_statistics');
			$settings['exchange1c_order_date'] = $this->config->get('exchange1c_order_date');
			$settings['exchange1c_table_fields'] = $this->config->get('exchange1c_table_fields');
			$settings['exchange1c_CMS_version'] = VERSION;
			$settings['exchange1c_table_fields'] = $this->model_extension_exchange1c->defineTableFields();

			$this->model_setting_setting->editSetting('exchange1c', $settings);
			$this->session->data['success'] = $this->language->get('text_success');

			if (!$refresh) {
				$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'));
			}

			$data['text_info'] = "Настройки сохранены";
			$this->log("Настройки сохранены", 2);

		} else {
			$settings = $this->model_setting_setting->getSetting('exchange1c');
		}

		if (isset($settings['exchange1c_version'])) {
			$data['version'] = $settings['exchange1c_version'];
		} else {
			$data['version'] = "0.0.0.0";
			$this->error['warning'] = "Модуль не установлен!";
			$settings['exchange1c_version'] = $this->model_extension_exchange1c->version();
			//$this->model_setting_setting->editSetting('exchange1c', $settings);
			$this->log("Обнаружен первый вход в админку");
		}
		$data['url_connect'] = HTTP_CATALOG . "export/exchange1c.php";

		$data['exchange1c_config_icon'] = $this->getParam('config_icon');

		// Формирование $data['error_warning']
		$this->setParamError($data, 'warning');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = "";
		}
		// Проверка базы данных
		$error = $this->model_extension_exchange1c->checkDB();
		if ($error) {
			$data['error_warning'] .= "<br>" . $error;
		}

		$this->setParamError($data, 'image');
		$this->setParamError($data, 'exchange1c_username');
		$this->setParamError($data, 'exchange1c_password');

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text'		=> $this->language->get('text_home'),
			'href'		=> $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator'	=> false
		);
		$data['breadcrumbs'][] = array(
			'text'		=> $this->language->get('text_module'),
			'href'		=> $this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'),
			'separator'	=> ' :: '
		);
		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/exchange1c', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);
		$data['token'] = $this->session->data['token'];
		$data['refresh'] = $this->url->link('extension/module/exchange1c/refresh', 'token=' . $this->session->data['token'], 'SSL');
		$data['action'] = $this->url->link('extension/module/exchange1c', 'token=' . $this->session->data['token'], 'SSL');
		$data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL');

		/**
		 * ГЕНЕРАЦИЯ ШАБЛОНА
		 */

		// Магазины
		if (isset($this->request->post['exchange1c_stores'])) {
			$data['exchange1c_stores'] = $this->request->post['exchange1c_stores'];
		}
		else {
			$data['exchange1c_stores'] = $this->config->get('exchange1c_stores');
			if(empty($data['exchange1c_stores'])) {
				$data['exchange1c_stores'][] = array(
					'store_id'	=> 0,
					'name'		=> ''
				);
			}
		}

		// Таблица настроек базы данных

		// свойства из торговой системы
		$data['exchange1c_properties'] 		= $this->getParam('properties', array());
		// таблица настройки загрузки заказов
		$data['exchange1c_order_import'] 	= $this->getParam('order_import', array());
		// таблица настройки выгрузки заказов
		$data['exchange1c_order_export'] 	= $this->getParam('order_export', array());
		// таблица настройки видов доставки
		$data['exchange1c_order_delivery'] 	= $this->getParam('order_delivery', array());
		// Виды доставки
		$data['order_types_of_delivery'] = array(
			0 => 'в разработке0',
			1 => 'в разработке1',
			2 => 'в разработке2'
		);

		// Поля товара для записи
		$data['product_fields'] = array(
			''				=> $this->language->get('text_not_import')
			,'model'		=> $this->language->get('text_model')
			,'sku'			=> $this->language->get('text_sku')
			,'upc'			=> $this->language->get('text_upc')
			,'ean'			=> $this->language->get('text_ean')
			,'jan'			=> $this->language->get('text_jan')
			,'isbn'			=> $this->language->get('text_isbn')
			,'shipping'		=> $this->language->get('text_shipping')
			,'location'		=> $this->language->get('text_location')
			,'mpn'			=> $this->language->get('text_mpn')
			,'weight'		=> $this->language->get('text_weight')
			,'width'		=> $this->language->get('text_width')
			,'height'		=> $this->language->get('text_height')
			,'length'		=> $this->language->get('text_length')
			,'status'		=> $this->language->get('text_status')
			,'minimum'		=> $this->language->get('text_minimum')
			,'sort_order'	=> $this->language->get('text_sort_order')
		);

		// Картинки
		$images = array(
			'watermark'	=> array(
				'ph'			=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'value'			=> $this->getParam('watermark'),
				'thumb'			=> $this->getParam('watermark') ? $this->model_tool_image->resize($this->getParam('watermark'), 100, 100) : $this->model_tool_image->resize('no_image.png', 100, 100)
			)
		);

		// Уровень записи в журнал
		$log_level_list = array(
			0	=> $this->language->get('text_log_level_0'),
			1	=> $this->language->get('text_log_level_1'),
			2	=> $this->language->get('text_log_level_2'),
			3	=> $this->language->get('text_log_level_3')
		);

		// SEO товары
		if (isset($this->request->post['exchange1c_seo_product_tags'])) {
			$data['exchange1c_seo_product_tags'] = $this->request->post['exchange1c_seo_product_tags'];
		} else {
			$data['exchange1c_seo_product_tags'] = '{name}, {sku}, {brand}, {model}, {cats}, {prod_id}, {cat_id}';
		}
        // SEO категории
		if (isset($this->request->post['exchange1c_seo_category_tags'])) {
			$data['exchange1c_seo_category_tags'] = $this->request->post['exchange1c_seo_category_tags'];
		} else {
			$data['exchange1c_seo_category_tags'] = '{cat}, {cat_id}';
		}
		// SEO производители
		if (isset($this->request->post['exchange1c_seo_manufacturertags'])) {
			$data['exchange1c_seo_manufacturer_tags'] = $this->request->post['exchange1c_seo_manufacturer_tags'];
		} else {
			$data['exchange1c_seo_manufacturer_tags'] = '{brand}, {brand_id}';
		}
		$list_product = array(
			'disable'		=> $this->language->get('text_disable'),
			'template'		=> $this->language->get('text_template')
			//'import'		=> $this->language->get('text_import')
		);
		$list_category = array(
			'disable'		=> $this->language->get('text_disable'),
			'template'		=> $this->language->get('text_template')
		);
		$list_seo_mode = array(
			'disable'		=> $this->language->get('text_disable')
		);
		if ($this->config->get('config_seo_url') == 1) {
			$list_seo_mode['if_empty'] 	= $this->language->get('text_if_empty');
			$list_seo_mode['overwrite']	= $this->language->get('text_overwrite');
		}

		// Статус товара по умолчанию при отсутствии
		$this->load->model('localisation/stock_status');
		$stock_statuses_info = $this->model_localisation_stock_status->getStockStatuses();
		$select_stock_statuses = array();
		$select_stock_statuses[] = "< " . $this->language->get('text_not_change') . " >";
		foreach ($stock_statuses_info as $status) {
			$select_stock_statuses[$status['stock_status_id']] = $status['name'];
		}

		// список статусов заказов
		$this->load->model('localisation/order_status');
		$order_statuses_info = $this->model_localisation_order_status->getOrderStatuses();
		$order_statuses = array();
		$order_statuses[] = $this->language->get('text_do_not_use');
		foreach ($order_statuses_info as $order_status) {
			$order_statuses[$order_status['order_status_id']] = $order_status['name'];
		}
		$data['order_statuses'] = $order_statuses;

		$data['order_event'] = array(
			'posted'			=> $this->language->get('text_order_posted'),
			'paid'				=> $this->language->get('text_paid'),
			'paid_shipped'		=> $this->language->get('text_paid_shipped'),
			'shipped'			=> $this->language->get('text_shipped'),
			'expired_payment'	=> $this->language->get('text_expired_payment')
		);

		// Дата и время выгрузки заказов
		if (isset($this->request->post['exchange1c_order_date'])) {
			$data['order_date_export'] = $this->request->post['exchange1c_order_date'];
		} else {
			if ($this->config->get('exchange1c_order_date')) {
				$data['order_date_export'] = strftime('%Y-%m-%dT%H:%M', strtotime($this->config->get('exchange1c_order_date')));
			} else {
				$data['order_date_export'] = strftime('%Y-%m-%dT%H:%M', strtotime('2000-01-01 00:00:00'));
			}
		}

		$list_options = array(
			'feature'	=> $this->language->get('text_product_options_feature')
			,'related'	=> $this->language->get('text_product_options_related')
			//,'certine'	=> $this->language->get('text_product_options_certine')
		);

		$list_options_type = array(
			'select'	=> $this->language->get('text_product_options_type_select'),
			'radio'		=> $this->language->get('text_product_options_type_radio')
		);

		$select_product_name = array(
			'disable'	=> $this->language->get('text_not_import'),
			'name'		=> $this->language->get('text_name'),
			'fullname'	=> $this->language->get('text_fullname'),
			'manually'	=> $this->language->get('text_field_manually')
			//'requisite'	=> $this->language->get('text_requisite')
		);

		$select_sync_attributes = array(
			'guid'    	=> $this->language->get('text_guid'),
			'name'		=> $this->language->get('text_name'),
		);

		$list_price_import_to = array(
			'discount'    	=> $this->language->get('text_discount'),
			'special'		=> $this->language->get('text_special'),
		);

		$list_order_date_ship = array(
			'order'    		=> $this->language->get('text_date_order'),
			'exchange'		=> $this->language->get('text_date_exchange'),
			'disable'		=> $this->language->get('text_disable')
		);

		// Типы цен
		$list_table_prices = array();
		$list_table_prices[] = array(
			'name'    		=> 'product',
			'desc'			=> $this->language->get('text_table_product')
		);
		$list_table_prices[] = array(
			'name'    		=> 'discount',
			'desc'			=> $this->language->get('text_table_discount')
		);
		$list_table_prices[] = array(
			'name'    		=> 'special',
			'desc'			=> $this->language->get('text_table_special')
		);
		$data['table_prices'] = $list_table_prices;

		// Очистка остатков
		$list_flush_quantity = array(
			'none'    		=> $this->language->get('text_disable'),
			'all'			=> $this->language->get('text_flush_quantity_all'),
			'category'		=> $this->language->get('text_flush_quantity_category')
		);

		// Валюты
		$this->load->model('localisation/currency');
		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		// Список типов опций в товаре
		$select_product_options_type = array(
			'select'    	=> $this->language->get('text_html_select'),
			'radio'			=> $this->language->get('text_html_radio'),
			'chekbox'		=> $this->language->get('text_html_checkbox')
		);

		// Список типов загружаемых типов свойств в товаре
		$select_product_property_type = array(
			'digit'    		=> $this->language->get('text_digit'),
			'string'   		=> $this->language->get('text_string'),
			'reference'		=> $this->language->get('text_reference')
		);

		// Режим синхронизации товаров
		$select_product_sync_mode = array(
			'guid'  	=> $this->language->get('text_guid'),
			'sku'    	=> $this->language->get('text_sku'),
			'name'		=> $this->language->get('text_name'),
			'ean'		=> $this->language->get('text_ean'),
			'code'		=> $this->language->get('text_code')
		);

		// Статусы товаров
		$select_product_status = array(
			'status_not_change' 	=> $this->language->get('text_status_not_change'),
			'disable_before_import'	=> $this->language->get('text_product_status_disable_before_import'),
			'disable_not_import'	=> $this->language->get('text_not_import_status_disable'),
			'disable_new'			=> $this->language->get('text_product_new_status_disable'),
			'status_on'				=> $this->language->get('text_on')
		);

		// Режимы импорта
		$select_mode_import = array(
			'create_update' 	=> $this->language->get('text_create_update'),
			'only_update'		=> $this->language->get('text_only_update'),
			'only_create'		=> $this->language->get('text_only_create'),
			'not_import'		=> $this->language->get('text_not_import')
		);

		// Режимы группы атрибута
		$select_attribute_group_mode = array(
			'only_new'			=> $this->language->get('text_only_new'),
			'always'			=> $this->language->get('text_always')
		);

		// Режимы установки названия группы атрибута
		$select_attribute_group_name_mode = array(
			'manual'			=> $this->language->get('text_manual'),
			'from_name'			=> $this->language->get('text_from_name')
		);

		// Генерация опций
		$params = array(
			'username'									=> array('type' => 'input',)
			,'password'									=> array('type' => 'password',)
			,'cleaning_db' 								=> array('type' => 'button')
			,'cleaning_links' 							=> array('type' => 'button')
			,'cleaning_cache' 							=> array('type' => 'button')
			,'cleaning_old_images' 						=> array('type' => 'button')
			,'generate_seo' 							=> array('type' => 'button')
			,'flush_quantity'							=> array('type' => 'select', 'options' => $list_flush_quantity)
			,'watermark'								=> array('type' => 'image')
			,'watermark_prefix'							=> array('type' => 'input')
			,'watermark_suffix'							=> array('type' => 'input')
			,'watermark_old_no_delete'					=> array('type' => 'radio')
			,'allow_ip'									=> array('type' => 'textarea')
			,'status_new_product'						=> array('type' => 'radio', 'default' => 1, 'text' => 'on_off')
			,'fill_parent_cats'							=> array('type' => 'radio', 'default' => 1)
			,'synchronize_attribute_by' 	     	 	=> array('type' => 'select', 'options' => $select_sync_attributes, 'default' => 'guid')
			,'module_status'							=> array('type' => 'radio', 'default' => 1, 'text' => 'on_off')
			,'flush_log'								=> array('type' => 'radio', 'default' => 1)
			,'currency_convert'							=> array('type' => 'radio', 'default' => 1)
			,'convert_orders_cp1251'					=> array('type' => 'radio', 'default' => 1)
			,'upload_files_unlink'						=> array('type' => 'radio', 'default' => 1)
			,'seo_product_seo_url_import'				=> array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1)
			,'seo_product_seo_url_template'				=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_product_meta_title_import'			=> array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1)
			,'seo_product_meta_title_template'			=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_product_meta_description_import'		=> array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1)
			,'seo_product_meta_description_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_product_meta_keyword_import'			=> array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1)
			,'seo_product_meta_keyword_template'		=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_product_tag_import'					=> array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1)
			,'seo_product_tag_template'					=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_category_seo_url_template'			=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_category_meta_title_template'			=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_category_meta_description_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_category_meta_keyword_template'		=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_manufacturer_seo_url_template'		=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_manufacturer_meta_title_import'		=> array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1)
			,'seo_manufacturer_meta_title_template'		=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_manufacturer_meta_description_import'	=> array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1)
			,'seo_manufacturer_meta_description_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_manufacturer_meta_keyword_import'		=> array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1)
			,'seo_manufacturer_meta_keyword_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'order_currency'							=> array('type' => 'input')
			,'ignore_price_zero'						=> array('type' => 'radio', 'default' => 1)
			,'log_memory_use_view'						=> array('type' => 'radio', 'default' => 1)
			,'log_debug_line_view'						=> array('type' => 'radio', 'default' => 1)
			,'log_level'								=> array('type' => 'select', 'options' => $log_level_list)
			,'seo_product_mode'							=> array('type' => 'select', 'options' => $list_seo_mode, 'width' => array(1,2,9))
			,'seo_product_seo_url'						=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_product_meta_title'					=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_product_meta_description'				=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_product_meta_keyword'					=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_product_tag'							=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_category_mode'						=> array('type' => 'select', 'options' => $list_seo_mode, 'width' => array(1,2,9))
			,'seo_category_seo_url'						=> array('type' => 'select', 'options' => $list_category, 'width' => array(1,2,0))
			,'seo_category_meta_title'					=> array('type' => 'select', 'options' => $list_category, 'width' => array(1,2,0))
			,'seo_category_meta_description'			=> array('type' => 'select', 'options' => $list_category, 'width' => array(1,2,0))
			,'seo_category_meta_keyword'				=> array('type' => 'select', 'options' => $list_category, 'width' => array(1,2,0))
			,'seo_manufacturer_mode'					=> array('type' => 'select', 'options' => $list_seo_mode, 'width' => array(1,2,9))
			,'seo_manufacturer_seo_url'					=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_manufacturer_meta_title'				=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_manufacturer_meta_description'		=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_manufacturer_meta_keyword'			=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'orders_export_modify'						=> array('type' => 'radio')
			,'order_notify'								=> array('type' => 'radio', 'default' => 1)
			,'order_modify_exchange'					=> array('type' => 'radio', 'default' => 1, 'width' => array(2,3,7))
			,'order_status_export'						=> array('type' => 'select', 'options' => $order_statuses, 'width' => array(2,3,7))
			,'order_status_exported'					=> array('type' => 'select', 'options' => $order_statuses, 'width' => array(2,3,7))
			,'order_notify_subject'						=> array('type' => 'input')
			,'order_notify_text'						=> array('type' => 'textarea')
			,'order_status_shipped'						=> array('type' => 'select', 'options' => $order_statuses, 'width' => array(2,3,7))
			,'order_status_paid'						=> array('type' => 'select', 'options' => $order_statuses, 'width' => array(2,3,7))
			,'order_status_completed'					=> array('type' => 'select', 'options' => $order_statuses, 'width' => array(2,3,7))
			,'order_reserve_product'					=> array('type' => 'radio')
			,'order_customer_export'					=> array('type' => 'radio', 'default' => 1)
			,'orders_import'							=> array('type' => 'radio', 'default' => 1)
			,'set_quantity_if_zero'						=> array('type' => 'input')
			,'export_module_to_all'						=> array('type' => 'radio')
			,'services_in_table_product'				=> array('type' => 'radio', 'default' => 1)
			,'clean_options'							=> array('type' => 'radio')
			,'clean_prices_full_import'					=> array('type' => 'radio', 'default' => 1)
			,'remove_doubles_links'						=> array('type' => 'button')
			,'delete_import_data'						=> array('type' => 'button')
			,'price_types_auto_load'					=> array('type' => 'radio', 'default' => 1)
			,'attribute_group_name_mode'				=> array('type' => 'select', 'options' => $select_attribute_group_name_mode)
			,'attribute_group_mode'						=> array('type' => 'select', 'options' => $select_attribute_group_mode)
			,'attribute_group_name'						=> array('type' => 'input', 'default' => 'Свойства')
			,'categories_no_import'						=> array('type' => 'radio')
			,'category_new_no_create'					=> array('type' => 'radio', 'default' => 1)
			,'category_exist_status_enable'				=> array('type' => 'radio')
			,'category_new_status_disable'				=> array('type' => 'radio')
			,'category_attributes_parse'				=> array('type' => 'radio', 'default' => 1)
			,'warehouse_quantity_import'				=> array('type' => 'radio', 'default' => 1)
			,'product_attribute_mode_import'			=> array('type' => 'select', 'options' => $select_mode_import)
			,'product_link_option'						=> array('type' => 'radio')
			,'product_feature_import'					=> array('type' => 'radio', 'default' => 1)
			,'product_price_no_import'					=> array('type' => 'radio', 'default' => 1)
			,'product_options_mode'						=> array('type' => 'select', 'options' => $list_options)
			,'product_options_subtract'					=> array('type' => 'radio', 'default' => 1)
			,'product_options_empty_ignore'				=> array('type' => 'radio')
			,'product_default_stock_status'				=> array('type'	=> 'select', 'options' => $select_stock_statuses)
			,'product_images_check'						=> array('type' => 'radio')
			,'product_images_cache_clean'				=> array('type' => 'radio')
			,'product_option_name'						=> array('type' => 'input')
			,'product_sync_mode'						=> array('type' => 'select', 'options' => $select_product_sync_mode)
			,'product_options_type'						=> array('type' => 'select', 'options' => $select_product_options_type)
			,'product_property_type_no_import'			=> array('type' => 'checkbox', 'options' => $select_product_property_type)
			,'product_name'								=> array('type' => 'select', 'options' => $select_product_name, 'default' => 'name')
			,'product_name_field'						=> array('type' => 'input', 'default' => 'Наименование')
			,'product_status'							=> array('type' => 'select', 'options' => $select_product_status)
			,'product_images_no_import'					=> array('type' => 'radio')
			,'product_description_no_import'			=> array('type' => 'radio')
			,'product_manufacturer_no_import'			=> array('type' => 'radio')
			,'product_manufacturer_tag'					=> array('type' => 'input')
			,'product_taxes_no_import'					=> array('type' => 'radio', 'default' => 1)
			,'product_category_no_import'				=> array('type' => 'radio')
			,'product_disable_if_quantity_zero'			=> array('type' => 'radio')
			,'product_disable_if_price_zero'			=> array('type' => 'radio')
			,'product_guid_in_mpn'						=> array('type' => 'radio', 'default' => 1)
			,'product_sku_in_model'						=> array('type' => 'radio', 'default' => 1)
			,'product_no_create'						=> array('type' => 'radio')
			,'product_new_status_disable'				=> array('type' => 'radio')
			,'file_zip'									=> array('type' => 'radio')
			,'file_max_size'							=> array('type' => 'input', 'format' => 'int')
			,'log_filename'								=> array('type' => 'input')
			,'import_product_rules'						=> array('type' => 'textarea')
		);

		if (isset($settings['exchange1c_table_fields'])) {
			$tab_fields = $settings['exchange1c_table_fields'];
		}

		if (isset($tab_fields['product_description']['meta_h1'])) {
			$params['seo_product_meta_h1_import']			= array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1);
			$params['seo_product_meta_h1_template']			= array('type' => 'input', 'width' => array(0,9,0));
			$params['seo_product_meta_h1']					= array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0));
		}
		if (isset($tab_fields['category_description']['meta_h1'])) {
			$params['seo_category_meta_h1_import']			= array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1);
			$params['seo_category_meta_h1_template']		= array('type' => 'input', 'width' => array(0,9,0));
			$params['seo_category_meta_h1']					= array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0));
		}
		if (isset($tab_fields['manufacturer_description']['meta_h1'])) {
			$params['seo_manufacturer_meta_h1_import']		= array('type' => 'input', 'width' => array(0,9,0), 'hidden' => 1);
			$params['seo_manufacturer_meta_h1_template']	= array('type' => 'input', 'width' => array(0,9,0));
			$params['seo_manufacturer_meta_h1']				= array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0));
		}

		foreach ($params as $name => $param) {
			$html = '';
			switch ($param['type']) {
				case 'button':
					$html = $this->htmlButton($name, $param);
					break;
				case 'radio':
					$html = $this->htmlRadio($name, $param);
					break;
				case 'select':
					$html = $this->htmlSelect($name, $param);
					break;
				case 'image':
					$html = $this->htmlImage($name, $images[$name]);
					break;
				case 'input':
					$html = $this->htmlInput($name, $param);
					break;
				case 'password':
					$html = $this->htmlInput($name, $param, 'password');
					break;
				case 'textarea':
					$html = $this->htmlTextarea($name, $param);
					break;
				case 'checkbox':
					$html = $this->htmlCheckbox($name, $param);
					break;
			}
			if ($html)
				$data['html_'.$name] = $this->htmlParam($name, $html, $param);
		}

		// Статистика
		$data['statistics'] = $this->model_extension_exchange1c->getStatistics();
		$data['exchange_date'] = $this->config->get('exchange1c_xml_date');

		// Группы покупателей
		$this->load->model('customer/customer_group');
		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		array_unshift($data['customer_groups'], array('customer_group_id'=>0,'sort_order'=>0,'name'=>'--- Выберите ---'));

		// типы цен
		if (isset($this->request->post['exchange1c_price_type'])) {
			$data['exchange1c_price_type'] = $this->request->post['exchange1c_price_type'];
		}
		else {
			$data['exchange1c_price_type'] = $this->config->get('exchange1c_price_type');
			if(empty($data['exchange1c_price_type'])) {
				$data['exchange1c_price_type'] = array();
			}
		}

		// Валюты
		if (isset($this->request->post['exchange1c_currency'])) {
			$data['exchange1c_currency'] = $this->request->post['exchange1c_currency'];
		}
		else {
			$data['exchange1c_currency'] = $this->config->get('exchange1c_currency');
			if(empty($data['exchange1c_currency'])) {
				$data['exchange1c_currency'] = array();
			}
		}

	 	// максимальный размер загружаемых файлов
		$data['lang']['text_max_filesize'] = sprintf($this->language->get('text_max_filesize'), @ini_get('max_file_uploads'));
		$data['upload_max_filesize'] = ini_get('upload_max_filesize');
		$data['post_max_size'] = ini_get('post_max_size');

		$links_info = $this->model_extension_exchange1c->linksInfo();
		$data['links_product_info'] = $links_info['product_to_1c'];
		$data['links_category_info'] = $links_info['category_to_1c'];
		$data['links_manufacturer_info'] = $links_info['manufacturer_to_1c'];
		$data['links_attribute_info'] = $links_info['attribute_to_1c'];

	 	// информация о памяти
		$data['memory_limit'] = ini_get('memory_limit');
	 	// информация о времени выполнения PHP
		$data['max_execution_time'] = ini_get('max_execution_time');

		// Вывод шаблона
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/exchange1c', $data));

	} // index()


	/**
	 * Установка модуля
	 */
	public function install() {

		$this->load->model('setting/setting');
		$settings = $this->model_setting_setting->getSetting('exchange1c', 0);

		if (version_compare(VERSION, '2.3') >= 0) {
			$this->load->model('extension/extension');
			$this->model_extension_extension->install('module', 'exchange1c');
		}

		$this->load->model('extension/exchange1c');
		$this->model_extension_exchange1c->setEvents();
		$module_version = $this->model_extension_exchange1c->version();

		$this->load->model('extension/unit');
		$this->model_extension_unit->installUnit();


		$this->load->model('setting/setting');
		$settings['exchange1c_version'] 					= $module_version;
		$settings['exchange1c_name'] 						= 'Exchange 1C 8.x for OpenCart 2.x';
		$settings['exchange1c_CMS_version']					= VERSION;
		$settings['exchange1c_seo_category_name'] 			= '[category_name]';
		$settings['exchange1c_seo_parent_category_name'] 	= '[parent_category_name]';
		$settings['exchange1c_seo_product_name'] 			= '[product_name]';
		$settings['exchange1c_seo_product_price'] 			= '[product_price]';
		$settings['exchange1c_seo_manufacturer'] 			= '[manufacturer]';
		$settings['exchange1c_seo_sku'] 					= '[sku]';
		$settings['exchange1c_table_fields']				= $this->model_extension_exchange1c->defineTableFields();

		$this->model_setting_setting->editSetting('exchange1c', $settings);

		// Определение полей таблиц которые могут быть в разных версиях CMS

//		$this->load->model('extension/module');
//		$this->model_extension_module->addModule('exchange1c',
//			array(
//				'version'	=> $this->module_version,
//				'name'		=> $this->module_name
//			)
//		);

		// Изменения в базе данных
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "cart` WHERE `field` = 'product_unit_id'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` ADD  `product_unit_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER  `option`");
		}

		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order_product` WHERE `field` = 'product_unit_id'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "order_product` ADD  `product_unit_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER  `product_id`");
			$this->db->query("ADD INDEX (  `product_unit_id` )");
		}

		// Добавляем связь со значением атрибута
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_attribute` WHERE `field` = 'attribute_value_id'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_attribute` ADD `attribute_value_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER  `attribute_id`");
		}

		// Добавляем связь скидки с опцией
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_discount` WHERE `field` = 'product_feature_id'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_discount` ADD `product_feature_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER  `customer_group_id`");
		}

		// Добавляем связь акции с опцией
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_special` WHERE `field` = 'product_feature_id'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_special` ADD `product_feature_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER  `customer_group_id`");
		}

		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "product_attribute` WHERE `key_name` = 'value_id'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_attribute` ADD INDEX `value_id` ( `attribute_value_id` )");
		}

		// Добавим индекс, чтобы быстрее работал поиск по наименованию
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "option_description` WHERE `column_name` = 'name'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "option_description` ADD INDEX `name_key` ( `name` )");
		}

		// Добавим индекс, чтобы быстрее работал поиск по наименованию
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "option_value_description` WHERE `column_name` = 'name'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "option_value_description` ADD INDEX `name_key` ( `name` )");
		}

		// Добавим индекс, чтобы быстрее работал поиск по наименованию
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "attribute_description` WHERE `column_name` = 'name'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "attribute_description` ADD INDEX `name_key` ( `name` )");
		}

		// Добавим базовую единицу товара в товар
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` WHERE `field` = 'unit_id'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product` ADD `unit_id` INT ( 11 ) NOT NULL AFTER `quantity`");
		}

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		// Увеличиваем точность поля веса до тысячных
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` CHANGE `quantity` `quantity` decimal(15,3) NOT NULL DEFAULT 0.000 COMMENT 'Количество'");
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` CHANGE `weight` `weight` decimal(15,3) NOT NULL DEFAULT 0.000 COMMENT 'Вес'");

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_option_value` CHANGE `quantity` `quantity` decimal(15,3) NOT NULL DEFAULT 0 COMMENT 'Количество'");

		// Связь товаров с 1С
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "product_to_1c` (
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`guid` 						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид товара в УС',
				`version` 					VARCHAR(32) 	NOT NULL 				COMMENT 'Версия объекта в УС',
				UNIQUE KEY `product_link` (`product_id`, `guid`),
				FOREIGN KEY (`product_id`) 				REFERENCES `". DB_PREFIX ."product`(`product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Связь категорий с 1С
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "category_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "category_to_1c` (
				`category_id` 				INT(11) 		NOT NULL 				COMMENT 'ID категории',
				`guid` 						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид категории в УС',
				`version` 					VARCHAR(32) 	NOT NULL 				COMMENT 'Версия объекта в УС',
				UNIQUE KEY `category_link` (`category_id`,`guid`),
				FOREIGN KEY (`category_id`) 			REFERENCES `". DB_PREFIX ."category`(`category_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Свойства из 1С
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "attribute_to_1c` (
				`attribute_id` 				INT(11) 		NOT NULL 				COMMENT 'ID атрибута',
				`guid`						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид свойства в УС',
				`type`						VARCHAR(1) 		NOT NULL 				COMMENT 'Тип свойства в УС',
				`version`					VARCHAR(32) 	NOT NULL 				COMMENT 'Версия объекта в УС',
				UNIQUE KEY `attribute_link` (`attribute_id`, `guid`),
				FOREIGN KEY (`attribute_id`) 			REFERENCES `". DB_PREFIX ."attribute`(`attribute_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Значения свойства из 1С
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_value`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "attribute_value` (
				`attribute_value_id` 		INT(11) 		NOT NULL AUTO_INCREMENT	COMMENT 'ID значения атрибута',
				`attribute_id` 				INT(11) 		NOT NULL 				COMMENT 'ID атрибута',
				`guid`						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид значения свойства в УС',
				`name`						VARCHAR(255) 	NOT NULL 				COMMENT 'Название значения свойства',
				PRIMARY KEY (`attribute_value_id`),
				UNIQUE KEY `attribute_value_key` (`attribute_id`, `guid`),
				FOREIGN KEY (`attribute_id`) 			REFERENCES `". DB_PREFIX ."attribute`(`attribute_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Привязка опций к товару
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "option_to_product`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "option_to_product` (
				`option_id` 				INT(11) 		NOT NULL 				COMMENT 'ID опции',
				`product_id` 				VARCHAR(64) 	NOT NULL 				COMMENT 'Ид товара',
				UNIQUE KEY `option_link` (`option_id`, `product_id`),
				FOREIGN KEY (`option_id`) 				REFERENCES `". DB_PREFIX ."option`(`option_id`),
				FOREIGN KEY (`product_id`) 				REFERENCES `". DB_PREFIX ."product`(`product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Привязка производителя к каталогу 1С
		// В Ид производителя из 1С записывается либо Ид свойства сопоставленное либо Ид элемента справочника с производителями
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "manufacturer_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "manufacturer_to_1c` (
				`manufacturer_id` 			INT(11) 		NOT NULL 				COMMENT 'ID производителя',
				`guid` 						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид производителя в УС',
				UNIQUE KEY `manufacturer_link` (`manufacturer_id`, `guid`),
				FOREIGN KEY (`manufacturer_id`) 		REFERENCES `". DB_PREFIX ."manufacturer`(`manufacturer_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Привязка магазина к каталогу в 1С
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "store_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "store_to_1c` (
				`store_id` 					INT(11) 		NOT NULL 				COMMENT 'Код магазина',
				`guid` 						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид каталога в УС',
				UNIQUE KEY `store_link` (`store_id`, `guid`),
				FOREIGN KEY (`store_id`) 				REFERENCES `". DB_PREFIX ."store`(`store_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Остатки товара
		// Хранятся остатки товара как с характеристиками, так и без.
		// Если склады и характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_quantity` (
				`product_quantity_id` 		INT(11) 		NOT NULL AUTO_INCREMENT	COMMENT 'Счетчик',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'Ссылка на товар',
				`product_feature_id` 		INT(11) 		DEFAULT 0 NOT NULL		COMMENT 'Ссылка на характеристику товара',
				`warehouse_id` 				INT(11) 		DEFAULT 0 NOT NULL 		COMMENT 'Ссылка на склад',
				`quantity` 					DECIMAL(10,3) 	DEFAULT 0 				COMMENT 'Остаток',
				PRIMARY KEY (`product_quantity_id`),
				UNIQUE KEY `product_quantity_key` (`product_id`, `product_feature_id`, `warehouse_id`),
				FOREIGN KEY (`product_id`) 			REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_feature_id`) 	REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`warehouse_id`) 		REFERENCES `" . DB_PREFIX . "warehouse`(`warehouse_id`),
				INDEX (`product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// склады
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "warehouse`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "warehouse` (
				`warehouse_id` 				SMALLINT(3) 	NOT NULL AUTO_INCREMENT,
				`address_id`				INT(11)			NOT NULL				COMMENT 'Адрес склада',
				`name` 						VARCHAR(100) 	NOT NULL 			 	COMMENT 'Название склада в УС',
				`guid` 						VARCHAR(64) 	NOT NULL				COMMENT 'Ид склада в УС',
				`phone`						VARCHAR(64)		NOT NULL				COMMENT 'Телефон',
				`responsible`				VARCHAR(255)	NOT NULL				COMMENT 'Ответственный',
				PRIMARY KEY (`warehouse_id`),
				UNIQUE KEY `warehouse_link` (`warehouse_id`, `guid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Классификатор адресов
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "address_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "address_1c` (
				`address_id` 				INT(11) 		NOT NULL AUTO_INCREMENT,
				`country` 					VARCHAR(100) 	NOT NULL 			 	COMMENT 'Страна',
				`zip` 						VARCHAR(6) 		NOT NULL				COMMENT 'Почтовый индекс',
				`region`					VARCHAR(100)	NOT NULL				COMMENT 'Регион',
				`city`						VARCHAR(100)	NOT NULL				COMMENT 'Город',
				`street`					VARCHAR(100)	NOT NULL				COMMENT 'Улица',
				`home`						VARCHAR(8)		NOT NULL				COMMENT 'Дом',
				PRIMARY KEY (`address_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Характеристики товара
		// Если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "product_feature` (
				`product_feature_id` 		INT(11) 		NOT NULL AUTO_INCREMENT COMMENT 'Счетчик',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'Ссылка на товар',
				`ean` 						VARCHAR(14) 	NOT NULL DEFAULT '' 	COMMENT 'Штрихкод',
				`sku` 						VARCHAR(128) 	NOT NULL DEFAULT '' 	COMMENT 'Артикул',
				`guid` 						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид характеристики в УС',
				PRIMARY KEY (`product_feature_id`),
				UNIQUE KEY `product_feature_key` (`product_id`, `guid`),
				INDEX (`product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Типы цен
//		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "prices_to_1c`");
//		$this->db->query(
//			"CREATE TABLE `" . DB_PREFIX . "prices_to_1c` (
//				`guid` 						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид в УС',
//				`version`					VARCHAR(32) 	NOT NULL 				COMMENT 'Версия объекта в УС',
//				`name` 						VARCHAR(32) 	NOT NULL				COMMENT 'Название валюты в УС',
//				`table` 					VARCHAR(32) 	NOT NULL				COMMENT 'Название таблицы',
//				`quantity` 					INT(4) 			NOT NULL DEFAULT '1'	COMMENT 'Количество для скидок',
//				`priority`					INT(5) 			NOT NULL DEFAULT '0'	COMMENT 'Приоритет для скидок',
//				`currency_id` 				INT(11) 		NOT NULL 				COMMENT 'Валюта сайта',
//				`tax_rate_id` 				VARCHAR(32) 	NOT NULL 				COMMENT 'Налог сайта',
//				UNIQUE KEY `price_key` (`name`, `guid`)
//			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
//		);

		// Значения характеристики товара(доп. значения)
		// Если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature_value`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "product_feature_value` (
				`product_feature_id` 		INT(11) 		NOT NULL 				COMMENT 'ID характеристики товара',
				`product_option_id` 		INT(11) 		NOT NULL 				COMMENT 'ID опции товара',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`product_option_value_id` 	INT(11) 		NOT NULL 				COMMENT 'ID значения опции товара',
				UNIQUE KEY `product_feature_value_key` (`product_feature_id`, `product_id`, `product_option_value_id`),
				FOREIGN KEY (`product_feature_id`) 		REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`product_option_id`) 		REFERENCES `" . DB_PREFIX . "product_option`(`product_option_id`),
				FOREIGN KEY (`product_id`) 				REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_option_value_id`)	REFERENCES `" . DB_PREFIX . "product_option_value`(`product_option_value_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Цены, если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_price`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "product_price` (
				`product_price_id` 			INT(11) 		NOT NULL AUTO_INCREMENT	COMMENT 'Счетчик',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`product_feature_id` 		INT(11) 		NOT NULL DEFAULT '0' 	COMMENT 'ID характеристики товара',
				`customer_group_id`			INT(11) 		NOT NULL DEFAULT '0'	COMMENT 'ID группы покупателя',
				`price` 					DECIMAL(15,4) 	NOT NULL DEFAULT '0'	COMMENT 'Цена',
				PRIMARY KEY (`product_price_id`),
				UNIQUE KEY `product_price_key` (`product_id`, `product_feature_id`, `customer_group_id`),
				FOREIGN KEY (`product_id`) 				REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_feature_id`) 		REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				INDEX (`product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		$this->log->write("Включен модуль " . $this->module_name . " версии " . $this->model_extension_exchange1c->version());

	} // install()


	/**
	 * ver 2
	 * update 2017-04-30
	 * Деинсталляция
	 */
	public function uninstall() {


		$this->load->model('extension/exchange1c');
		$table_fields = $this->model_extension_exchange1c->defineTableFields();

		$this->load->model('extension/unit');
		$this->model_extension_unit->uninstallUnit();

		$this->load->model('extension/event');
		$this->model_extension_event->deleteEvent('exchange1c');

		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('exchange1c');

		//$this->load->model('extension/modification');
		//$modification = $this->model_extension_modification->getModificationByCode('exchange1c');
		//if ($modification) $this->model_extension_modification->deleteModification($modification['modification_id']);

		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "category_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "manufacturer_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "store_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_price`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "option_to_product`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_unit`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature_value`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "warehouse`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "address_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_value`");

		// Удаляем все корректировки в базе

		// Изменения в базе данных
		$change = false;
		if (isset($table_fields['cart']['product_unit_id'])) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` DROP  `product_unit_id`");
			$change = true;
		}
		if ($change) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` DROP INDEX  `cart_id` ,	ADD INDEX  `cart_id` (  `customer_id` ,  `session_id` ,  `product_id` ,  `recurring_id`)");
		}
		if (isset($table_fields['order_product']['product_unit_id'])) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "order_product` DROP  `product_unit_id`");
			$change = true;
		}

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		// Увеличиваем точность поля веса до тысячных
		@$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` CHANGE `quantity` `quantity` int(6) NOT NULL DEFAULT 0.000 COMMENT 'Количество'");
		@$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` CHANGE `weight` `weight` int(6) NOT NULL DEFAULT 0.000 COMMENT 'Вес'");

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		@$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_option_value` CHANGE `quantity` `quantity` decimal(15,3) NOT NULL DEFAULT 0 COMMENT 'Количество'");

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` CHANGE `quantity` `quantity` int(4) NOT NULL DEFAULT 0 COMMENT 'Количество'");

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_option_value` CHANGE `quantity` `quantity` int(4) NOT NULL DEFAULT 0 COMMENT 'Количество'");

		// Восстанавливаем таблицу product_attribute
		$result = @$this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_attribute` WHERE `field` = 'attribute_value_id'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_attribute` DROP `attribute_value_id`");
		}

		$result = @$this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "product_attribute` WHERE `key_name` = 'value_id'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_attribute` DROP INDEX `value_id`");
		}

		// Восстанавливаем таблицу product_discount
		$result = @$this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_discount` WHERE `field` = 'product_feature_id'");
		if ($result->num_rows) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_discount` WHERE `product_feature_id` <> 0");
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_discount` DROP `product_feature_id`");
		}

		// Восстанавливаем таблицу product_special
		$result = @$this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_special` WHERE `field` = 'product_feature_id'");
		if ($result->num_rows) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_special` WHERE `product_feature_id` <> 0");
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_special` DROP `product_feature_id`");
		}

		// Удалим добавленный нами индекс
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "option_description` WHERE `key_name` = 'name_key'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "option_description` DROP INDEX `name_key`");
		}

		// Удалим добавленный нами индекс
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "option_value_description` WHERE `key_name` = 'name_key'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "option_value_description` DROP INDEX `name_key`");
		}

		// Удалим добавленный нами индекс
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "attribute_description` WHERE `key_name` = 'name_key'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "attribute_description` DROP INDEX `name_key`");
		}

		// Удалим базовую единицу товара
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` WHERE `field` = 'unit_id'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product` DROP `unit_id`");
		}

		$this->log->write("Отключен модуль " . $this->module_name);
		$this->log->write("Удалены таблицы: product_quantity, category_to_1c, attribute_to_1c, manufacturer_to_1c, store_to_1c, product_quantity, product_price, product_to_1c, product_unit, unit, unit_group, unit_type, warehouse.");
		$this->log->write("Восстановлены изменения таблиц product, product_option_value, cart");

	} // uninstall()


	/**
	 * ver 2
	 * update 2017-06-04
	 * Проверка доступа с IP адреса
	 */
	private function checkAccess($echo = false) {

		// Проверяем включен или нет модуль
		if (!$this->config->get('exchange1c_module_status')) {
			if ($echo) $this->echo_message(0, "The module is disabled");
			return false;
		}
		// Разрешен ли IP
		$config_allow_ips = $this->config->get('exchange1c_allow_ip');

		if ($config_allow_ips != '') {
			$ip = $_SERVER['REMOTE_ADDR'];
			$allow_ips = explode("\r\n", $config_allow_ips);
			foreach ($allow_ips as $allow_ip) {
				$length = strlen($allow_ip);
				if (substr($ip,0,$length) == $allow_ip) {
					return true;
				}
			}

		} else {
			return true;
		}
		if ($echo) $this->echo_message(0, "From Your IP address are not allowed");
		return false;

	} // checkAccess()


	/**
	 * Алгортим описан https://dev.1c-bitrix.ru/api_help/sale/algorithms/data_2_site.php
	 * Авторизация на сайте
	 */
	public function modeCheckauth() {

		if (!$this->checkAccess(true))
			exit;
		// Авторизуем
		if (($this->config->get('exchange1c_username') != '') && (@$_SERVER['PHP_AUTH_USER'] != $this->config->get('exchange1c_username'))) {
			$this->echo_message(0, "Incorrect login");
		}
		if (($this->config->get('exchange1c_password') != '') && (@$_SERVER['PHP_AUTH_PW'] != $this->config->get('exchange1c_password'))) {
			$this->echo_message(0, "Incorrect password");
			exit;
		}
		echo "success\n";
		echo "key\n";
		echo md5($this->config->get('exchange1c_password')) . "\n";

	} // modeCheckauth()


	/**
	 * ver 1
	 * update 2017-06-26
	 * Ручное обновление
	 */
	public function manualUpdate($ver) {

		$this->load->language('extension/module/exchange1c');
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'extension/module/exchange1c'))  {
			$this->load->model('extension/exchange1c');
			$this->log($ver, 2);
			$result = "Пока не реализовано";
			if (!$result) {
				$json['error'] = "Ошибка запуска обновления";
			} else {
				$json['success'] = "Обновление успешно выполнено: \n" . $result;
			}
		} else {
			$json['error'] = $this->language->get('error_permission');
		}
		$this->response->setOutput(json_encode($json));

	} // manualUpdate()


	/**
	 * ver 2
	 * update 2017-05-02
	 * Очистка базы данных через админ-панель
	 */
	public function manualCleaning() {

		$this->load->language('extension/module/exchange1c');
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'extension/module/exchange1c'))  {
			$this->load->model('extension/exchange1c');
			$result = $this->model_extension_exchange1c->cleanDB();
			if (!$result) {
				$json['error'] = "Таблицы не были очищены";
			} else {
				$json['success'] = "Успешно очищены таблицы: \n" . $result;
			}
		} else {
			$json['error'] = $this->language->get('error_permission');
		}
		$this->response->setOutput(json_encode($json));

	} // manualCleaning()


	/**
	 * ver 2
	 * update 2017-05-02
	 * Очистка связей с 1С через админ-панель
	 */
	public function manualCleaningLinks() {

		$this->load->language('extension/module/exchange1c');
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'extension/module/exchange1c'))  {
			$this->load->model('extension/exchange1c');
			$result = $this->model_extension_exchange1c->cleanLinks();
			if (!$result) {
				$json['error'] = "Таблицы не были очищены";
			} else {
				$json['success'] = "Успешно очищены таблицы: \n" . $result;
			}
		} else {
			$json['error'] = $this->language->get('error_permission');
		}
		$this->response->setOutput(json_encode($json));

	} // manualCleaningLinks()


	/**
	 * ver 2
	 * update 2017-05-02
	 * Очистка старых ненужных картинок через админ-панель
	 */
	public function manualCleaningOldImages() {

		$this->load->language('extension/module/exchange1c');
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'extension/module/exchange1c'))  {
			$this->load->model('extension/exchange1c');
			$result = $this->model_extension_exchange1c->cleanOldImages("import_files/");
			if ($result['error']) {
				$json['error'] = $result['error'];
			} else {
				$json['success'] = "Успешно удалено файлов: " . $result['num'];
			}
		} else {
			$json['error'] = $this->language->get('error_permission');
		}
		$this->response->setOutput(json_encode($json));

	} // manualCleaningLinks()


	/**
	 * ver 2
	 * update 2017-05-02
	 * Очистка кэша: системного, картинок
	 */
	public function manualCleaningCache() {

		$this->load->language('extension/module/exchange1c');
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'extension/module/exchange1c'))  {
			$this->load->model('extension/exchange1c');

			$result = $this->cleanCache();

			if (!$result) {
				$json['error'] = "Ошибка очистки кэша";
			} else {
				$json['success'] = "Кэш успешно очищен: \n" . $result;
			}
		} else {
			$json['error'] = $this->language->get('error_permission');
		}

		$this->response->setOutput(json_encode($json));

	} // manualCleaningCache()


	/**
	 * ver 2
	 * update 2017-05-02
	 * Генерация SEO на все товары
	 */
	public function manualGenerateSeo() {

		$this->load->language('extension/module/exchange1c');
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'extension/module/exchange1c'))  {
			$this->load->model('extension/exchange1c');
			$result = $this->model_extension_exchange1c->seoGenerate();
 			if ($result['error']) {
				$json['error'] = "Ошибка формирования SEO\n" . $result['error'];
			} else {
				$json['success'] = "SEO успешно сформирован, обработано:\nТоваров: " . $result['product'] . "\nКатегорий: " . $result['category'] . "\nПроизводителей: " . $result['manufacturer'];
			}
		} else {
			$json['error'] = $this->language->get('error_permission');;
		}
		$this->response->setOutput(json_encode($json));

	} // manualGenerateSeo()


	/**
	 * ver 2
	 * update 2017-06-13
	 * Удаляет дубули ссылок связей с торговой системой в таблицах *_to_1c
	 */
	public function manualRemoveDoublesLinks() {

		$this->load->language('extension/module/exchange1c');
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'extension/module/exchange1c'))  {
			$this->load->model('extension/exchange1c');
			$result = $this->model_extension_exchange1c->removeDoublesLinks();
 			if ($result['error']) {
				$json['error'] = "Ошибка удаления ссылок\n" . $result['error'];
			} else {
				$json['success'] = "Ссылки успешно удалены, обработано:".
				"\nАтрибутов: " . $result['attribute'] .
				"\nКатегорий: " . $result['category'] .
				"\nПроизводителей: " . $result['manufacturer'] .
				"\nТоваров: " . $result['product'] .
				"\nМагазинов: " . $result['store'];
			}
		} else {
			$json['error'] = $this->language->get('error_permission');
		}
		$this->response->setOutput(json_encode($json));

	} // manualRemoveDoublesLinks()


	/**
	 * ver 1
	 * update 2017-12-11
	 * Удаляет товары у которых были загружены из УС
	 */
	public function manualDeleteImportData() {

		$this->load->language('extension/module/exchange1c');
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'extension/module/exchange1c'))  {
			$this->load->model('extension/exchange1c');
			$result = $this->model_extension_exchange1c->deleteImportData();
 			if ($result['error']) {
				$json['error'] = "ERROR: 1001";
			} else {
				$json['success'] = "Успешно удалено:".
				"\nАтрибутов: " . $result['attribute'] .
				"\nКатегорий: " . $result['category'] .
				"\nПроизводителей: " . $result['manufacturer'] .
				"\nТоваров: " . $result['product'];
			}
		} else {
			$json['error'] = $this->language->get('error_permission');
		}
		$this->response->setOutput(json_encode($json));

	} // manualDeleteImportProducts()


	/**
	 * Проверка существования каталогов
	 */
	private function checkDirectories($name) {

		$path = DIR_IMAGE;
		$dir = explode("/", $name);
		for ($i = 0; $i < count($dir)-1; $i++) {
			$path .= $dir[$i] . "/";
			if (!is_dir($path)) {
				$error = "";
				@mkdir($path, 0775) or die ($error = "Ошибка создания директории '" . $path . "'");
				if ($error)
					return $error;
				$this->log("Создана директория: " . $path, 2);
			}
		}
		return "";
	}  // checkDirectories()


	/**
	 * ver 2
	 * update 2017-06-03
	 * Распаковываем картинки
	 */
	private function extractImage($zipArc, $zip_entry, $name) {

		$error = "";

		// Если стоит режим не загружать, картинки не распаковываем
		if ($this->config->get('exchange1c_product_images_import_mode') == 'disable') {
			return "";
		}

		$this->log("Распаковка картинки = " . $name, 2);

		if (substr($name, -1) == "/") {

			// проверим каталог
			if (is_dir(DIR_IMAGE.$name)) {
				//$this->log('[zip] directory exist: '.$name, 2);

			} else {
				//$this->log('[zip] create directory: '.$name, 2);
				@mkdir(DIR_IMAGE.$name, 0775) or die ($error = "Ошибка создания директории '" . DIR_IMAGE.$name . "'");
				if ($error) return $error;
			}

		} elseif (zip_entry_open($zipArc, $zip_entry, "r")) {

			$error = $this->checkDirectories($name);
			if ($error) return $error;

			if (is_file(DIR_IMAGE.$name)) {
				//$this->log('[zip] file exist: '.$name, 2);
			} else {
				$dump = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

				// для безопасности проверим, не является ли этот файл php
				$pos = strpos($dump, "<?php");

				if ($pos !== false) {
					$this->log("[!] ВНИМАНИЕ Файл '" . $name . "' является PHP скриптом и не будет записан!");

				} else {

					if (file_exists(DIR_IMAGE . $name) && $this->config->get('exchange1c_product_images_import_mode') != 'full') {
						return "";
					}
					$fd = @fopen(DIR_IMAGE . $name, "w+");

					if ($fd === false) {
						return "Ошибка создания файла: " . DIR_IMAGE.$name . ", проверьте права доступа!";
					}

					//$this->log('[zip] create file: '.$name, 2);
					fwrite($fd, $dump);
					fclose($fd);

					// для безопасности проверим, является ли этот файл картинкой
//					$image_info = getimagesize(DIR_IMAGE.$name);
//					if ($image_info == NULL) {
//						$this->log("[!] ВНИМАНИЕ Файл '" . $name . "' не является картинкой, и будет удален!");
//						unlink(DIR_IMAGE.$name);
//					}
				}
			}
			zip_entry_close($zip_entry);
		}

		//$this->log("Завершена распаковка картинки", 2);
		return $error;

	} // extractImage()


	/**
	 * ver 3
	 * update 2017-11-04
	 * Распаковываем XML
	 */
	private function extractXML($zipArc, $zip_entry, $name, &$xmlFiles) {

		$error = "";
		$this->log("Распаковка XML,  name = " . $name, 2);
		$cache = DIR_CACHE . 'exchange1c/';

		if (substr($name, -1) == "/") {
			// это директория
			if (is_dir($cache . $name)) {
				$this->log("Каталог существует: " . $name, 2);
			} else {
				$this->log("Создание каталога: " . $name, 2);
				@mkdir($cache . $name, 0775) or die ($error = "Ошибка создания директории '" . $cache . $name . "'");
				if ($error) return $error;
			}

		} elseif (zip_entry_open($zipArc, $zip_entry, "r")) {

			$this->log($cache . $name, 2);
			$dump = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

			// Удалим существующий файл
			if (file_exists($cache . $name)) {
				unlink($cache . $name);
				$this->log("Удален старый файл: " . $cache . $name, 2);
			}

			// для безопасности проверим, является ли этот файл XML
			$str_xml = mb_substr($dump, 1, 5);

			$path_obj = explode('/', $name);
			$filename = array_pop($path_obj);
			$path = "";

			// Если есть каталоги, нужно их создать
			foreach ($path_obj as $dir_name) {
				if (!is_dir($cache . $path . $dir_name)) {
					@mkdir($cache . $path . $dir_name, 0775) or die ($error = "Ошибка создания директории '" . $cache . $path . $dir_name . "'");
					$this->log("Создание папки: " . $dir_name, 2);
					$this->log("Путь к файлу в кэше: " . $path, 2);
				} else {
					$this->log("Папка уже существует: " . $path . $dir_name, 2);
				}
				$path .= $dir_name . "/";
			}

			if ($str_xml != "<?xml") {

				$this->log("[!] ВНИМАНИЕ Файл '" . $filename . "' не является XML файлом и не будет записан!");

			} elseif ($fd = @fopen($cache . $path . $filename, "w+")) {

				$xmlFiles[] = $path . $filename;
				$this->log("Создан файл: " . $path . $filename, 2);
				fwrite($fd, $dump);
				fclose($fd);
				$this->log($xmlFiles, 2);

			} else {

				$this->log("Ошибка создания файла и открытие на запись: " . $path . $filename);

			}

			zip_entry_close($zip_entry);
		}

		$this->log("Завершена распаковка XML", 2);
		return "";

	} // extractXML()


	/**
	 * ver 3
	 * update 2017-06-03
	 * Распаковываем ZIP архив
	 * Папка с картинками распаковывается в папку что указана в архиве
	 */
	private function extractZip($zipFile, &$error) {

		$xmlFiles = array();
		$cache = DIR_CACHE . 'exchange1c/';

		$zipArc = zip_open($zipFile);
		if (is_resource($zipArc)) {
			$this->log("extractZip(): Распаковка архива: " . $zipFile, 2);

			while ($zip_entry = zip_read($zipArc)) {
				$name = zip_entry_name($zip_entry);
				$pos = stripos($name, 'import_files/');

				if ($pos !== false) {
					$error = $this->extractImage($zipArc, $zip_entry, substr($name, $pos));
					if ($error) return $xmlFiles;

				} else {
					$error = $this->extractXML($zipArc, $zip_entry, $name, $xmlFiles);
					$this->log($xmlFiles, 2);
					if ($error) return $xmlFiles;
				}
			}

		} else {
			return $xmlFiles;
		}

		zip_close($zipArc);
		$this->log("extractZip(): Завершена распаковка архива", 2);
		return $xmlFiles;

	} // extractZip()


	/**
	 * Определяет тип файла по наименованию
	 */
	public function detectFileType($fileName) {

		$types = array('import', 'offers', 'prices', 'rests');
		foreach ($types as $type) {
			$pos = stripos($fileName, $type);
			if ($pos !== false)
				return $type;
		}
		return '';

	} // detectFileType()


	/**
	 * Создание и скачивание заказов
	 */
	public function downloadOrders() {

		$this->load->model('extension/exchange1c');
		$orders = $this->model_extension_exchange1c->queryOrders(
			array(
				 'from_date' 		=> $this->config->get('exchange1c_order_date')
				,'new_status'		=> $this->config->get('exchange1c_order_status')
				,'notify'			=> $this->config->get('exchange1c_order_notify')
				,'currency'			=> $this->config->get('exchange1c_order_currency') ? $this->config->get('exchange1c_order_currency') : 'руб.'
			)
		);
		$this->response->addheader('Pragma: public');
		$this->response->addheader('Connection: Keep-Alive');
		$this->response->addheader('Expires: 0');
		$this->response->addheader('Content-Description: File Transfer');
		$this->response->addheader('Content-Type: application/octet-stream');
		$this->response->addheader('Content-Disposition: attachment; filename="orders.xml"');
		$this->response->addheader('Content-Transfer-Encoding: binary');
		$this->response->addheader('Content-Length: ' . strlen($orders));
		//$this->response->addheader('Content-Type: text/html; charset=windows-1251');

		//$this->response->setOutput(file_get_contents(DIR_CACHE . 'exchange1c/orders.xml', FILE_USE_INCLUDE_PATH, null));
        $this->response->setOutput($orders);

	} // downloadOrders()


	/**
	 * ver 1
	 * update 2017-10-16
	 */
	public function modeCatalogInit() {

		if (!$this->checkAuthKey()) {
//			echo "failure";
			exit;
		}
		$this->cleanCache();
		$result = $this->modeInit();
		echo $result[0] . "\n";
		echo $result[1] . "\n";
		$this->log($result, 2);
		//echo "sessid=" . md5($this->config->get('exchange1c_password')) . "\n";

	} // modeCatalogInit()


	/**
	 * ver 2
	 * update 2017-07-29
	 * Обрабатывает команду инициализации
	 * При успешной инициализации возвращает временный файл с данными:
	 * в 1-ой строке содержится признак, разрешен ли Zip (zip=yes);
	 * во 2-ой строке содержится информация об ограничении файлов по размеру (file_limit=);
	 * в 3-ейй строке содержится ключ сессии обмена (sessid=);
	 * в 4-ой строке содержится версия CommerceML (version=).
	 */
	public function modeInit() {

		$result = array();

		$result[0] = $this->config->get('exchange1c_file_zip') == 1 ?  "zip=yes" : "zip=no";
		$result[1] = "file_limit=" . $this->getPostMaxFileSize();
		$this->log(PHP_VERSION_ID, 2);

		// Очистка лога при начале обмена
		//if ($this->config->get('exchange1c_flush_log')) {
		//	$this->clearLog();
		//}

		return $result;

	} // modeInit()


	/**
	 * ver 3
	 * update 2017-07-29
	 * Импорт файла через админ-панель
	 */
	private function manualImportFile() {

		$error = "";
		$cache = DIR_CACHE . 'exchange1c/';

		if ($this->config->get('exchange1c_flush_log') == 1) {
			$this->clearLog();
		}

		$uploaded_file = $this->request->files['file']['tmp_name'];
		$filename_decode = html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8');

		// Перемещаем файл из временной папки PHP в папку кэша модуля
		rename($uploaded_file, $cache . $filename_decode);
		$import_file = $cache . $filename_decode;
		$this->log($filename_decode, 2);
		$this->log($this->request->files['file'], 2);

		if (is_file($import_file)) {

			$path_info = pathinfo($filename_decode);
			$this->log($path_info, 2);
			$filename = $path_info['filename'];
			$extension = $path_info['extension'];

			$result = $this->modeInit();

			if (count($result) < 2) {
				$this->error['warning'] = "Ошибка инициализации перед загрузкой файла: " . $filename;
				return false;
			}

			if ($extension == 'zip') {

				$xmlFiles = $this->extractZip($import_file, $error);
				if ($error) return $error;

				$this->log($xmlFiles, 2);

				if (!count($xmlFiles)) {
					$this->error['warning'] = "Архив пустой или запароленный";
					return false;
				}

				$goods = array();
				$properties = array();
				foreach ($xmlFiles as $key => $file) {
					$pos = strripos($file, "/goods/");
					if ($pos !== false) {
						$goods[] = $file;
						unset($xmlFiles[$key]);
					}
					$pos = strripos($file, "/properties/");
					if ($pos !== false) {
					$properties[] = $file;
						unset($xmlFiles[$key]);
					}
				}

				// Порядок обработки файлов
				sort($goods);
				sort($properties);
				sort($xmlFiles);

				foreach ($xmlFiles as $file) {
					$this->log('Обрабатывается файл основной: ' . $file, 2);
					$error = $this->modeImport($cache . $file);
					if ($error) return $error;
				}
				foreach ($properties as $file) {
					$this->log('Обрабатывается файл свойств: ' . $file, 2);
					$error = $this->modeImport($cache . $file);
					if ($error) return $error;
				}
				foreach ($goods as $file) {
					$this->log('Обрабатывается файл товаров: ' . $file, 2);
					$error = $this->modeImport($cache . $file);
					if ($error) return $error;
				}

			} elseif ($extension == 'xml') {
				// Это не архив
				$error = $this->modeImport($import_file);
				if ($error) return $error;

			} else {
				return "Файл не является архивом ZIP или XML файлом";
			}

		} // if (!empty($this->request->files['file']['name']) && is_file($import_file))

		return "";

	} // manualImportFile()


	/**
	 * ver 6
	 * update 2017-07-28
	 * Импорт файла через админ-панель
	 * ПРОБЛЕМА: не прерывается по ошибке чтения файлов, но в лог пишет ошибку
	 */
	public function manualImport() {

		$this->load->language('extension/module/exchange1c');
		$cache = DIR_CACHE . 'exchange1c/';
		$json = array();
		$error = "";

		// Разрешен ли IP
		if ($this->checkAccess()) {
			$error = $this->manualImportFile();
		}

		if ($error) {
			//$json['error'] = $this->language->get('text_upload_error');
			$json['error'] = $error;
			$this->log( "[!] Ручной обмен прошел с ошибками", 2);

		} else {
			$json['success'] = $this->language->get('text_upload_success');
			$this->log( "[i] Ручной обмен прошел без ошибок", 2);

		}

		$this->cache->delete('product');
		$this->response->setOutput(json_encode($json));

	} // manualImport()


	/**
	 * ver 1
	 * update 2017-12-05
	 * Очищает статистику
	 */
	public function clearStat() {

		$this->load->language('extension/module/exchange1c');
		$error = "";

		// Разрешен ли IP
		if ($this->checkAccess()) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('exchange1c-stat', array());
		}

		$msg = "Cleared statistics successfully ";
		$this->log( "[i] Очищена статистика (exchange1c-stat)", 2);

		$this->response->setOutput($msg);

	} // manualImport()


	/**
	 * Проверяет наличие куки ключа
	 */
	private function checkAuthKey($echo=true) {

		if (!isset($this->request->cookie['key'])) {
			if ($echo) $this->echo_message(0, "no cookie key\n");
			return false;
		}
		if ($this->request->cookie['key'] != md5($this->config->get('exchange1c_password'))) {
			if ($echo) $this->echo_message(0, "Session error\n");
			return false;
		}
		return true;
	}


	/**
	 * ver 2
	 * update 2017-22-04
	 * Переводит значение из килобайт, мегабат и гигабайт в байты
	 */
	private function formatSize($size) {
		if (empty($size)) {
			return 0;
		}
		$type = $size{strlen($size)-1};
		if (!is_numeric($type)) {
			$size = (integer)$size;
			switch ($type) {
				case 'K': $size = $size*1024;
					break;
				case 'M': $size = $size*1024*1024;
					break;
				case 'G': $size = $size*1024*1024*1024;
					break;
			}
			return $size;
		}
		return (int)$size;
	} // formatSize()


	/**
	 * ver 2
	 * update 2017-10-29
	 * Возвращает максимальный объем файла в байта для загрузки
	 */
	private function getPostMaxFileSize() {

		$size = $this->formatSize(ini_get('post_max_size'));
		$this->log("POST_MAX_SIZE: " . $size, 2);

		$size_max_manual = $this->formatSize($this->config->get('exchange1c_file_max_size'));
		if ($size_max_manual) {
			$this->log("POST_MAX_SIZE (переопределен в настройках): " . $size_max_manual, 2);
			if ($size_max_manual < $size) {
				$size = $size_max_manual;
			}
		}

		return $size;
	}


	/**
	 * Очистка лога
	 */
	private function clearLog() {
		$file = DIR_LOGS . $this->config->get('config_error_filename');
		$handle = fopen($file, 'w+');
		fclose($handle);
	}


	/**
	 * ver 1
	 * update 2017-06-02
	 * Обрабатывает загруженный файл на сервер
	 */
	private function modeFile($mode, &$error) {

    	$this->log('modeFile', 2);

        $xmlfiles = array();

		if (!$this->checkAuthKey()) exit;
		$cache = DIR_CACHE . 'exchange1c/';

		// Проверяем на наличие каталога
		if(!is_dir($cache)) mkdir($cache);

		// Проверяем на наличие имени файла
		if (isset($this->request->get['filename'])) {
			$uplod_file = $cache . $this->request->get['filename'];
		}
		else {
			$error = "modeFile(): No file name variable";
			return false;
		}

		// Проверяем XML или изображения
		if (strpos($this->request->get['filename'], 'import_files') !== false) {
			$cache = DIR_IMAGE;
			$uplod_file = $cache . $this->request->get['filename'];
			$this->checkUploadFileTree(dirname($this->request->get['filename']) , $cache);
		}

		// Проверка на запись файлов в кэш
		if (!is_writable($cache)) {
			$error = "modeFile(): The folder " . $cache . " is not writable!";
			return false;
		}

		$this->log("upload file: " . $uplod_file,2);

		// Получаем данные
		$data = file_get_contents("php://input");
		if ($data !== false) {

			// Записываем в файл
			$filesize = file_put_contents($uplod_file, $data, FILE_APPEND | LOCK_EX);
			$this->log("file size: " . $filesize, 2);

			if ($filesize) {
				chmod($uplod_file , 0664);

				$xmlfiles = $this->extractZip($uplod_file, $error);
				if ($error) {
					$this->echo_message(0, "modeFile(): Error extract file: " . $uplod_file);
					return false;
				};
				if (count($xmlfiles) && $this->config->get('exchange1c_upload_files_unlink')) {
					// Это архив, удаляем архив
					unlink($uplod_file);
				}
			} else {
				$this->echo_message(0, "modeFile(): Error create file");
			}
		}
		else {
			$this->echo_message(0, "modeFile(): Data empty");
		}

		return $xmlfiles;

	} // modeFile()


	/**
	 * ver 5
	 * update 2017-06-01
	 * Обрабатывает загруженный файл на сервер
	 */
	public function modeFileCatalog() {

    	$this->log('modeFileCatalog', 2);
        $error = '';

		$this->modeFile('catalog', $error);

		if ($error) {
			$this->echo_message(0, $error);
		} else {
			$this->echo_message(1, "Successfully import catalog ");
		}

	} // modeFileCatalog()


	/**
	 * ver 4
	 * update 2017-06-05
	 * Обрабатывает загруженный файл заказов на сервер
	 */
	public function modeFileSale() {

    	$this->log('modeFileSale', 2);

		if ($this->config->get('exchange1c_orders_import') != 1) {
			$this->log("modeFileSale(): Загрузка заказов отключена");
			exit;
		}

		$cache = DIR_CACHE . 'exchange1c/';
		$error = '';

		// Загружаем файл
		$xmlfiles = $this->modeFile('sale', $error);

		// Если во время обработки файла произошла ошибка, то загрузка данных из файлов
		if ($error) {
			$this->echo_message(0, $error);
			exit;
		}

		if (!$xmlfiles) {
			$this->echo_message(0, 'modeFileSale(): no XML files');
			exit;
		}

		$this->log($xmlfiles, 2);

		$this->load->model('extension/exchange1c');

		foreach ($xmlfiles as $xmlfile) {

			$importFile = $cache . $xmlfile;

			// Загружаем файл
			$error = $this->model_extension_exchange1c->importFile($importFile, $this->detectFileType($importFile));
			if ($error) {
				$this->echo_message(0, $error);
				$this->log("modeFileSale(): Ошибка обработки файла: " . $importFile);
				return false;
			}

			// Удалим файл
			//$this->log("[i] Удаление файла: " . $importFile,2);
			//unlink($importFile);

		}

		$this->echo_message(1, "modeFileSale(): Successfully processed orders");
		//$this->cache->delete('order');

	} // modeFileSale()


	/**
	 * ver 5
	 * update 2017-10-30
	 * Обрабатывает *.XML файлы
	 *
	 * @param	boolean		true - ручной импорт
	 */
	public function modeImport($manual = false) {

    	$this->log('modeImport', 2);

		if ($manual) $this->log("modeImport(): Ручная загрузка данных.");

		$cache = DIR_CACHE . 'exchange1c/';
		if(!is_dir($cache)) mkdir($cache);

		// Определим имя файла
		if ($manual)

			$importFile = $manual;

		elseif (isset($this->request->get['filename']))

			$importFile = $cache . $this->request->get['filename'];

		else {

			if (!$manual) $this->echo_message(0, "modeImport(): No import file name");

			// Удалим файл
			$this->log("[i] Удаление файла: " . $importFile,2);
			unlink($importFile);

			return "modeImport(): No import file name";

		}

		// Определяем текущую локаль
		$this->load->model('extension/exchange1c');

		// Загружаем файл
		$error = $this->model_extension_exchange1c->importFile($importFile, $this->detectFileType($importFile));
		if ($error) {

			if (!$manual) {
				$this->echo_message(0, 'modeImport(): ' . $error);
				//$this->echo_message(0, "Error processing file " . $importFile);
			}

			$this->log("modeImport(): Ошибка при загрузки файла: " . $importFile);

			// Удалим файл
			if ($this->config->get('exchange1c_upload_files_unlink')) {
				$this->log("[i] Удаление файла: " . $importFile,2);
				unlink($importFile);
			}

			return $error;

		} else {
			if (!$manual) {
				$this->echo_message(1, "modeImport(): Successfully processed file " . $importFile);
			}
		}

		// Удалим файл
		if ($this->config->get('exchange1c_upload_files_unlink')) {
			$this->log("[i] Удаление файла: " . $importFile,2);
			unlink($importFile);
		}

		$this->cache->delete('product');
		return "";

	} // modeImport()


	/**
	 * ver 3
	 * update 2017-06-21
	 * Режим запроса заказов
	 */
	public function modeQueryOrders() {

		if (!$this->checkAuthKey(true)) exit;

		$this->load->model('extension/exchange1c');

		$orders = $this->model_extension_exchange1c->queryOrders(
			array(
				 'from_date' 		=> $this->config->get('exchange1c_order_date')
				,'new_status'		=> $this->config->get('exchange1c_order_status')
				,'notify'			=> $this->config->get('exchange1c_order_notify')
				,'currency'			=> $this->config->get('exchange1c_order_currency') ? $this->config->get('exchange1c_order_currency') : 'руб.'
			)
		);
		if ($this->config->get('exchange1c_convert_orders_cp1251') == 1) {
			//echo header('Content-Type: text/html; charset=windows-1251', true);
			// посоветовал yuriygr с GitHub
			//echo iconv('utf-8', 'cp1251', $orders);
			echo iconv('utf-8', 'cp1251//TRANSLIT', $orders);
			//echo mb_convert_encoding($orders, 'cp1251//TRANSLIT', 'utf-8');
		} else {
			echo $orders;
		}

	} // modeQueryOrders()


	/**
	 * ver 3
	 * update 2017-06-01
	 * Изменение статусов заказов с момента последней выгрузки и после подтверждения получения торговой системы
	 */
	public function modeOrdersChangeStatus(){
		if (!$this->checkAuthKey(true)) exit;
		$this->load->model('extension/exchange1c');

		$result = $this->model_extension_exchange1c->queryOrdersStatus();

		if($result){

			$this->load->model('setting/setting');
			$config = $this->model_setting_setting->getSetting('exchange1c');
			$config['exchange1c_order_date'] = date('Y-m-d H:i:s');
			$this->model_setting_setting->editSetting('exchange1c', $config);
			$config['exchange1c_order_date'] = $this->config->get('exchange1c_order_date');
		}

		$this->echo_message(1,$result);

	} // modeOrdersChangeStatus()


	// -- Системные процедуры
	/**
	 * ver 2
	 * update 2017-11-25
	 * Очистка папки cache
	 */
	private function cleanCache() {
		// Проверяем есть ли директория
		$result = "";
		if (file_exists(DIR_CACHE . 'exchange1c')) {
			if (is_dir(DIR_CACHE . 'exchange1c')) {
				$this->cleanDir(DIR_CACHE . 'exchange1c/');
			}
			else {
				unlink(DIR_CACHE . 'exchange1c');
			}
		}
		@mkdir (DIR_CACHE . 'exchange1c');
		$result .= "Очищен кэш модуля: /system/storage/cache/exchange1c/*\n";

		// очистка системного кэша
		$files = glob(DIR_CACHE . 'cache.*');
		foreach ($files as $file) {
			$this->cleanDir($file);
		}
        $result .= "Очищен системный кэш: /system/storage/cache/cache*\n";

		// очистка кэша картинок
		$imgfiles = glob(DIR_IMAGE . 'cache/*');
		foreach ($imgfiles as $imgfile) {
			$this->cleanDir($imgfile);
			$this->log("Удаление картинки: " . $imgfile ,2);
		}
		$result .= "Очищен кэш картинок: /image/cache/*\n";

		return $result;

	} // cleanCache()


	/**
	 * Проверка дерева каталога для загрузки файлов
	 */
	private function checkUploadFileTree($path, $curDir = null) {
		if (!$curDir) $curDir = DIR_CACHE . 'exchange1c/';
		foreach (explode('/', $path) as $name) {
			if (!$name) continue;
			if (file_exists($curDir . $name)) {
				if (is_dir( $curDir . $name)) {
					$curDir = $curDir . $name . '/';
					continue;
				}
				unlink ($curDir . $name);
			}
			mkdir ($curDir . $name );
			$curDir = $curDir . $name . '/';
		}
	} // checkUploadFileTree()


	/**
	 * Очистка папки рекурсивно
	 */
	private function cleanDir($root, $self = false) {
		if (is_file($root)) {
			unlink($root);
		} else {
			if (substr($root, -1)!= '/') {
				$root .= '/';
			}
			$dir = dir($root);
			while ($file = $dir->read()) {
				if ($file == '.' || $file == '..') continue;
				if ($file == 'index.html') continue;
				if (file_exists($root . $file)) {
					if (is_file($root . $file)) { unlink($root . $file); continue; }
					if (is_dir($root . $file)) { $this->cleanDir($root . $file . '/', true); continue; }
					//var_dump ($file);
				}
				//var_dump($file);
			}
		}
		if ($self) {
			if(file_exists($root) && is_dir($root)) {
				rmdir($root); return 0;
			}
			//var_dump($root);
		}
		return 0;
	} // cleanDir()


	/**
	 * События
	 */
	public function eventDeleteProduct($route, $products) {
		$this->log("route = " . $route);
		foreach ($products as $product_id) {
			$this->log("Удаление связи с товаром product_id = " . $product_id);
			$this->load->model('extension/exchange1c');
			$this->model_extension_exchange1c->deleteLinkProduct($product_id);
		}
	} // eventProductDelete()


	/**
	 * События
	 */
	public function eventDeleteCategory($route, $categories) {
		$this->log("route = " . $route);
		foreach ($categories as $category_id) {
			$this->log("Удаление связи с категорией category_id = " . $category_id);
			$this->load->model('extension/exchange1c');
			$this->model_extension_exchange1c->deleteLinkCategory($category_id);
		}
	} // eventCategoryDelete()


	/**
	 * События
	 */
	public function eventDeleteManufacturer($route, $manufacturers) {
		$this->log("route = " . $route);
		foreach ($manufacturers as $manufacturer_id) {
			$this->log("Удаление связи с производителем manufacturer_id = " . $manufacturer_id);
			$this->log("route = " . $route);
			$this->load->model('extension/exchange1c');
			$this->model_extension_exchange1c->deleteLinkManufacturer($manufacturer_id);
		}
	} // eventManufacturerDelete()


	/**
	 * События
	 */
	public function eventDeleteAttribute($route, $attributes) {
		$this->log("route = " . $route);
		foreach ($attributes as $attribute_id) {
			$this->log("Удаление связи с атрибутом attribute_id = " . $attribute_id);
			$this->log("route = " . $route);
			$this->load->model('extension/exchange1c');
			$this->model_extension_exchange1c->deleteLinkAttribute($attribute_id);
		}
	} // eventDeleteAttribute()


	/**
	 * Удаляет категорию и все что в ней
	 */
    public function delete($path) {
		if (is_dir($path)) {
			array_map(function($value) {
				$this->delete($value);
				rmdir($value);
			},glob($path . '/*', GLOB_ONLYDIR));
			array_map('unlink', glob($path."/*"));
		}
	} // delete()


	/**
	* Формирует архив модуля для инсталляции
	*/
	public function modeExportModule() {

		if ($this->config->get('exchange1c_export_module_to_all') != 1) {
			if (!$this->checkAccess(true)) {
				echo "<br />\n";
				echo "Экспорт модуля " . $this->module_name . " для IP " . $_SERVER['REMOTE_ADDR'] . " в данный момент запрещен!";
				$this->log("Экспорт модуля " . $this->module_name . " для IP " . $_SERVER['REMOTE_ADDR'] . " запрещен!");
				return false;
			}
		}

		$this->log("Экспорт модуля " . $this->module_name . " для IP " . $_SERVER['REMOTE_ADDR']);
		// создаем папку export в кэше

		// Короткое название версии
		$cms_short_version = substr($this->config->get('exchange1c_CMS_version'),0,3);

		$filename = DIR_DOWNLOAD . 'oc' . $cms_short_version . '-exchange1c_' . $this->config->get('exchange1c_version') . '.ocmod.zip';
		if (is_file($filename))
			unlink($filename);

		// Пакуем в архив
		$zip = new ZipArchive;
		$zip->open($filename, ZIPARCHIVE::CREATE);
		$zip->addFile(DIR_APPLICATION . 'controller/extension/module/exchange1c.php', 'upload/admin/controller/extension/module/exchange1c.php');
		$zip->addFile(DIR_APPLICATION . 'language/en-gb/extension/module/exchange1c.php', 'upload/admin/language/en-gb/extension/module/exchange1c.php');
		$zip->addFile(DIR_APPLICATION . 'language/ru-ru/extension/module/exchange1c.php', 'upload/admin/language/ru-ru/extension/module/exchange1c.php');
		$zip->addFile(DIR_APPLICATION . 'model/extension/exchange1c.php', 'upload/admin/model/extension/exchange1c.php');
		if (is_file(DIR_APPLICATION . 'model/catalog/unit.php')) {
			$zip->addFile(DIR_APPLICATION . 'model/catalog/unit.php', 'upload/admin/model/catalog/unit.php');
		}
		if (is_file(DIR_APPLICATION . 'model/extension/unit.php')) {
			$zip->addFile(DIR_APPLICATION . 'model/extension/unit.php', 'upload/admin/model/extension/unit.php');
		}
		if (is_file(DIR_APPLICATION . 'model/extension/unit_full.php')) {
			$zip->addFile(DIR_APPLICATION . 'model/extension/unit_full.php', 'upload/admin/model/extension/unit_full.php');
		}
		$zip->addFile(DIR_APPLICATION . 'view/template/extension/module/exchange1c.tpl', 'upload/admin/view/template/extension/module/exchange1c.tpl');
		$zip->addFile(DIR_APPLICATION . '../export/exchange1c.php', 'upload/export/exchange1c.php');
		$zip->addFile(DIR_APPLICATION . '../bitrix/admin/1c_exchange.php', 'upload/bitrix/admin/1c_exchange.php');
		if (is_file(DIR_APPLICATION . '../catalog/model/catalog/unit.php')) {
			$zip->addFile(DIR_APPLICATION . '../catalog/model/catalog/unit.php', 'upload/catalog/model/catalog/unit.php');
		}

		if (is_file(DIR_APPLICATION . '../export/history.txt'))
			$zip->addFile(DIR_APPLICATION . '../export/history.txt', 'history.txt');
		if (is_file(DIR_APPLICATION . '../export/install.php'))
			$zip->addFile(DIR_APPLICATION . '../export/install.php', 'install.php');
		if (is_file(DIR_APPLICATION . '../export/README.md'))
			$zip->addFile(DIR_APPLICATION . '../export/README.md', 'README.md');

		$sql = "SELECT `xml`,`code` FROM " . DB_PREFIX . "modification WHERE code LIKE 'exchange1c%'";
		$query = $this->db->query($sql);
		$xml_files = array();
		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				if ($row['code'] == 'exchange1c') {
					$xml_name = 'install.xml';
				} else{
					$xml_name = $row['code'] . '.ocmod.xml';
				}
				if ($fp = @fopen(DIR_DOWNLOAD . $xml_name, "wb")) {
					$result = @fwrite($fp, $row['xml']);
					$this->log("Add to arhive file: " . $xml_name);
					$zip->addFile(DIR_DOWNLOAD . $xml_name, $xml_name);
					@fclose($fp);
					$xml_files[] = $xml_name;
				}
			}
		}

		$zip->close();
		foreach ($xml_files as $file) {
			@unlink(DIR_DOWNLOAD . $file);
		}

		if ($fp = fopen($filename, "rb")) {
			echo '<a href="' . HTTP_CATALOG . 'system/storage/download/' . substr($filename, strlen(DIR_DOWNLOAD)) . '">' . substr($filename, strlen(DIR_DOWNLOAD)) . '</a>';
		}

	} // modeExportModule()


	/**
	 * ver 2
	 * updare 2017-05-02
	* Эта функция самоуничтожения модуля! Будьте осторожны!
	* Данные в базе не изменяются и не восстанавливаются в предыдущее состояние
	*/
	public function modeRemoveModule() {

		// Эта строчка защищает от несанкционированного удаления, для удаления модуля, закомментарьте строчку ниже
		return false;

		// Разрешен ли IP
		if ($this->config->get('exchange1c_allow_ip') != '') {
			$ip = $_SERVER['REMOTE_ADDR'];
			$allow_ips = explode("\r\n", $this->config->get('exchange1c_allow_ip'));
			if (!in_array($ip, $allow_ips)) {
				echo("Ваш IP адрес " . $ip . " не найден в списке разрешенных");
				return false;
			}
		} else {
			echo("Список IP адресов пуст, задайте адрес");
			return false;
		}

		$this->log("Удаление модуля " . $this->module_name,1);
		// создаем папку export в кэше

		$this->uninstall();

		$files = array();
		$files[] = DIR_APPLICATION . 'controller/extension/module/exchange1c.php';
		$files[] = DIR_APPLICATION . 'language/en-gb/extension/module/exchange1c.php';
		$files[] = DIR_APPLICATION . 'language/ru-ru/extension/module/exchange1c.php';
		$files[] = DIR_APPLICATION . 'model/extension/unit.php';
		$files[] = DIR_APPLICATION . 'model/catalog/unit.php';
		$files[] = DIR_APPLICATION . '../catalog/model/catalog/unit.php';
		$files[] = DIR_APPLICATION . 'model/extension/unit_full.php';
		$files[] = DIR_APPLICATION . 'model/extension/exchange1c.php';
		$files[] = DIR_APPLICATION . 'view/template/extension/module/exchange1c.tpl';
		$files[] = substr(DIR_APPLICATION, 0, strlen(DIR_APPLICATION) - 6) . 'export/exchange1c.php';
		foreach ($files as $file) {
			if (is_file($file)) {
				unlink($file);
				$this->log("Удален файл " . $file,1);
			}
		}

		// Удаление модификатора
		$this->load->model('extension/modification');
		$modification = $this->model_extension_modification->getModificationByCode('exchange1c');
		if ($modification) $this->model_extension_modification->deleteModification($modification['modification_id']);

		echo "Модуль успешно удален!";

	} // modeRemoveModule()

}
?>
