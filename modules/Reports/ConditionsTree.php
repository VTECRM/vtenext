<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@103881 */

/**
 * Class used to generate unique condition ids
 */
class ConditionIds {
	
	static $id = 1;
	
	public static function get() {
		return self::$id++;
	}
}

/**
 * Represent a single condition (a leaf in the tree)
 */
class ConditionLeaf {

	protected $id;
	
	public $relation;
	public $cond;
	
	protected $depth = 1;
	protected $weight = 1;
	
	public function __construct($relation, $cond) {
		$this->id = ConditionIds::get();
		$this->relation = $relation ?: 'Main';
		$this->cond = $cond;
	}
	
	public function getWeight() {
		return $this->weight;
	}
	
	public function getDepth() {
		return $this->depth;
	}
	
	public function setDepth($d) {
		$this->depth = $d;
	}
	
	public function checkOr() {
		return true;
	}
	
	// Debug functions
	/*
	public function toSql() {
		return $this->cond;
	}
	
	protected function cond2str($cond) {
		$cond = '';
		if ($this->cond) {
			$cond = "{$this->cond['fieldid']} {$this->cond['comparator']} {$this->cond['value']}";
		}
		return $cond;
	}
	
	public function draw(&$gdTree, $parentId = null) {
		$title = "LEAF";
		$cond = $this->cond2str($this->cond);
		$text = $cond." ({$this->relation})";
		$w = 9*strlen($text);
		$gdTree->add($this->id, $parentId, $title, $text, $w);
	}
	
	public function __toString() {
		$cond = $this->cond2str($this->cond);
		$r = str_repeat("&nbsp;", $this->depth*4) . " LEAF {$this->relation} ($cond):\n<br>";
		return $r;
	}
	*/
	
}

/**
 * Represent a pair of conditions, joined by either OR or AND
 */
class ConditionNode {
	public $type;	// and/or
	
	protected $id;
	protected $left;	// can be a ConditionNode or a ConditionLeaf
	protected $right;	// idem
	
	protected $leftModules = array();
	protected $rightModules = array();
	
	protected $weight = 0;	// how many leaves are there behind this node
	protected $depth = 0;	// how many generations from the root
	
	public function __construct($type, $left = null, $right = null) {
		$this->id = ConditionIds::get();
		if (!$type) {
			throw new Exception("Missing type in node");
		}
		$this->type = strtolower($type);
		if ($left) {
			$this->left = $left;
			$this->left->setDepth($this->depth+1);
			$this->weight += $left->getWeight();
			$this->mergeModules($left, 'left');
		}
		if ($right) {
			$this->right = $right;
			$this->right->setDepth($this->depth+1);
			$this->weight += $right->getWeight();
			$this->mergeModules($right, 'right');
		}
	}
	
	public function getWeight() {
		return $this->weight;
	}
	
	public function getDepth() {
		return $this->depth;
	}
	
	public function setDepth($d) {
		$this->depth = $d;
		if ($this->left) {
			$this->left->setDepth($d+1);
		}
		if ($this->right) {
			$this->right->setDepth($d+1);
		}
	}
	
	protected function mergeModules($nodeOrLeaf, $side = 'left') {
		if ($side == 'left') {
			if ($nodeOrLeaf instanceof ConditionLeaf) {
				$this->leftModules[] = $nodeOrLeaf->relation;
			} else {
				$this->leftModules = array_merge($this->leftModules, $nodeOrLeaf->leftModules, $nodeOrLeaf->rightModules);
			}
			$this->leftModules = array_unique($this->leftModules);
		} else {
			if ($nodeOrLeaf instanceof ConditionLeaf) {
				$this->rightModules[] = $nodeOrLeaf->relation;
			} else {
				$this->rightModules = array_merge($this->rightModules, $nodeOrLeaf->leftModules, $nodeOrLeaf->rightModules);
			}
			$this->rightModules = array_unique($this->rightModules);
		}
	}
	
	public function add($nodeOrLeaf, $type = null) {
		$nodeOrLeaf->setDepth($this->depth + 1);
		$this->weight += $nodeOrLeaf->getWeight();
		
		if (!$this->left) {
			$this->left = $nodeOrLeaf;
			$this->mergeModules($nodeOrLeaf, 'left');
		} elseif (!$this->right) {
			$this->right = $nodeOrLeaf;
			$this->mergeModules($nodeOrLeaf, 'right');
		} else {
			// put it where the weight is the minimum
			if ($this->left->getWeight() <= $this->right->getWeight()) {
				$this->left = new ConditionNode($type, $this->left, $nodeOrLeaf);
				$this->left->setDepth($this->depth+1);
				// this code is ok for a generic tree, but in this case, it pushes down leaves of different type
				/*if ($this->left instanceof ConditionLeaf) {
					$this->left = new ConditionNode($type, $this->left, $nodeOrLeaf);
					$this->left->setDepth($this->depth+1);
				} else {
					$this->left->add($nodeOrLeaf, $type);
				}*/
				$this->mergeModules($this->left, 'left');
			} else {
				$this->right = new ConditionNode($type, $this->right, $nodeOrLeaf);
				$this->right->setDepth($this->depth+1);
				// idem
				/*if ($this->right instanceof ConditionLeaf) {
					$this->right = new ConditionNode($type, $this->right, $nodeOrLeaf);
					$this->right->setDepth($this->depth+1);
				} else {
					$this->right->add($nodeOrLeaf, $type);
				}*/
				$this->mergeModules($this->right, 'right');
			}
		}
	}
	
	public function checkOr() {
		$r = true;
		if (count($this->leftModules) > 0 && count($this->rightModules) > 0) {
			if ($this->type == 'or') {
				if (count($this->leftModules) > 1 || count($this->rightModules) > 1) {
					$r = false;
				} elseif ($this->leftModules[0] != $this->rightModules[0]) {
					$r = false;
				}
			}

			if ($r && $this->left instanceof ConditionNode) {
				$r = $r && $this->left->checkOr();
			}
			if ($r && $this->right instanceof ConditionNode) {
				$r = $r && $this->right->checkOr();
			}
		}
		
		return $r;
	}
	
	// Debug functions
	/*
	public function toSql() {
		$sql = "";
		if ($this->left && $this->right) {
			$leftSql = $this->left->toSql();
			$rightSql = $this->right->toSql();
			$sql = "($leftSql ".strtoupper($this->type)." $rightSql)";
		} elseif ($this->left) {
			$leftSql = $this->left->toSql();
			$sql = "$leftSql";
		} elseif ($this->right) {
			$rightSql = $this->right->toSql();
			$sql = "$rightSql";
		}
		return $sql;
	}
	
	public function draw(&$gdTree, $parentId = null) {
			
		$title = strtoupper($this->type);
		$text = "";
		$w = 60;

		$gdTree->add($this->id, $parentId, $title, $text, $w);
		if ($this->left) {
			$this->left->draw($gdTree, $this->id);
		}
		if ($this->right) {
			$this->right->draw($gdTree, $this->id);
		}
	}
	
	public function __toString() {
		$pad = str_repeat("&nbsp;", $this->depth*4);
		$r = $pad . " NODE {$this->type} ({$this->weight}):\n<br>";
		if ($this->left) {
			$r .= $pad." LEFT (".implode(',', $this->leftModules)."):<br>\n";
			$r .= strval($this->left) ?: "";
		} else {
			$r .= $pad." LEFT: X<br>\n";
		}
		if ($this->right) {
			$r .= $pad." RIGHT (".implode(',', $this->rightModules)."):<br>\n";
			$r .= strval($this->right) ?: "";
		} else {
			$r .= $pad." RIGHT: X<br>\n";
		}
		return $r;
	}
	*/
	
}

/**
 * The complete tree of the conditions
 */ 
class ConditionsTree {
	
	protected $root = null;
	
	/**
	 * Parse a hierarchy of conditions in a tree-like structure
	 */
	public function parse($conditions) {
		if (is_array($conditions) && !empty($conditions)) {
			if (empty($conditions['glue'])) {
				// list of conditions
				$tree = $this->parseList($conditions);
			} else {
				// one group/condition
				$tree = $this->parseCondition($conditions);
			}
			$this->root = $tree;
		}
	}
	
	/**
	 * Check if there are OR nodes with different modules on each side
	 * In this case, the push down won't be available
	 */
	public function checkOrNodes() {
		if ($this->root) {
			return $this->root->checkOr();
		} else {
			return true;
		}
	}
	
	protected function parseList($conditions) {

		$cond = $conditions[0];
		$current = $this->parseCondition($cond);

		if (count($conditions) == 1) {
			return $current;
		}
		
		$type = strtolower($cond['glue']);
		$ortrees = array();
		
		for ($i=1; $i<count($conditions); ++$i) {
			$cond = $conditions[$i];

			if ($type == 'or') {
				$ortrees[] = $current;
				$current = $this->parseCondition($cond);

			} else {
				//add to current (and) tree
				$newcond = $this->parseCondition($cond);
				if ($current instanceof ConditionLeaf) {
					$current = new ConditionNode($type, $current, $newcond);
				} else {
					if ($current->type == $type) {
						$current->add($newcond, $type);
					} else {
						$current = new ConditionNode($type, $current, $newcond);
					}
				}
			}
			$type = strtolower($cond['glue']);
		}

		if ($current) {
			$ortrees[] = $current;
		}
		
		if (count($ortrees) == 1) {
			$tree = $ortrees[0];
		} else {
			$tree = $this->mergeNodes($ortrees, 'or');
		}
		
		return $tree;
	}
	
	/**
	 * Parse a group or a condition and returns a node/leaf
	 * Returns a subtree
	 */
	protected function parseCondition($cond) {
	
		if ($cond['conditions']) {
			// has sub nodes
			$node = $this->parseList($cond['conditions']);
		} elseif ($cond['fieldid']) {
			$node = new ConditionLeaf($cond['relation'], $cond);
		}

		return $node;
	}
	
	protected function mergeNodes($list, $type = 'or') {
		if (count($list) == 1) {
			return $list[0];
		} else {
			$base = new ConditionNode($type, $list[0], $list[1]);
			if (count($list) > 2) {
				for ($i=2; $i<count($list); ++$i) {
					$base = new ConditionNode($type, $base, $list[$i]);
				}
			}
		}
		return $base;
	}
	
	// Debug functions
	/*
	public function __toString() {
		return strval($this->root);
	}
	
	public function toQuery() {
		if ($this->root) {
			return $this->root->toSql();
		} else {
			return "";
		}
	}
	
	public function draw($output = "storage/tree_debug.png") {
		// NOT IMPLEMTENTED
	}
	*/

}
