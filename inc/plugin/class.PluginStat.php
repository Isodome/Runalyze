<?php
/**
 * This file contains class::PluginStat
 * @package Runalyze\Plugin
 */
/**
 * Abstract plugin class for statistics
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
abstract class PluginStat extends Plugin {
	/**
	 * Boolean flag: show sports-navigation
	 * @var bool
	 */
	protected $ShowSportsNavigation = false;

	/**
	 * Boolean flag: show link for all sports
	 * @var bool
	 */
	protected $ShowAllSportsLink = false;

	/**
	 * Boolean flag: show years-navigation
	 * @var bool
	 */
	protected $ShowYearsNavigation = false;

	/**
	 * Boolean flag: show compare-link in years-navigation
	 * @var bool
	 */
	protected $ShowCompareYearsLink = true;

	/**
	 * Array of links (each wrapped in a <li>-tag
	 * @var array
	 */
	private $LinkList = array();

	/**
	 * Header
	 */
	private $Header = '';

	/**
	 * Type
	 * @return int
	 */
	final public function type() {
		return PluginType::Stat;
	}

	/**
	 * Set flag for sports-navigation
	 * @param bool $flag
	 * @param bool $allFlag
	 */
	protected function setSportsNavigation($flag = true, $allFlag = false) {
		$this->ShowSportsNavigation = $flag;
		$this->ShowAllSportsLink = $allFlag;
	}

	/**
	 * Set flag for years-navigation
	 * @param bool $flag
	 * @param bool $compareFlag [optional]
	 */
	protected function setYearsNavigation($flag = true, $compareFlag = true) {
		$this->ShowYearsNavigation = $flag;
		$this->ShowCompareYearsLink = $compareFlag;
	}

	/**
	 * Set array of links for toolbar-navigation
	 * @param array $Links
	 */
	protected function setToolbarNavigationLinks($Links) {
		$this->LinkList = $Links;
	}

	/**
	 * Includes the plugin-file for displaying the statistics
	 */
	public function display() {
		$this->prepareForDisplay();

		echo '<div class="panel-heading">';
		$this->displayHeader($this->Header, $this->getNavigation());
		echo '</div>';
		echo '<div class="panel-content statistics-container">';
		$this->displayContent();
		echo '</div>';
	}

	/**
	 * Set header
	 * @param string $Header
	 */
	protected function setHeader($Header) {
		$this->Header = $Header;
	}

	/**
	 * Add sport and year (if set) to header
	 */
	protected function setHeaderWithSportAndYear() {
		$HeaderParts = array();

		if ($this->sportid > 0 && $this->ShowSportsNavigation) {
			$Sport = new Sport($this->sportid);
			$HeaderParts[] = $Sport->name();
		}

		if ($this->year > 0 && $this->ShowYearsNavigation) {
			$HeaderParts[] = $this->year;
		}

		if (!empty($HeaderParts)) {
			$this->setHeader($this->name().': '.implode(', ', $HeaderParts));
		}
	}

	/**
	 * Get query for sport and year
	 * @return string
	 */
	protected function getSportAndYearDependenceForQuery() {
		$Query = '';

		if ($this->sportid > 0) {
			$Query .= ' AND `sportid`='.(int) $this->sportid;
		}

		if ($this->year > 0) {
			$Query .= ' AND YEAR(FROM_UNIXTIME(`time`))='.(int)$this->year;
		}

		return $Query;
	}

	/**
	 * Display header
	 * @param string $name
	 * @param string $rightMenu
	 * @param string $leftMenu
	 */
	private function displayHeader($name = '', $rightMenu = '', $leftMenu = '') {
		if ($name == '') {
			$name = $this->name();
		}

		if (!empty($leftMenu)) {
			echo '<div class="icons-left">'.$leftMenu.'</div>';
		}

		if (!empty($rightMenu)) {
			echo '<div class="panel-menu">'.$rightMenu.'</div>';
		}

		echo '<h1>'.$name.'</h1>';
		echo '<div class="hover-icons">'.$this->getConfigLink().$this->getReloadLink().'</div>';
	}

	/**
	 * Get navigation
	 */
	private function getNavigation() {
		if ($this->ShowSportsNavigation) {
			$this->LinkList[] = '<li class="with-submenu"><span class="link">'.__('Choose sport').'</span><ul class="submenu">'.$this->getSportLinksAsList().'</ul>';
		}

		if ($this->ShowYearsNavigation) {
			$this->LinkList[] = '<li class="with-submenu"><span class="link">'.__('Choose year').'</span><ul class="submenu">'.$this->getYearLinksAsList($this->ShowCompareYearsLink).'</ul>';
		}

		if (!empty($this->LinkList)) {
			return '<ul>'.implode('', $this->LinkList).'</ul>';
		}

		return '';
	}

	/**
	 * Get links for all sports
	 * @return array
	 */
	private function getSportLinksAsList() {
		$Links = '';

		if ($this->ShowAllSportsLink) {
			$Links .= '<li'.(-1==$this->sportid ? ' class="active"' : '').'>'.$this->getInnerLink(__('All'), -1, $this->year).'</li>';
		}

		$Sports = DB::getInstance()->query('SELECT `name`, `id` FROM `'.PREFIX.'sport` ORDER BY `id` ASC')->fetchAll();
		foreach ($Sports as $Sport) {
			$Links .= '<li'.($Sport['id']==$this->sportid ? ' class="active"' : '').'>'.$this->getInnerLink($Sport['name'], $Sport['id'], $this->year).'</li>';
		}

		return $Links;
	}

	/**
	 * Get links for all years
	 * @param bool $CompareYears If set, adds a link with year=-1
	 */
	private function getYearLinksAsList($CompareYears = true) {
		$Links = '';

		if ($CompareYears) { 
			$Links .= '<li'.(-1==$this->year ? ' class="active"' : '').'>'.$this->getInnerLink($this->titleForAllYears(), $this->sportid, -1).'</li>';
		}

		for ($x = date("Y"); $x >= START_YEAR; $x--) {
			$Links .= '<li'.($x==$this->year ? ' class="active"' : '').'>'.$this->getInnerLink($x, $this->sportid, $x).'</li>';
		}

		return $Links;
	}
		
	/**
	 * Get the year as string
	 * @return string
	 */
	protected function getYearString() {
		return ($this->year != -1 ? $this->year : $this->titleForAllYears());
	}

	/**
	 * Returns the html-link to this statistic for tab-navigation
	 * @return string
	 */
	public function getLink() {
		return '<a rel="statistics" href="'.self::$DISPLAY_URL.'?id='.$this->id().'">'.$this->name().'</a>';
	}

	/**
	 * Returns the html-link for inner-html-navigation
	 * @param string $name displayed link-name
	 * @param int $sport id of sport, default $this->sportid
	 * @param int $year year, default $this->year
	 * @param string $dat optional dat-parameter
	 * @return string
	 */
	protected function getInnerLink($name, $sport = 0, $year = 0, $dat = '') {
		if ($sport == 0) {
			$sport = $this->sportid;
		}

		if ($year == 0) {
			$year = $this->year;
		}

		return Ajax::link($name, 'statistics-inner', self::$DISPLAY_URL.'?id='.$this->id().'&sport='.$sport.'&jahr='.$year.'&dat='.$dat);
	}

	/**
	 * Are various statistics installed?
	 * @return bool
	 */
	static public function hasVariousStats() {
		$Factory = new PluginFactory();
		$array = $Factory->variousPlugins();

		return (!empty($array));
	}

	/**
	 * Get the link for first various statistic
	 * @return string
	 */
	static public function getLinkForVariousStats() {
		$Factory = new PluginFactory();
		$array = $Factory->variousPlugins();

		return $Factory->newInstance($array[0])->getLink();
	}
}