<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@168297

require_once('include/nusoap/nusoap.php');

/**
 * Extends the standard soap server in order to support handlers in different path/classes
 */
class VTESOAPServer extends soap_server {

	/**
	 * The namespace for the SOAP webservices
	 */
	public $vte_namespace = 'https://www.vtenext.com/products/crm';

	protected $vte_ws = array();

	/**
	 * Handy function to register all WS
	 */
	public function registerAllFromDB() {
		// read db and register everything
		
		$SWSMan = SOAPWSManager::getInstance();
		
		$allws = $SWSMan->getAllWebservices();
		foreach ($allws as $ws) {
			$params = array();
			foreach ($ws['params'] as $p) {
				$params[$p['name']] = $p['param_type'];
			}
			$this->register($ws, $params, array('return' => $ws['return_type']));
		}
	}

	/**
	 * Register types used by VTE WS
	 */
	public function registerVTETypes() {
		$this->wsdl->addComplexType(
			'common_array',
			'complexType',
			'array',
			'',
			array(
				'fieldname' => array('name'=>'fieldname','type'=>'xsd:string'),
			)
		);

		$this->wsdl->addComplexType(
			'common_array1',
			'complexType',
			'array',
			'',
			'SOAP-ENC:Array',
			array(),
			array(
				array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:common_array[]')
			),
			'tns:common_array'
		);

		$this->wsdl->addComplexType(
			'add_contact_detail_array',
			'complexType',
			'array',
			'',
			array(
				'salutation' => array('name'=>'salutation','type'=>'xsd:string'),
				'firstname' => array('name'=>'firstname','type'=>'xsd:string'),
				'phone' => array('name'=>'phone','type'=>'xsd:string'),
				'lastname' => array('name'=>'lastname','type'=>'xsd:string'),
				'mobile' => array('name'=>'mobile','type'=>'xsd:string'),
				'accountid' => array('name'=>'accountid','type'=>'xsd:string'),
				'leadsource' => array('name'=>'leadsource','type'=>'xsd:string'),
			)
		);

		$this->wsdl->addComplexType(
			'field_details_array',
			'complexType',
			'array',
			'',
			array(
				'fieldlabel' => array('name'=>'fieldlabel','type'=>'xsd:string'),
				'fieldvalue' => array('name'=>'fieldvalue','type'=>'xsd:string'),
			)
		);
		$this->wsdl->addComplexType(
			'field_datalist_array',
			'complexType',
			'array',
			'',
			array(
				'fielddata' => array('name'=>'fielddata','type'=>'xsd:string'),
			)
		);

		$this->wsdl->addComplexType(
			'product_list_array',
			'complexType',
			'array',
			'',
			array(
				'productid' => array('name'=>'productid','type'=>'xsd:string'),
				'productname' => array('name'=>'productname','type'=>'xsd:string'),
				'productcode' => array('name'=>'productcode','type'=>'xsd:string'),
				'commissionrate' => array('name'=>'commissionrate','type'=>'xsd:string'),
				'qtyinstock' => array('name'=>'qtyinstock','type'=>'xsd:string'),
				'qty_per_unit' => array('name'=>'qty_per_unit','type'=>'xsd:string'),
				'unit_price' => array('name'=>'unit_price','type'=>'xsd:string'),
			)
		);

		$this->wsdl->addComplexType(
			'get_ticket_attachments_array',
			'complexType',
			'array',
			'',
			array(
				'files' => array(
					'fileid'=>'xsd:string','type'=>'tns:xsd:string',
					'filename'=>'xsd:string','type'=>'tns:xsd:string',
					'filesize'=>'xsd:string','type'=>'tns:xsd:string',
					'filetype'=>'xsd:string','type'=>'tns:xsd:string',
					'filecontents'=>'xsd:string','type'=>'tns:xsd:string'
				),
			)
		);
	}
	
	/**
	 * Extended function to handle $name as an array('name' => public name, 'path' => 'filename', 'class' => class name)
	 */
	function register($name,$in=array(),$out=array(),$namespace=false,$soapaction=false,$style=false,$use=false,$documentation='',$encodingStyle=''){
		if (is_array($name) && !empty($name['name'])) {
			$this->vte_ws[$name['name']] = $name;
			$name = $name['name'];
		}
		
		if ($namespace === false) $namespace = $this->vte_namespace;
		return parent::register($name,$in,$out,$namespace,$soapaction,$style,$use,$documentation,$encodingStyle);
	}
	
	/**
	 * Extend the invocation to support path+class
	 */
	function invoke_method() {
	
		$orig_methodname = $this->methodname;
		if (array_key_exists($this->methodname, $this->vte_ws)) {
			$wsinfo = $this->vte_ws[$this->methodname];
			if (!class_exists($wsinfo['handler_class'])) {
				if (!file_exists($wsinfo['handler_path'])) {
					$this->fault('SOAP-ENV:Client',"File {$wsinfo['handler_path']} not found for operation '$this->methodname'.");
					return;
				}
				require_once($wsinfo['handler_path']);
				// check again
				if (!class_exists($wsinfo['handler_class'])) {
					$this->fault('SOAP-ENV:Client',"Class {$wsinfo['handler_class']} not found for operation '$this->methodname'.");
					return;
				}
			}
			// change some vars
			$this->methodname = $wsinfo['handler_class'].'.'.$wsinfo['name'];
			$this->operations[$this->methodname] = true;
			$copyWsdl = $this->wsdl;
			$this->wsdl = null;
		}
		
		// call the original method
		$r = parent::invoke_method();
		
		// restore vars
		$this->methodname = $orig_methodname;
		// don't restore wsdl, otherwise the response wil be empty
		//if ($copyWsdl) $this->wsdl = $copyWsdl;
		
		return $r;
	}
	
}