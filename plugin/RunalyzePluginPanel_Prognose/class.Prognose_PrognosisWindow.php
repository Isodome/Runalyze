<?php
/**
 * This file contains the class Prognose_PrognosisWindow
 * @package Runalyze\Plugins\Panels
 */
/**
 * Prognosis calculator window
 * 
 * Additional window for calculating special prognoses.
 * @author Hannes Christiansen
 * @package Runalyze\Plugins\Panels
 */
class Prognose_PrognosisWindow {
	/**
	 * Formular
	 * @var Formular
	 */
	protected $Formular = null;

	/**
	 * Fieldset: Input
	 * @var FormularFieldset
	 */
	protected $FieldsetInput = null;

	/**
	 * Fieldset: Result
	 * @var FormularFieldset
	 */
	protected $FieldsetResult = null;

	/**
	 * Prognosis object
	 * @var RunningPrognosis
	 */
	protected $PrognosisObject = null;

	/**
	 * Prognosis strategies
	 * @var array
	 */
	protected $PrognosisStrategies = array();

	/**
	 * Distances
	 * @var array
	 */
	protected $Distances = array();

	/**
	 * Prognoses as array
	 * @var array
	 */
	protected $Prognoses = array();

	/**
	 * Result table
	 * @var string
	 */
	protected $ResultTable = '';

	/**
	 * Info lines
	 * @var array
	 */
	protected $InfoLines = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->setDefaultValues();
		$this->readPostData();
		$this->runCalculations();
		$this->fillResultTable();
		$this->initFieldsets();
		$this->initFormular();
	}

	/**
	 * Set default values
	 */
	protected function setDefaultValues() {
		$Strategy = new RunningPrognosisBock;
		$TopResults = $Strategy->getTopResults(2);

		if (empty($_POST)) {
			$Plugin = Plugin::getInstanceFor('RunalyzePluginPanel_Prognose');

			$_POST['model'] = 'jack-daniels';
			$_POST['distances'] = implode(', ', $Plugin->getDistances());

			$_POST['vdot'] = JD::getConstVDOTform();
			$_POST['endurance'] = true;
			$_POST['endurance-value'] = BasicEndurance::getConst();

			$_POST['best-result-km'] = !empty($TopResults) ? $TopResults[0]['distance'] : '5.0';
			$_POST['best-result-time'] = !empty($TopResults) ? Time::toString($TopResults[0]['s'], false, true) : '0:26:00';
			$_POST['second-best-result-km'] = !empty($TopResults) ? $TopResults[1]['distance'] : '10.0';
			$_POST['second-best-result-time'] = !empty($TopResults) ? Time::toString($TopResults[1]['s'], false, true) : '1:00:00';
		}

		$this->InfoLines['jack-daniels'] = 'Dein aktueller VDOT-Wert: '.JD::getConstVDOTform().'. Dein aktueller GA-Wert: '.BasicEndurance::getConst().'.';

		$ResultLine = empty($TopResults) ? 'keine' : Running::km($TopResults[0]['distance']).' in '.Time::toString($TopResults[0]['s']).' und '.Running::km($TopResults[1]['distance']).' in '.Time::toString($TopResults[1]['s']);
		$this->InfoLines['robert-bock'] = 'Deine beiden &bdquo;<em>besten</em>&rdquo; Bestzeiten: '.$ResultLine;

		$this->setupJackDanielsStrategy();
		$this->setupBockStrategy();
		$this->setupSteffnyStrategy();
	}

	/**
	 * Read post data
	 */
	protected function readPostData() {
		$this->PrognosisObject = new RunningPrognosis;
		$this->Distances = Helper::arrayTrim(explode(',', $_POST['distances']));

		$this->PrognosisObject->setStrategy( $this->PrognosisStrategies[$_POST['model']] );
	}

	/**
	 * Setup prognosis strategy: Jack Daniels
	 */
	protected function setupJackDanielsStrategy() {
		$Strategy = new RunningPrognosisDaniels;
		$Strategy->adjustVDOT( isset($_POST['endurance']) );
		$Strategy->setVDOT( (float)Helper::CommaToPoint($_POST['vdot']) );
		$Strategy->setBasicEnduranceForAdjustment( (int)$_POST['endurance-value'] );

		$this->PrognosisStrategies['jack-daniels'] = $Strategy;
	}

	/**
	 * Setup prognosis strategy: Robert Bock
	 */
	protected function setupBockStrategy() {
		$Strategy = new RunningPrognosisBock;
		$Strategy->setFromResults(
			$_POST['best-result-km'],
			Time::toSeconds($_POST['best-result-time']),
			$_POST['second-best-result-km'],
			Time::toSeconds($_POST['second-best-result-time'])
		);

		$this->PrognosisStrategies['robert-bock'] = $Strategy;
	}

	/**
	 * Setup prognosis strategy: Herbert Steffny
	 */
	protected function setupSteffnyStrategy() {
		$Strategy = new RunningPrognosisSteffny;
		$Strategy->setReferenceResult($_POST['best-result-km'], Time::toSeconds($_POST['best-result-time']));

		$this->PrognosisStrategies['herbert-steffny'] = $Strategy;
	}

	/**
	 * Init calculations
	 */
	protected function runCalculations() {
		foreach ($this->Distances as $km) {
			$PB         = Running::PersonalBest($km, true);
			$Prognosis  = $this->PrognosisObject->inSeconds( $km );

			if ($PB > 0)
				$PBdate = Mysql::getInstance()->fetchSingle('SELECT `time` FROM `'.PREFIX.'training` WHERE `typeid`="'.CONF_WK_TYPID.'" AND `distance`="'.$km.'" ORDER BY `s` ASC');

			$this->Prognoses[] = array(
				'distance'	=> Running::Km($km, 1, $km <= 3),
				'prognosis'		=> Time::toString($Prognosis),
				'prognosis-pace'=> SportSpeed::minPerKm($km, $Prognosis).'/km',
				'prognosis-vdot'=> round(JD::Competition2VDOT($km, $Prognosis), 2),
				'diff'			=> $PB == 0 ? '-' : ($PB>$Prognosis?'+ ':'- ').Time::toString(abs(round($PB-$Prognosis)),false,true),
				'diff-class'	=> $PB > $Prognosis ? 'plus' : 'minus',
				'pb'			=> $PB > 0 ? Time::toString($PB) : '-',
				'pb-pace'		=> $PB > 0 ? SportSpeed::minPerKm($km, $PB).'/km' : '-',
				'pb-vdot'		=> $PB > 0 ? round(JD::Competition2VDOT($km, $PB),2) : '-',
				'pb-date'		=> $PB > 0 ? date('d.m.Y', $PBdate['time']) : '-'
			);
		}
	}

	/**
	 * Fill result table
	 */
	protected function fillResultTable() {
		$this->startResultTable();
		$this->fillResultTableWithResults();
		$this->finishResultTable();
	}

	/**
	 * Start result table
	 */
	protected function startResultTable() {
		$this->ResultTable = '<table style="width:100%;"><thead><tr>
					<th>Distanz</th>
					<th>Prognose</th>
					<th class="small">Pace</th>
					<th class="small">VDOT</th>
					<th>Differenz</th>
					<th>Bestzeit</th>
					<th class="small">Pace</th>
					<th class="small">VDOT</th>
					<th class="small">Datum</th>
				</tr></thead><tbody>';
	}

	/**
	 * Fill result table with results
	 */
	protected function fillResultTableWithResults() {
		foreach ($this->Prognoses as $i => $Prognosis) {
			$this->ResultTable .= '
				<tr class="'.HTML::trClass($i).' r">
					<td class="c">'.$Prognosis['distance'].'</td>
					<td class="b">'.$Prognosis['prognosis'].'</td>
					<td class="small">'.$Prognosis['prognosis-pace'].'</td>
					<td class="small">'.$Prognosis['prognosis-vdot'].'</td>
					<td class="small '.$Prognosis['diff-class'].'">'.$Prognosis['diff'].'</td>
					<td class="b">'.$Prognosis['pb'].'</td>
					<td class="small">'.$Prognosis['pb-pace'].'</td>
					<td class="small">'.$Prognosis['pb-vdot'].'</td>
					<td class="small">'.$Prognosis['pb-date'].'</td>
				</tr>';
		}
	}

	/**
	 * Finish result table
	 */
	protected function finishResultTable() {
		$this->ResultTable .= '</tbody></table>';
	}

	/**
	 * Init fields
	 */
	protected function initFieldsets() {
		$this->initFieldsetForInputData();
		$this->initFieldsetForResults();
	}

	/**
	 * Init fieldset for input data
	 */
	protected function initFieldsetForInputData() {
		$this->FieldsetInput = new FormularFieldset('Eingabe');

		foreach ($this->InfoLines as $InfoMessage)
			$this->FieldsetInput->addInfo($InfoMessage);

		$FieldModel = new FormularSelectBox('model', 'Prognose-Modell');
		$FieldModel->addOption('jack-daniels', 'Jack Daniels (VDOT)');
		$FieldModel->addOption('robert-bock', 'Robert Bock (CPP)');
		$FieldModel->addOption('herbert-steffny', 'Herbert Steffny (simpel)');
		$FieldModel->addAttribute('onchange', '$(\'#prognosis-calculator .only-\'+$(this).val()).closest(\'div\').show();$(\'#prognosis-calculator .hide-on-model-change:not(.only-\'+$(this).val()+\')\').closest(\'div\').hide();');
		$FieldModel->setLayout( FormularFieldset::$LAYOUT_FIELD_W50_AS_W100 );

		$FieldDistances = new FormularInput('distances', Ajax::tooltip('Distanzen', 'Kommagetrennte Liste mit allen Distanzen, f&uuml;r die eine Prognose erstellt werden soll.'));
		$FieldDistances->setLayout( FormularFieldset::$LAYOUT_FIELD_W50_AS_W100 );
		$FieldDistances->setSize( FormularInput::$SIZE_FULL_INLINE );

		$this->FieldsetInput->addField($FieldModel);
		$this->FieldsetInput->addField($FieldDistances);

		$this->addFieldsForJackDaniels();
		$this->addFieldsForBockAndSteffny();
	}

	/**
	 * Add fields for jack daniels
	 */
	protected function addFieldsForJackDaniels() {
		$FieldVdot = new FormularInput('vdot', Ajax::tooltip('Neuer VDOT', 'Statt deinem eigentlichen VDOT-Wert wird dieser zur Berechnung herangezogen.'));
		$FieldVdot->setLayout( FormularFieldset::$LAYOUT_FIELD_W50_AS_W100 );
		$FieldVdot->addCSSclass('hide-on-model-change');
		$FieldVdot->addCSSclass('only-jack-daniels');

		$FieldEndurance = new FormularCheckbox('endurance', Ajax::tooltip('Grundlagenausdauer-Faktor', 'Mit dieser Einstellung wird auch deine berechnete Grundlagenausdauer in die Berechnungen einflie&szlig;en.'));
		$FieldEndurance->setLayout( FormularFieldset::$LAYOUT_FIELD_W50 );
		$FieldEndurance->addCSSclass('hide-on-model-change');
		$FieldEndurance->addCSSclass('only-jack-daniels');

		$FieldEnduranceValue = new FormularInput('endurance-value', Ajax::tooltip('Grundlagenausdauer-Wert', 'Statt deinem eigentlichen GA-Wert wird dieser zur Berechnung herangezogen. Auch Eingaben &ge; 100 &#37; sind m&ouml;glich.'));
		$FieldEnduranceValue->setLayout( FormularFieldset::$LAYOUT_FIELD_W50 );
		$FieldEnduranceValue->addCSSclass('hide-on-model-change');
		$FieldEnduranceValue->addCSSclass('only-jack-daniels');
		$FieldEnduranceValue->setUnit( FormularUnit::$PERCENT );

		$this->FieldsetInput->addField($FieldVdot);
		$this->FieldsetInput->addField($FieldEnduranceValue);
		$this->FieldsetInput->addField($FieldEndurance);
	}

	/**
	 * Add fields for robert bock and herbert steffny
	 */
	protected function addFieldsForBockAndSteffny() {
		$BestResult = new FormularInput('best-result-km', 'Bestes Ergebnis');
		$BestResult->setLayout( FormularFieldset::$LAYOUT_FIELD_W50 );
		$BestResult->addCSSclass('hide-on-model-change');
		$BestResult->addCSSclass('only-robert-bock');
		$BestResult->addCSSclass('only-herbert-steffny');
		$BestResult->setUnit( FormularUnit::$KM );

		$BestResultTime = new FormularInput('best-result-time', 'in');
		$BestResultTime->setLayout( FormularFieldset::$LAYOUT_FIELD_W50 );
		$BestResultTime->addCSSclass('hide-on-model-change');
		$BestResultTime->addCSSclass('only-robert-bock');
		$BestResultTime->addCSSclass('only-herbert-steffny');

		$SecondBestResult = new FormularInput('second-best-result-km', 'Zweitbestes Ergebnis');
		$SecondBestResult->setLayout( FormularFieldset::$LAYOUT_FIELD_W50 );
		$SecondBestResult->addCSSclass('hide-on-model-change');
		$SecondBestResult->addCSSclass('only-robert-bock');
		$SecondBestResult->setUnit( FormularUnit::$KM );

		$SecondBestResultTime = new FormularInput('second-best-result-time', 'in');
		$SecondBestResultTime->setLayout( FormularFieldset::$LAYOUT_FIELD_W50 );
		$SecondBestResultTime->addCSSclass('hide-on-model-change');
		$SecondBestResultTime->addCSSclass('only-robert-bock');

		$this->FieldsetInput->addField($BestResult);
		$this->FieldsetInput->addField($BestResultTime);
		$this->FieldsetInput->addField($SecondBestResult);
		$this->FieldsetInput->addField($SecondBestResultTime);
	}

	/**
	 * Init fieldset for results
	 */
	protected function initFieldsetForResults() {
		$this->FieldsetResult = new FormularFieldset('Prognose');
		$this->FieldsetResult->addBlock( $this->ResultTable );
	}

	/**
	 * Init formular
	 */
	protected function initFormular() {
		$this->Formular = new Formular();
		$this->Formular->setId('prognosis-calculator');
		$this->Formular->addCSSclass('ajax');
		$this->Formular->addCSSclass('no-automatic-reload');
		$this->Formular->addFieldset( $this->FieldsetInput );
		$this->Formular->addFieldset( $this->FieldsetResult );
		$this->Formular->addSubmitButton('Prognose anzeigen');
	}

	/**
	 * Display
	 */
	public function display() {
		$this->displayHeading();
		$this->displayFormular();
	}

	/**
	 * Display heading
	 */
	protected function displayHeading() {
		echo HTML::h1('Prognose-Rechner');
	}

	/**
	 * Display formular
	 */
	protected function displayFormular() {
		$this->Formular->display();

		echo Ajax::wrapJSasFunction('$(\'#prognosis-calculator .hide-on-model-change:not(.only-'.$_POST['model'].'\').closest(\'div\').hide();');
	}
}