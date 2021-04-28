<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * Description of RelatedModuleMeta
 * TODO to add and extend a way to track many-many and many-one relationships.
 * @author MAK
 */
class RelatedModuleMeta {
	private $module;
	private $relatedModule;
	private $CAMPAIGNCONTACTREL = 1;
	private $PRODUCTQUOTESREL = 2;
	private $PRODUCTINVOICEREL = 3;
	private $PRODUCTPURCHASEORDERREL = 4;
	
	private function  __construct($module, $relatedModule) {
		$this->module = $module;
		$this->relatedModule = $relatedModule;
	}

	/**
	 *
	 * @param <type> $module
	 * @param <type> $relatedModule
	 * @return RelatedModuleMeta 
	 */
	public static function getInstance($module, $relatedModule) {
		return new RelatedModuleMeta($module, $relatedModule);
	}

	public function getRelationMeta() {
		$campaignContactRel = array('Campaigns','Contacts');
		$productInvoiceRel = array('Products','Invoice');
		$productQuotesRel = array('Products','Quotes');
		$productPurchaseOrder = array('Products','PurchaseOrder');
		if(in_array($this->module, $campaignContactRel) && in_array($this->relatedModule,
				$campaignContactRel)) {
			return $this->getRelationMetaInfo($this->CAMPAIGNCONTACTREL);
		}
		if(in_array($this->module, $productInvoiceRel) && in_array($this->relatedModule,
				$productInvoiceRel)) {
			return $this->getRelationMetaInfo($this->PRODUCTINVOICEREL);
		}
		if(in_array($this->module, $productQuotesRel) && in_array($this->relatedModule,
				$productQuotesRel)) {
			return $this->getRelationMetaInfo($this->PRODUCTQUOTESREL);
		}
		if(in_array($this->module, $productPurchaseOrder) && in_array($this->relatedModule,
				$productPurchaseOrder)) {
			return $this->getRelationMetaInfo($this->PRODUCTPURCHASEORDERREL);
		}
	}

	private function getRelationMetaInfo($relationId) {
		global $table_prefix;
		switch($relationId) {
			case $this->CAMPAIGNCONTACTREL: return array(
					'relationTable' => $table_prefix.'_campaigncontrel',
					'Campaigns' => 'campaignid',
					'Contacts' => 'contactid'
				);
			case $this->PRODUCTINVOICEREL: return array(
					'relationTable' => $table_prefix.'_inventoryproductrel',
					'Products' => 'productid',
					'Invoice' => 'id'
				);
			case $this->PRODUCTQUOTESREL: return array(
					'relationTable' => $table_prefix.'_inventoryproductrel',
					'Products' => 'productid',
					'Quotes' => 'id'
				);
			case $this->PRODUCTPURCHASEORDERREL: return array(
					'relationTable' => $table_prefix.'_inventoryproductrel',
					'Products' => 'productid',
					'PurchaseOrder' => 'id'
				);
		}
	}
}
?>