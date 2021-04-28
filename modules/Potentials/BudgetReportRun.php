<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@44323 crmv@53923 */

class BudgetReportRun extends ReportRun {

	// TODO: abilita export
	public $enableExportPdf = false;
	public $enableExportXls = false;
	public $enablePrint = false;
	public $hideParamsBlock = true;

	// private stuff
	protected $budgetPeriod = 'year';
	protected $budgetSubperiod = null; // changed in constructor
	protected $budgetPerUser = false;

	function __construct($reportid) {
		parent::__construct($reportid);
	
		$this->reportid = $reportid;
		$this->primarymodule = 'Potentials';
		$this->secondarymodule = '';
		$this->reporttype = '';
		//$this->reportname = Nuovo Report SDK;

		$this->columns = array();

		if (!empty($_REQUEST['budgetPeriod'])) {
			$this->budgetPeriod = $_REQUEST['budgetPeriod'];
		}

		if (!empty($_REQUEST['budgetSubperiod'])) {
			$this->budgetSubperiod = $_REQUEST['budgetSubperiod'];
		}

		if (empty($this->budgetSubperiod)) {
			// set a default
			switch ($this->budgetPeriod) {
				case 'year':
					$this->budgetSubperiod = 'year_'.date('Y');
					break;
				case 'months6':
					$this->budgetSubperiod = 'months6_1';
					break;
				case 'months4':
					$this->budgetSubperiod = 'months4_1';
					break;
				case 'months3':
					$this->budgetSubperiod = 'months3_1';
					break;
				case 'month':
					$this->budgetSubperiod = 'month_'.date('m');
					break;
			}
		}

		if (!empty($_REQUEST['budgetPerUser'])) {
			$this->budgetPerUser = in_array($_REQUEST['budgetPerUser'], array('on', 'true', '1'));
		}

	}

	function getPrimaryStdFilterHTML() {
		return '';
	}

	function getSecondaryStdFilterHTML() {
		return '';
	}

	function getDatesPicklist() {
		$html = "<select id=\"budgetSubperiod\" name=\"budgetSubperiod\" onchange=\"reloadBudget()\">";
		$options = array();
		switch ($this->budgetPeriod) {
			case 'year': {
				for ($y=date('Y')-3; $y<=date('Y'); ++$y) {
					$options[] = array('value' => 'year_'.$y, 'label' => $y);
				}
				break;
			}
			case 'months6': {
				$options[] = array('value' => 'months6_1', 'label' => getTranslatedString('LBL_FIRST').' '.getTranslatedString('LBL_SEMESTER'));
				$options[] = array('value' => 'months6_2', 'label' => getTranslatedString('LBL_SECOND').' '.getTranslatedString('LBL_SEMESTER'));
				break;
			}
			case 'months4': {
				$options[] = array('value' => 'months4_1', 'label' => getTranslatedString('LBL_FIRST').' '.getTranslatedString('LBL_QUARTER'));
				$options[] = array('value' => 'months4_2', 'label' => getTranslatedString('LBL_SECOND').' '.getTranslatedString('LBL_QUARTER'));
				$options[] = array('value' => 'months4_3', 'label' => getTranslatedString('LBL_THIRD').' '.getTranslatedString('LBL_QUARTER'));
				break;
			}
			case 'months3': {
				$options[] = array('value' => 'months3_1', 'label' => getTranslatedString('LBL_FIRST').' '.getTranslatedString('LBL_TRIMESTER'));
				$options[] = array('value' => 'months3_2', 'label' => getTranslatedString('LBL_SECOND').' '.getTranslatedString('LBL_TRIMESTER'));
				$options[] = array('value' => 'months4_3', 'label' => getTranslatedString('LBL_THIRD').' '.getTranslatedString('LBL_TRIMESTER'));
				$options[] = array('value' => 'months4_4', 'label' => getTranslatedString('LBL_FOURTH').' '.getTranslatedString('LBL_TRIMESTER'));
				break;
			}
			case 'month': {
				$months = array(
					getTranslatedString('LBL_MONTH_JANUARY'),
					getTranslatedString('LBL_MONTH_FEBRUARY'),
					getTranslatedString('LBL_MONTH_MARCH'),
					getTranslatedString('LBL_MONTH_APRIL'),
					getTranslatedString('LBL_MONTH_MAY'),
					getTranslatedString('LBL_MONTH_JUNE'),
					getTranslatedString('LBL_MONTH_JULY'),
					getTranslatedString('LBL_MONTH_AUGUST'),
					getTranslatedString('LBL_MONTH_SEPTEMBER'),
					getTranslatedString('LBL_MONTH_OCTOBER'),
					getTranslatedString('LBL_MONTH_NOVEMBER'),
					getTranslatedString('LBL_MONTH_DECEMBER'),
				);
				for ($i=1; $i<=12; ++$i) {
					$options[] = array('value' => 'month_'.$i, 'label' => $months[$i-1]);
				}
				break;
			}
		}

		$html .= "<option value=\"\">-- ".getTranslatedString('LBL_SELECT')."-- </option>";
		foreach ($options as $o) {
			$sel = ($o['value'] == $this->budgetSubperiod ? 'selected=""' : '');
			$html .= "<option value=\"{$o['value']}\" $sel>{$o['label']}</option>";
		}

		$html .= "</select>";
		return $html;
	}

	function getSDKBlock() {
		// TODO: ci sono url multipli //crmv@59091 
		$html = getTranslatedString('LBL_PERIOD', 'APP_STRINGS').":
		<select id=\"budgetPeriod\" name=\"budgetPeriod\" onchange=\"reloadBudget(true)\">
			<option value=\"year\" ".($this->budgetPeriod == 'year' ? 'selected=""' : '').">".getTranslatedString('LBL_REP_EXTRACT_YEAR')."</option>
			<option value=\"months6\" ".($this->budgetPeriod == 'months6' ? 'selected=""' : '').">".getTranslatedString('LBL_SEMESTER')."</option>
			<option value=\"months4\" ".($this->budgetPeriod == 'months4' ? 'selected=""' : '').">".getTranslatedString('LBL_QUARTER')."</option>
			<option value=\"months3\" ".($this->budgetPeriod == 'months3' ? 'selected=""' : '').">".getTranslatedString('LBL_TRIMESTER')."</option>
			<option value=\"month\" ".($this->budgetPeriod == 'month' ? 'selected=""' : '').">".getTranslatedString('LBL_REP_EXTRACT_MONTH')."</option>
		</select>
		";
		$html .= $this->getDatesPicklist();
		$html .= '<input type="checkbox" name="budgetPerUser" id="budgetPerUser" '.($this->budgetPerUser ? 'checked="checked"' : '').'><label for="budgetPerUser">'.getTranslatedString('GroupByUser').'</label>';
		return $html;
	}

	function getQueryDateCondition($column = '') {
		global $table_prefix;
		
		$limit1 = $limit2 = '';
		switch ($this->budgetPeriod) {
			case 'year': {
				$limit1 = str_replace('year_', '', $this->budgetSubperiod).'-01-01';
				$limit2 = str_replace('year_', '', $this->budgetSubperiod).'-12-31';
				break;
			}
			case 'months6': {
				if ($this->budgetSubperiod == 'months6_1') {
					$limit1 = date('Y').'-01-01';
					$limit2 = date('Y').'-06-30';
				} else {
					$limit1 = date('Y').'-07-01';
					$limit2 = date('Y').'-12-31';
				}
				break;
			}
			case 'months4': {
				if ($this->budgetSubperiod == 'months4_1') {
					$limit1 = date('Y').'-01-01';
					$limit2 = date('Y').'-04-30';
				} elseif ($this->budgetSubperiod == 'months4_2') {
					$limit1 = date('Y').'-05-01';
					$limit2 = date('Y').'-08-31';
				} else {
					$limit1 = date('Y').'-09-01';
					$limit2 = date('Y').'-12-31';
				}
				break;
			}
			case 'months3': {
				if ($this->budgetSubperiod == 'months3_1') {
					$limit1 = date('Y').'-01-01';
					$limit2 = date('Y').'-03-31';
				} elseif ($this->budgetSubperiod == 'months3_2') {
					$limit1 = date('Y').'-04-01';
					$limit2 = date('Y').'-06-30';
				} elseif ($this->budgetSubperiod == 'months3_3') {
					$limit1 = date('Y').'-07-01';
					$limit2 = date('Y').'-09-30';
				} else {
					$limit1 = date('Y').'-10-01';
					$limit2 = date('Y').'-12-31';
				}
				break;
			}
			case 'month': {
				$m = str_replace('month_', '', $this->budgetSubperiod);
				$dm = cal_days_in_month(CAL_GREGORIAN, $m, date('Y'));
				$sm = str_pad($m, 2, '0', STR_PAD_LEFT);
				$limit1 = date('Y')."-$sm-01";
				$limit2 = date('Y')."-$sm-$dm";
				break;
			}
		}
		if (!empty($limit1) && !empty($limit2)) {
			if (empty($column)) $column = "{$table_prefix}_potential.closingdate";
			$cond = "AND $column BETWEEN '$limit1' AND '$limit2'";
		}
		return $cond;
	}

	function getData($reportid,$filterlist,$outputformat) {
		global $adb, $table_prefix, $current_user;

		$potFocus = CRMEntity::getInstance('Potentials');
		$potTab = $potFocus->table_name;
		
		$whereCond = $this->getQueryDateCondition();
		//$whereCloseCond = preg_replace('/^\s*and\s*/i', '', $whereCond);
		//$whereCloseCond = str_replace('pot.closingdate', 'pot.eff_closingdate', $whereCloseCond);

		// crmv@49622
		if ($this->budgetPerUser) {
			//$whereCloseCond = str_replace('pot.', 'ipot.', $whereCloseCond);

			$query =
			"select
				{$table_prefix}_crmentity.smownerid,
				sum(case when $potTab.probability >= 70 then $potTab.amount else 0 end) as best,
				sum(case when $potTab.probability >= 80 then $potTab.amount else 0 end) as forecast,
				sum(case when $potTab.probability >= 90 then $potTab.amount else 0 end) as worst
			from {$table_prefix}_potential
				inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = $potTab.potentialid
				POTJOIN
				".$potFocus->getNonAdminAccessControlQuery('Potentials',$current_user)."
			where
				{$table_prefix}_crmentity.deleted = 0
			$whereCond
			group by {$table_prefix}_crmentity.smownerid";
			
			$query = $potFocus->listQueryNonAdminChange($query, 'Potentials');
			$query = str_replace('POTJOIN',"
				inner join (
					select distinct pq.potentialid
					from {$table_prefix}_quotes pq
					inner join {$table_prefix}_crmentity pqcrm on pqcrm.crmid = pq.quoteid
					where pqcrm.deleted = 0 and pq.potentialid > 0
				) quotes on quotes.potentialid = $potTab.potentialid", $query
			);

		} else {
			$query =
			"select
				pl.productlineid,
				0 as best,
				0 as forecast,
				0 as worst
			from {$table_prefix}_potential
				inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = $potTab.potentialid
				inner join {$table_prefix}_quotes q on q.potentialid = $potTab.potentialid and q.quotestage in ('Created', 'Delivered')
				inner join {$table_prefix}_crmentity qcrm on qcrm.crmid = q.quoteid
				LINEIDJOIN
				".$potFocus->getNonAdminAccessControlQuery('Potentials',$current_user)."
			where
				{$table_prefix}_crmentity.deleted = 0 and qcrm.deleted = 0
				$whereCond
			group by pl.productlineid";
		
			// add security parameters
			$query = $potFocus->listQueryNonAdminChange($query, 'Potentials');
			// replace the join with linesid (troubles in the listQueryChange, so do it later)
			$query = str_replace('LINEIDJOIN',"
				inner join (
					select distinct ipr.id, pl.productlineid
					from {$table_prefix}_inventoryproductrel ipr
					inner join {$table_prefix}_products p on p.productid = ipr.productid
					inner join {$table_prefix}_crmentity pcrm on pcrm.crmid = p.productid
					left join {$table_prefix}_productlines pl on pl.productlineid = p.productlineid
					left join {$table_prefix}_crmentity plcrm on plcrm.crmid = pl.productlineid
					where pcrm.deleted = 0 and (plcrm.deleted is null or plcrm.deleted = 0)
				) pl on pl.id = q.quoteid",
			$query);
		}
		
		// crmv@49622e

		// retrieve potentials
		$respot = $adb->pquery($query, array());
		$data = array();
		while ($row = $adb->FetchByAssoc($respot, -1, false)) {
			// calculate the order amount for product line
			if ($this->budgetPerUser) {
				$orderAmount = $this->getOrderAmountByUser($row['smownerid'], $potFocus);
			} else {
				$orderAmount = $this->getOrderAmountByProdLine($row['productlineid'], $potFocus);
				$forecast = $this->getForecastsByProdLine($row['productlineid'], $potFocus);
				$row['best'] = $forecast['best'];
				$row['forecast'] = $forecast['forecast'];
				$row['worst'] = $forecast['worst'];
			}
			$row['orders_tot'] = $orderAmount;
			$data[] = $row;
		}

		return $data;
	}
	
	function getForecastsByProdLine($lineid, &$potFocus) {
		$values = array('best'=>0, 'forecast' => 0, 'worst' => 0);
		
		$lineid = intval($lineid);
		$potentials = $this->getPotentialsByProdLine($lineid, $potFocus);
		
		foreach ($potentials as $potinfo) {
			if ($potinfo['probability'] >= 70) {
				$quoteid = $potinfo['quoteid'];
				$lineinfo = $potFocus->getProdLinesInfo($quoteid, 'Quotes');
				if ($lineinfo && is_array($lineinfo['list'])) {
					foreach ($lineinfo['list'] as $line) {
						if ($line['productlineid'] == $lineid) {
							if ($potinfo['probability'] >= 70) {
								$values['best'] += $line['total'];
								if ($potinfo['probability'] >= 80) {
									$values['forecast'] += $line['total'];
									if ($potinfo['probability'] >= 90) {
										$values['worst'] += $line['total'];
									}
								}
							}
							break;
						}
					}
				}
			}
		}
		
		return $values;
	}
	
	function getOrderAmountByUser($userid, &$potFocus) {
		global $adb, $table_prefix, $current_user;
		
		$userid = intval($userid);
		
		// first get the relevant orders
		$queryGenerator = QueryGenerator::getInstance('SalesOrder', $current_user);
		$queryGenerator->addField('subject');
				
		$where = $this->getQueryDateCondition("{$table_prefix}_salesorder.duedate");
		$queryGenerator->appendToWhereClause(" $where AND {$table_prefix}_crmentity.deleted = 0 AND {$table_prefix}_salesorder.sostatus NOT IN ('Cancelled')");
		$queryGenerator->appendToWhereClause(" AND {$table_prefix}_crmentity.smownerid = '$userid'");
		
		$query = $queryGenerator->getQuery();
		$query = replaceSelectQuery($query, "{$table_prefix}_crmentity.crmid");
		
		$soids = array();
		$res = $adb->query($query);
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$soids[] = $row['crmid'];
		}

		//now I have all the required sales order id
		$total = 0;
		foreach ($soids as $sid) {
			$lineinfo = $potFocus->getProdLinesInfo($sid, 'SalesOrder');
			if ($lineinfo && is_array($lineinfo['list'])) {
				$total += $lineinfo['linestotal'];
			}
		}
		return $total;
	}
	
	function getOrderAmountByProdLine($lineid, &$potFocus) {
		global $adb, $table_prefix, $current_user;
		
		$lineid = intval($lineid);
		
		// first get the relevant orders
		$queryGenerator = QueryGenerator::getInstance('SalesOrder', $current_user);
		$queryGenerator->addField('subject');
		
		$queryGenerator->appendToFromClause("
			INNER JOIN {$table_prefix}_inventoryproductrel ipr on ipr.id = {$table_prefix}_salesorder.salesorderid
			inner join {$table_prefix}_products p on p.productid = ipr.productid
			inner join {$table_prefix}_crmentity pcrm on pcrm.crmid = p.productid
			left join {$table_prefix}_productlines pl on pl.productlineid = p.productlineid
			left join {$table_prefix}_crmentity plcrm on plcrm.crmid = pl.productlineid
		");
		
		$where = $this->getQueryDateCondition("{$table_prefix}_salesorder.duedate");
		$queryGenerator->appendToWhereClause(" $where AND pcrm.deleted = 0 AND {$table_prefix}_salesorder.sostatus NOT IN ('Cancelled')");
		if ($lineid > 0) {
			$queryGenerator->appendToWhereClause(" AND pl.productlineid = '$lineid' AND plcrm.deleted = 0");
		} else {
			$queryGenerator->appendToWhereClause(" AND pl.productlineid IS NULL");
		}
		
		$query = $queryGenerator->getQuery();
		$query = replaceSelectQuery($query, "distinct {$table_prefix}_crmentity.crmid");
		
		$soids = array();
		$res = $adb->query($query);
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$soids[] = $row['crmid'];
		}
	
		//now I have all the required sales order id
		$total = 0;
		foreach ($soids as $sid) {
			$lineinfo = $potFocus->getProdLinesInfo($sid, 'SalesOrder');
			if ($lineinfo && is_array($lineinfo['list'])) {
				foreach ($lineinfo['list'] as $line) {
					if ($line['productlineid'] == $lineid) {
						$total += $line['total'];
						break;
					}
				}
			}
		}
		return $total;
	}

	function getPotentialsByUser($userid, &$potFocus) {
		global $adb, $table_prefix, $current_user;

		$userid = intval($userid);
		$potTab = $table_prefix.'_potential';
		
		$data = array();
		
		$whereCond = $this->getQueryDateCondition();
		$whereCond .= " AND {$table_prefix}_crmentity.smownerid = '$userid'";
		
		$query =
			"select
				{$table_prefix}_crmentity.smownerid, $potTab.potentialid, $potTab.potentialname, $potTab.probability, q.quoteid
			from {$table_prefix}_potential
				inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = $potTab.potentialid
				inner join {$table_prefix}_quotes q on q.potentialid = $potTab.potentialid and q.quotestage in ('Created', 'Delivered')
				inner join {$table_prefix}_crmentity qcrm on qcrm.crmid = q.quoteid
				POTJOIN
				".$potFocus->getNonAdminAccessControlQuery('Potentials',$current_user)."
			where
				{$table_prefix}_crmentity.deleted = 0
			$whereCond";
			
			$query = $potFocus->listQueryNonAdminChange($query, 'Potentials');
			$query = str_replace('POTJOIN',"
				inner join (
					select distinct pq.potentialid
					from {$table_prefix}_quotes pq
					inner join {$table_prefix}_crmentity pqcrm on pqcrm.crmid = pq.quoteid
					where pqcrm.deleted = 0 and pq.potentialid > 0
				) quotes on quotes.potentialid = $potTab.potentialid", $query
			);
				
		$respot = $adb->query($query);
		$data = array();
		while ($row = $adb->FetchByAssoc($respot, -1, false)) {
			$data[] = $row;
		}
		
		return $data;
	}
	
	function getPotentialsByProdLine($prodlineid, &$potFocus) {
		global $adb, $table_prefix, $current_user;

		$prodlineid = intval($prodlineid);
		$potTab = $table_prefix.'_potential';
		
		$data = array();
		
		$whereCond = $this->getQueryDateCondition();
		if ($prodlineid > 0) {
			$whereCond .= " AND pl.productlineid = '$prodlineid'";
		} else {
			$whereCond .= " AND pl.productlineid IS NULL";
		}
		
		$query =
			"select
				$potTab.potentialid, $potTab.potentialname, $potTab.probability, q.quoteid
			from {$table_prefix}_potential
				inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = $potTab.potentialid
				inner join {$table_prefix}_quotes q on q.potentialid = $potTab.potentialid and q.quotestage in ('Created', 'Delivered')
				inner join {$table_prefix}_crmentity qcrm on qcrm.crmid = q.quoteid
				LINEIDJOIN
				".$potFocus->getNonAdminAccessControlQuery('Potentials',$current_user)."
			where
				{$table_prefix}_crmentity.deleted = 0 and qcrm.deleted = 0
				$whereCond";
		
		// add security parameters
		$query = $potFocus->listQueryNonAdminChange($query, 'Potentials');
		// replace the join with linesid (troubles in the listQueryChange, so do it later)
		$query = str_replace('LINEIDJOIN',"
			inner join (
				select distinct ipr.id, pl.productlineid
				from {$table_prefix}_inventoryproductrel ipr
				inner join {$table_prefix}_products p on p.productid = ipr.productid
				inner join {$table_prefix}_crmentity pcrm on pcrm.crmid = p.productid
				left join {$table_prefix}_productlines pl on pl.productlineid = p.productlineid
				left join {$table_prefix}_crmentity plcrm on plcrm.crmid = pl.productlineid
				where pcrm.deleted = 0 and (plcrm.deleted is null or plcrm.deleted = 0)
			) pl on pl.id = q.quoteid",
		$query);
				
		// exclude potentials with no or more than 1 active quotes
		$respot = $adb->query($query);
		$data = array();
		$oldid = null;
		$remove_ids = array();
		while ($row = $adb->FetchByAssoc($respot, -1, false)) {
			if (!is_null($oldid) && $oldid == $row['potentialid']) {
				$remove_ids[] = $oldid;
				continue;
			}
			$data[$row['potentialid']] = $row;
			$oldid = $row['potentialid'];
		}
		$remove_ids = array_unique($remove_ids);
		if (count($remove_ids) > 0) {
			foreach ($remove_ids as $rid) {
				unset($data[$rid]);
			}
		}
		
		return $data;
	}

	function GenerateReport($outputformat, $filterlist = null, $directOutput=false) {
		global $adb, $table_prefix;
		
		ini_set('max_execution_time',600);

		$data = $this->getData($this->reportid,$filterlist,$outputformat);
		$nrows = count($data);

		$html = '';
		
		$potFocus = CRMEntity::getInstance('Potentials');

		switch ($outputformat) {
			default:
			case 'HTML':
			case 'PDF':
			case 'PRINT':

				if (count($data) > 0) {
				$html = "<table cellpadding=\"5\" cellspacing=\"0\" align=\"center\" class=\"rptTable\">";

				$html .=
				"<thead><tr class=\"reportRowTitle\">
					<td class=\"rptCellLabel\">".($this->budgetPerUser ? getTranslatedString('LBL_USER') : getTranslatedString('SINGLE_ProductLines', 'ProductLines'))."</td>
					<td class=\"rptCellLabel\">Budget</td>
					<td class=\"rptCellLabel\">Best</td>
					<td class=\"rptCellLabel\">Forecast</td>
					<td class=\"rptCellLabel\">Worst</td>
					<td class=\"rptCellLabel\">".getTranslatedString('ClosedOrders')."</td>
					<td class=\"rptCellLabel\">Delta Budget</td>
				</tr></thead><tbody>";
				$rowN = 0;
				$counter = 0;
				foreach ($data as $drow) {
					if ($drow['productlineid'] > 0) {
						$prodLineName = getEntityName('ProductLines', $drow['productlineid']);
						$prodLineName = $prodLineName[$drow['productlineid']];
						$budgetQty = 0;
						$budgetDivisors = array('year'=>1, 'months6'=>2, 'months4'=>3, 'months3'=>4, 'month'=>12);
						if (array_key_exists($this->budgetPeriod, $budgetDivisors)) {
							$yearyAmount = getSingleFieldValue($table_prefix.'_productlines', 'yearly_budget', 'productlineid', $drow['productlineid']);
							$budgetQty = floatval($yearyAmount) / $budgetDivisors[$this->budgetPeriod];
						}
						$budget = formatUserNumber($budgetQty);
						$deltaBudget = formatUserNumber($drow['orders_tot'] - $budgetQty); // crmv@49622
					} else {
						$prodLineName = 'N/A';
						$budget = $deltaBudget = ' - ';
					}

					$best = formatUserNumber($drow['best']);
					$forecast = formatUserNumber($drow['forecast']);
					$worst = formatUserNumber($drow['worst']);
					$orders = formatUserNumber($drow['orders_tot']);

					if ($this->budgetPerUser) {
						$oppalist = $this->getPotentialsByUser($drow['smownerid'], $potFocus);
						$prodLineName = getUserName($drow['smownerid']);
					} else {
						$oppalist = $this->getPotentialsByProdLine($drow['productlineid'], $potFocus);
					}

					$oppadiv = '';
					if (count($oppalist) > 0) {
						$scrolling = (count($oppalist) > 10);
						if ($scrolling) {
							$oppadiv .= '<div style="height:200px;overflow-y:scroll">';
						}
						$oppadiv .= "<table cellpadding=\"2\" cellspacing=\"1\" border=\"0\">";
						foreach ($oppalist as $opp) {
							$oppadiv .= "<tr><td><a href=\"index.php?module=Potentials&action=DetailView&record={$opp['potentialid']}\">{$opp['potentialname']}</a></td></tr>";
						}
						$oppadiv .= "</table>";
						if ($scrolling) {
							$oppadiv .= '</div>';
						}
						$oppadiv = getCrmvDivHtml('potList_'.$counter, getTranslatedString('Potentials').' '.getTranslatedString('LBL_FOR').' '.$prodLineName, $oppadiv);
					}

					$linename = "<a href=\"javascript:;\" onclick=\"fnvshobj(this, 'potList_$counter')\">$prodLineName</a>$oppadiv";


					$html .=
					"<tr class=\"reportRow$rowN\">
						<td class=\"rptData\"><b>$linename</b></td>
						<td class=\"rptData\">$budget</td>
						<td class=\"rptData\">$best</td>
						<td class=\"rptData\">$forecast</td>
						<td class=\"rptData\">$worst</td>
						<td class=\"rptData\">$orders</td>
						<td class=\"rptData\">$deltaBudget</td>
					</tr>";
					$rowN ^= 1;
					++$counter;
				}
				$html .= "</tbody></table>";
				} else {
					$html = "<b>".getTranslatedString('LBL_NO_DATA')."</b>";
				}
				$html .= '<script type="text/javascript">var report_info_override = "";</script>'; // crmv@49622
				$return_data = $html;
				break;
			case 'XLS':
				$return_data = '';
				break;
			case 'TOTALXLS':
			case 'TOTALHTML':
			case 'PRINT_TOTAL':
				$report_data = '';
				break;
		}
		return array($return_data, $nrows);
	}


}

?>