<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Ernesto J. Rodriguez (Certun)
 * File: Encounter.php
 * Date: 1/21/12
 * Time: 3:26 PM
 */
/*
 GaiaEHR (Electronic Health Records)
 Medical.php
 Medical dataProvider
 Copyright (C) 2012 Ernesto J. Rodriguez (Certun)

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
if(!isset($_SESSION)) {
	session_name("GaiaEHR");
	session_start();
	session_cache_limiter('private');
}
include_once ($_SESSION['root'] . '/dataProvider/Patient.php');
include_once ($_SESSION['root'] . '/dataProvider/User.php');
include_once ($_SESSION['root'] . '/dataProvider/Laboratories.php');
include_once ($_SESSION['root'] . '/dataProvider/Medications.php');
include_once ($_SESSION['root'] . '/classes/dbHelper.php');
class Medical
{
	/**
	 * @var dbHelper
	 */
	private $db;
	/**
	 * @var User
	 */
	private $user;
	/**
	 * @var Patient
	 */
	private $patient;
	private $laboratories;
	private $medications;

	function __construct()
	{
		$this->db           = new dbHelper();
		$this->user         = new User();
		$this->patient      = new Patient();
		$this->laboratories = new Laboratories();
		$this->medications  = new Medications();
		return;
	}

	/*********************************************
	 * METHODS USED BY SENCHA                    *
	 *********************************************/
	/**
	 * @return mixed
	 */
	/*************************************************************************************************************/
	public function getImmunizationsList()
	{
		$sql = "SELECT * FROM codes WHERE code_type='100'";
		$this->db->setSQL($sql);
		return $this->db->fetchRecords(PDO::FETCH_ASSOC);
	}

	public function getPatientImmunizations(stdClass $params)
	{
		$immunizations = $this->getPatientImmunizationsByPid($params->pid);
		return $immunizations;
	}

	public function addPatientImmunization(stdClass $params)
	{
		$data = get_object_vars($params);
		unset($data['id'], $data['alert']);
		$data['administered_date'] = $this->parseDate($data['administered_date']);
		$data['education_date']    = $this->parseDate($data['education_date']);
		$data['create_date']       = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, 'patient_immunizations', 'I'));
		$this->db->execLog();
		$params->id = $this->db->lastInsertId;
		return $params;

	}

	public function updatePatientImmunization(stdClass $params)
	{
		$data = get_object_vars($params);
		$id   = $data['id'];
		unset($data['id'], $data['alert']);
		$data['administered_date'] = $this->parseDate($data['administered_date']);
		$data['education_date']    = $this->parseDate($data['education_date']);
		$data['create_date']       = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, 'patient_immunizations', 'U', array('id' => $id)));
		$this->db->execLog();
		return $params;

	}

	/*************************************************************************************************************/
	public function getPatientAllergies(stdClass $params)
	{
		return $this->getAllergiesByPatientID($params->pid);
	}

	public function addPatientAllergies(stdClass $params)
	{
		$data = get_object_vars($params);
		unset($data['id'], $data['allergy_name'], $data['alert'], $data['allergy1'], $data['allergy2'], $data['reaction1'], $data['reaction2'], $data['reaction3'], $data['reaction4']);
		$data['begin_date']  = $this->parseDate($data['begin_date']);
		$data['end_date']    = $this->parseDate($data['end_date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, 'patient_allergies', 'I'));
		$this->db->execLog();
		$params->id = $this->db->lastInsertId;
		return $params;
	}

	public function updatePatientAllergies(stdClass $params)
	{
		$data = get_object_vars($params);
		$id   = $data['id'];
		unset($data['id'], $data['allergy_name'], $data['alert'], $data['allergy1'], $data['allergy2'], $data['reaction1'], $data['reaction2'], $data['reaction3'], $data['reaction4']);
		if($params->allergy1 != '') {
			$data['allergy'] = $params->allergy1;
		} elseif($params->allergy2 != '') {
			$name               = $this->medications->getMedicationNameById($params->allergy2);
			$data['allergy']    = $name['PROPRIETARYNAME'];
			$data['allergy_id'] = $params->allergy2;
		}
		$params->allergy = $data['allergy'];
		if($params->reaction1 != '') {
			$data['reaction'] = $params->reaction1;
		} elseif($params->reaction2 != '') {
			$data['reaction'] = $params->reaction2;
		}
		elseif($params->reaction3 != '') {
			$data['reaction'] = $params->reaction3;
		}
		elseif($params->reaction4 != '') {
			$data['reaction'] = $params->reaction4;
		}
		$params->reaction    = $data['reaction'];
		$data['begin_date']  = $this->parseDate($data['begin_date']);
		$data['end_date']    = $this->parseDate($data['end_date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, "patient_allergies", "U", "id='$id'"));
		$this->db->execLog();
		$params->alert = ($params->end_date == null || $params->end_date == '0000-00-00 00:00:00' || $params->end_date == '') ? 1 : 0;
		return $params;

	}

	/*************************************************************************************************************/
	public function getMedicalIssues(stdClass $params)
	{
		return $this->getMedicalIssuesByPatientID($params->pid);
	}

	public function addMedicalIssues(stdClass $params)
	{
		$data = get_object_vars($params);
		unset($data['id'], $data['alert']);
		$data['begin_date']  = $this->parseDate($data['begin_date']);
		$data['end_date']    = $this->parseDate($data['end_date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, 'patient_active_problems', 'I'));
		$this->db->execLog();
		$params->id = $this->db->lastInsertId;
		return $params;
	}

	public function updateMedicalIssues(stdClass $params)
	{
		$data = get_object_vars($params);
		$id   = $data['id'];
		unset($data['id'], $data['alert']);
		$data['begin_date']  = $this->parseDate($data['begin_date']);
		$data['end_date']    = $this->parseDate($data['end_date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, "patient_active_problems", "U", "id='$id'"));
		$this->db->execLog();
		return $params;

	}

	/*************************************************************************************************************/
	public function getPatientSurgery(stdClass $params)
	{
		return $this->getPatientSurgeryByPatientID($params->pid);
	}

	public function addPatientSurgery(stdClass $params)
	{
		$data = get_object_vars($params);
		unset($data['id'], $data['alert']);
		$data['date']        = $this->parseDate($data['date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, 'patient_surgery', 'I'));
		$this->db->execLog();
		$params->id = $this->db->lastInsertId;
		return $params;
	}

	public function updatePatientSurgery(stdClass $params)
	{
		$data = get_object_vars($params);
		$id   = $data['id'];
		unset($data['id'], $data['alert']);
		$data['date']        = $this->parseDate($data['date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, "patient_surgery", "U", "id='$id'"));
		$this->db->execLog();
		return $params;

	}

	/*************************************************************************************************************/
	public function getPatientDental(stdClass $params)
	{
		return $this->getPatientDentalByPatientID($params->pid);
	}

	public function addPatientDental(stdClass $params)
	{
		$data = get_object_vars($params);
		unset($data['id'], $data['alert']);
		$data['begin_date']  = $this->parseDate($data['begin_date']);
		$data['end_date']    = $this->parseDate($data['end_date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, 'patient_dental', 'I'));
		$this->db->execLog();
		$params->id = $this->db->lastInsertId;
		return $params;
	}

	public function updatePatientDental(stdClass $params)
	{
		$data = get_object_vars($params);
		$id   = $data['id'];
		unset($data['id'], $data['alert']);
		$data['begin_date']  = $this->parseDate($data['begin_date']);
		$data['end_date']    = $this->parseDate($data['end_date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, "patient_dental", "U", "id='$id'"));
		$this->db->execLog();
		return $params;

	}

	/*************************************************************************************************************/
	public function getPatientMedications(stdClass $params)
	{
		return $this->getPatientMedicationsByPatientID($params->pid);
	}

	public function addPatientMedications(stdClass $params)
	{
		$data = get_object_vars($params);
		unset($data['id'], $data['alert']);
		$data['begin_date']  = $this->parseDate($data['begin_date']);
		$data['end_date']    = $this->parseDate($data['end_date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, 'patient_medications', 'I'));
		$this->db->execLog();
		$params->id = $this->db->lastInsertId;
		return $params;
	}

	public function updatePatientMedications(stdClass $params)
	{
		$data = get_object_vars($params);
		$id   = $data['id'];
		unset($data['id'], $data['alert']);
		$data['begin_date']  = $this->parseDate($data['begin_date']);
		$data['end_date']    = $this->parseDate($data['end_date']);
		$data['create_date'] = $this->parseDate($data['create_date']);
		$this->db->setSQL($this->db->sqlBind($data, "patient_medications", "U", "id='$id'"));
		$this->db->execLog();
		return $params;

	}

	/*************************************************************************************************************/
	public function getMedicationLiveSearch(stdClass $params)
	{
		$this->db->setSQL("SELECT id,
								  PROPRIETARYNAME,
								  PRODUCTNDC,
								  NONPROPRIETARYNAME,
								  ACTIVE_NUMERATOR_STRENGTH,
								  ACTIVE_INGRED_UNIT
                           	 FROM medications
                            WHERE PROPRIETARYNAME LIKE '$params->query%'
                               OR NONPROPRIETARYNAME LIKE '$params->query%'");
		$records = $this->db->fetchRecords(PDO::FETCH_ASSOC);
		$total   = count($records);
		$records = array_slice($records, $params->start, $params->limit);
		return array(
			'totals' => $total,
			'rows'   => $records
		);
	}

	/***************************************************************************************************************/
	public function getPatientLabsResults(stdClass $params)
	{
		$records = array();
		$this->db->setSQL("SELECT pLab.*, pDoc.url AS document_url
							 FROM patient_labs AS pLab
						LEFT JOIN patient_documents AS pDoc ON pLab.document_id = pDoc.id
							WHERE pLab.parent_id = '$params->parent_id'
						 ORDER BY date DESC");
		$labs = $this->db->fetchRecords(PDO::FETCH_ASSOC);
		foreach($labs as $lab) {
			$id = $lab['id'];
			$this->db->setSQL("SELECT observation_loinc, observation_value, unit
							     FROM patient_labs_results
							    WHERE patient_lab_id = '$id'");
			$lab['columns'] = $this->db->fetchRecords(PDO::FETCH_ASSOC);
			$lab['data']    = array();
			foreach($lab['columns'] as $column) {
				$lab['data'][$column['observation_loinc']]           = $column['observation_value'];
				$lab['data'][$column['observation_loinc'] . '_unit'] = $column['unit'];
			}
			$records[] = $lab;
		}
		return $records;
	}

	public function addPatientLabsResult(stdClass $params)
	{
		$lab['pid']         = (isset($params->pid)) ? $params->pid : $_SESSION['patient']['pid'];
		$lab['uid']         = $_SESSION['user']['id'];
		$lab['document_id'] = $params->document_id;
		$lab['date']        = date('Y-m-d H:i:s');
		$lab['parent_id']   = $params->parent_id;
		$this->db->setSQL($this->db->sqlBind($lab, 'patient_labs', 'I'));
		$this->db->execLog();
		$patient_lab_id = $this->db->lastInsertId;
		foreach($this->laboratories->getLabObservationFieldsByParentId($params->parent_id) as $result) {
			$foo                      = array();
			$foo['patient_lab_id']    = $patient_lab_id;
			$foo['observation_loinc'] = $result->loinc_number;
			$foo['observation_value'] = null;
			$foo['unit']              = $result->default_unit;
			$this->db->setSQL($this->db->sqlBind($foo, 'patient_labs_results', 'I'));
			$this->db->execOnly();
		}
		return $params;
	}

	public function updatePatientLabsResult(stdClass $params)
	{
		$data = get_object_vars($params);
		$id   = $data['id'];
		unset($data['id']);
		foreach($data as $key => $val) {
			$foo = explode('_', $key);
			if(sizeof($foo) == 1) {
				$observationValue = $val;
			} else {
				$this->db->setSQL("UPDATE patient_labs_results
									  SET observation_value = '$observationValue',
									      unit = '$val'
								    WHERE patient_lab_id = '$id'
								      AND observation_loinc = '$foo[0]'");
				$this->db->execLog();
			}
		}
		return $params;
	}

	public function deletePatientLabsResult(stdClass $params)
	{
		return $params;
	}

	public function signPatientLabsResultById($id)
	{
		$foo['auth_uid'] = $_SESSION['user']['id'];
		$this->db->setSQL($this->db->sqlBind($foo, 'patient_labs', 'U', "id = '$id'"));
		$this->db->execLog();
		return array('success' => true);
	}

	/*********************************************
	 * METHODS USED BY PHP                       *
	 *********************************************/
	/**
	 * @param $pid
	 * @return array
	 */
	public function getPatientImmunizationsByPid($pid)
	{
		$this->db->setSQL("SELECT * FROM patient_immunizations WHERE pid='$pid'");
		return $this->db->fetchRecords(PDO::FETCH_ASSOC);
	}

	/**
	 * @param $eid
	 * @return array
	 */
	public function getImmunizationsByEncounterID($eid)
	{
		$this->db->setSQL("SELECT * FROM patient_immunizations WHERE eid='$eid'");
		return $this->db->fetchRecords(PDO::FETCH_ASSOC);
	}

	/**
	 * @param $pid
	 * @return array
	 */
	private function getAllergiesByPatientID($pid)
	{
		$this->db->setSQL("SELECT * FROM patient_allergies WHERE pid='$pid'");
		$records = array();
		foreach($this->db->fetchRecords(PDO::FETCH_ASSOC) as $rec) {
			$rec['alert'] = ($rec['end_date'] == null || $rec['end_date'] == '0000-00-00 00:00:00') ? 1 : 0;
			$records[]    = $rec;
		}
		return $records;
	}

	/**
	 * @param $eid
	 * @return array
	 */
	public function getAllergiesByEncounterID($eid)
	{
		$this->db->setSQL("SELECT * FROM patient_allergies WHERE eid='$eid'");
		return $this->db->fetchRecords(PDO::FETCH_ASSOC);
	}

	/**
	 * @param $pid
	 * @return array
	 */
	private function getMedicalIssuesByPatientID($pid)
	{
		$this->db->setSQL("SELECT * FROM patient_active_problems WHERE pid='$pid'");
		$records = array();
		foreach($this->db->fetchRecords(PDO::FETCH_ASSOC) as $rec) {
			$rec['alert'] = ($rec['end_date'] == null || $rec['end_date'] == '0000-00-00 00:00:00') ? 1 : 0;
			$records[] = $rec;
		}
		return $records;
	}

	/**
	 * @param $eid
	 * @return array
	 */
	public function getMedicalIssuesByEncounterID($eid)
	{
		$this->db->setSQL("SELECT * FROM patient_active_problems WHERE eid = '$eid'");
		return $this->db->fetchRecords(PDO::FETCH_ASSOC);
	}

	public function getPatientProblemsByPid($pid)
	{
		$this->db->setSQL("SELECT * FROM patient_active_problems WHERE pid = '$pid'");
		return $this->db->fetchRecords(PDO::FETCH_ASSOC);
	}

	/**
	 * @param $pid
	 * @return array
	 */
	private function getPatientSurgeryByPatientID($pid)
	{
		$this->db->setSQL("SELECT * FROM patient_surgery WHERE pid='$pid'");
		$records = array();
		foreach($this->db->fetchRecords(PDO::FETCH_ASSOC) as $rec) {
			$rec['alert'] = ($rec['end_date'] == null || $rec['end_date'] == '0000-00-00 00:00:00') ? 1 : 0;
			$records[]    = $rec;
		}
		return $records;
	}

	/**
	 * @param $eid
	 * @return array
	 */
	public function getPatientSurgeryByEncounterID($eid)
	{
		$this->db->setSQL("SELECT * FROM patient_surgery WHERE eid='$eid'");
		return $this->db->fetchRecords(PDO::FETCH_ASSOC);
	}

	/**
	 * @param $pid
	 * @return array
	 */
	private function getPatientDentalByPatientID($pid)
	{
		$this->db->setSQL("SELECT * FROM patient_dental WHERE pid='$pid'");
		$records = array();
		foreach($this->db->fetchRecords(PDO::FETCH_ASSOC) as $rec) {
			$rec['alert'] = ($rec['end_date'] == null || $rec['end_date'] == '0000-00-00 00:00:00') ? 1 : 0;
			$records[]    = $rec;
		}
		return $records;
	}

	public function getSurgeriesLiveSearch(stdClass $params)
	{
		$this->db->setSQL("SELECT *
   							FROM  surgeries
   							WHERE surgery      LIKE'$params->query%'
   							  OR type         LIKE'$params->query%'");
		$records = $this->db->fetchRecords(PDO::FETCH_ASSOC);
		$total   = count($records);
		$records = array_slice($records, $params->start, $params->limit);
		return array(
			'totals' => $total,
			'rows'   => $records
		);
	}

	public function getCDTLiveSearch(stdClass $params)
	{
		$this->db->setSQL("SELECT *
   							FROM  cdt_codes
   							WHERE text      LIKE'$params->query%'
   							  OR code         LIKE'$params->query%'");
		$records = $this->db->fetchRecords(PDO::FETCH_ASSOC);
		$total   = count($records);
		$records = array_slice($records, $params->start, $params->limit);
		return array(
			'totals' => $total,
			'rows'   => $records
		);
	}

	public function getRXNORMLiveSearch(stdClass $params)
	{
		$this->db->setSQL("SELECT *
   							   FROM  rxnconso
   							   WHERE RXCUI    	LIKE'$params->query%'
   							   OR STR         	LIKE'$params->query%'
   							   GROUP BY RXCUI");
		$records = $this->db->fetchRecords(PDO::FETCH_ASSOC);
		$total   = count($records);
		$records = array_slice($records, $params->start, $params->limit);
		return array(
			'totals' => $total,
			'rows'   => $records
		);
	}

	public function reviewAllMedicalWindowEncounter(stdClass $params)
	{
		$data = get_object_vars($params);
		$eid  = $data['eid'];
		unset($data['eid']);
		$data['review_immunizations']   = 1;
		$data['review_allergies']       = 1;
		$data['review_active_problems'] = 1;
		$data['review_surgery']         = 1;
		$data['review_medications']     = 1;
		$data['review_dental']          = 1;
		$this->db->setSQL($this->db->sqlBind($data, 'encounters', 'U', array('eid' => $eid)));
		$this->db->execLog();
		return array('success' => true);
	}

	public function getEncounterReviewByEid($eid)
	{
		$this->db->setSQL("SELECT review_alcohol,
                                      review_smoke,
                                      review_pregnant
                            	 FROM encounters
                            	WHERE eid = '$eid'");
		return $this->db->fetchRecord();
	}

	/**
	 * @param $eid
	 * @return array
	 */
	public function getPatientDentalByEncounterID($eid)
	{
		$this->db->setSQL("SELECT * FROM patient_dental WHERE eid='$eid'");
		return $this->db->fetchRecords(PDO::FETCH_ASSOC);
	}

	/**
	 * @param $pid
	 * @return array
	 */
	public function getPatientMedicationsByPatientID($pid)
	{
		$this->db->setSQL("SELECT * FROM patient_medications WHERE pid='$pid'");
		$records = array();
		foreach($this->db->fetchRecords(PDO::FETCH_ASSOC) as $rec) {
			$date1        = strtotime(date('Y-m-d'));
			$date2        = strtotime($rec['end_date']);
			$rec['alert'] = (($date2 > $date1) || $rec['end_date'] == null || $rec['end_date'] == '') ? 1 : 0;
			$records[]    = $rec;
		}
		return $records;
	}

	/**
	 * @param $eid
	 * @return array
	 */
	public function getPatientMedicationsByEncounterID($eid)
	{
		$this->db->setSQL("SELECT * FROM patient_medications WHERE eid='$eid'");
		return $this->db->fetchRecords(PDO::FETCH_ASSOC);
	}

	//******************************************************************************************************************
	public function reviewMedicalWindowEncounter(stdClass $params)
	{
		$data = get_object_vars($params);
		$eid  = $data['eid'];
		$area = $data['area'];
		unset($data['area'], $data['eid']);
		$data[$area] = 1;
		$this->db->setSQL($this->db->sqlBind($data, 'encounters', 'U', array('eid' => $eid)));
		$this->db->execLog();
		return array('success' => true);
	}

	/*************************************************************************************************************/
	public function getLabsLiveSearch(stdClass $params)
	{
		$this->db->setSQL("SELECT id,
								  parent_loinc,
								  loinc_number,
								  loinc_name
							FROM  labs_panels
							WHERE parent_loinc <> loinc_number
							  AND loinc_name      LIKE'$params->query%'");
		$records = $this->db->fetchRecords(PDO::FETCH_ASSOC);
		$total   = count($records);
		$records = array_slice($records, $params->start, $params->limit);
		return array(
			'totals' => $total,
			'rows'   => $records
		);
	}

	/**
	 * @param $date
	 * @return mixed
	 */
	public function parseDate($date)
	{
		return str_replace('T', ' ', $date);
	}

}

//
//$e = new Medical();
//echo '<pre>';
//print_r($e->getPatientMedicationsByPatientID(1));
