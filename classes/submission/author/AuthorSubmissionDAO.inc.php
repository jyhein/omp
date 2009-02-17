<?php

/**
 * @file classes/submission/author/AuthorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmissionDAO
 * @ingroup submission
 * @see AuthorSubmission
 *
 * @brief Operations for retrieving and modifying AuthorSubmission objects.
 */

// $Id$


import('submission.author.AuthorSubmission');

class AuthorSubmissionDAO extends DAO {
	var $monographDao;
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $monographFileDao;
	var $suppFileDao;
	var $copyeditorSubmissionDao;
	var $monographCommentDao;
	var $layoutAssignmentDao;
	var $proofAssignmentDao;
	var $galleyDao;

	/**
	 * Constructor.
	 */
	function AuthorSubmissionDAO() {
		parent::DAO();
		$this->monographDao =& DAORegistry::getDAO('MonographDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$this->monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
//		$this->copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
//		$this->monographCommentDao =& DAORegistry::getDAO('MonographCommentDAO');
//		$this->layoutAssignmentDao =& DAORegistry::getDAO('LayoutAssignmentDAO');
//		$this->proofAssignmentDao =& DAORegistry::getDAO('ProofAssignmentDAO');
//		$this->galleyDao =& DAORegistry::getDAO('MonographGalleyDAO');
	}

	/**
	 * Retrieve a author submission by monograph ID.
	 * @param $monographId int
	 * @return AuthorSubmission
	 */
	function &getAuthorSubmission($monographId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$result =& $this->retrieve(
			'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				c.copyed_id, c.copyeditor_id, c.date_notified AS copyeditor_date_notified, c.date_underway AS copyeditor_date_underway, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS ce_date_author_notified, c.date_author_underway AS ce_date_author_underway, c.date_author_completed AS ce_date_author_completed,
				c.date_author_acknowledged AS ce_date_author_acknowledged, c.date_final_notified AS ce_date_final_notified, c.date_final_underway AS ce_date_final_underway, c.date_final_completed AS ce_date_final_completed, c.date_final_acknowledged AS ce_date_final_acknowledged, c.initial_revision AS copyeditor_initial_revision, c.editor_author_revision AS ce_editor_author_revision,
				c.final_revision AS copyeditor_final_revision
			FROM monographs a
				LEFT JOIN acquisitions_arrangements s ON (s.arrangement_id = a.arrangement_id)
				LEFT JOIN copyed_assignments c on (a.monograph_id = c.monograph_id)
				LEFT JOIN acquisitions_arrangements_settings stpl ON (s.arrangement_id = stpl.arrangement_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings stl ON (s.arrangement_id = stl.arrangement_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings sapl ON (s.arrangement_id = sapl.arrangement_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings sal ON (s.arrangement_id = sal.arrangement_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.monograph_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$monographId,
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnAuthorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a AuthorSubmission object from a row.
	 * @param $row array
	 * @return AuthorSubmission
	 */
	function &_returnAuthorSubmissionFromRow(&$row) {
		$authorSubmission = new AuthorSubmission();

		// Monograph attributes
		$this->monographDao->_monographFromRow($authorSubmission, $row);

		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByMonographId($row['monograph_id']);
		$authorSubmission->setEditAssignments($editAssignments->toArray());

		// Editor Decisions
		for ($i = 1; $i <= $row['current_round']; $i++) {
//			$authorSubmission->setDecisions($this->getEditorDecisions($row['monograph_id'], $i), $i);
		}

		// Review Assignments
		for ($i = 1; $i <= $row['current_round']; $i++) {
//			$authorSubmission->setReviewAssignments($this->reviewAssignmentDao->getReviewAssignmentsByMonographId($row['monograph_id'], $i), $i);
		}

		// Comments
/*		$authorSubmission->setMostRecentEditorDecisionComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_EDITOR_DECISION, $row['monograph_id']));
		$authorSubmission->setMostRecentCopyeditComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_COPYEDIT, $row['monograph_id']));
		$authorSubmission->setMostRecentProofreadComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_PROOFREAD, $row['monograph_id']));
		$authorSubmission->setMostRecentLayoutComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_LAYOUT, $row['monograph_id']));
*/
		// Files
		$authorSubmission->setSubmissionFile($this->monographFileDao->getMonographFile($row['submission_file_id']));
/*		$authorSubmission->setRevisedFile($this->monographFileDao->getMonographFile($row['revised_file_id']));
		$authorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByMonograph($row['monograph_id']));
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setAuthorFileRevisions($this->monographFileDao->getMonographFileRevisions($row['revised_file_id'], $i), $i);
		}
		for ($i = 1; $i <= $row['current_round']; $i++) {
			$authorSubmission->setEditorFileRevisions($this->monographFileDao->getMonographFileRevisions($row['editor_file_id'], $i), $i);
		}
		$authorSubmission->setGalleys($this->galleyDao->getGalleysByMonograph($row['monograph_id']));

		// Initial Copyedit File
		if ($row['copyeditor_initial_revision'] != null) {
			$authorSubmission->setInitialCopyeditFile($this->monographFileDao->getMonographFile($row['copyedit_file_id'], $row['copyeditor_initial_revision']));
		}

		// Editor / Author Copyedit File
		if ($row['ce_editor_author_revision'] != null) {
			$authorSubmission->setEditorAuthorCopyeditFile($this->monographFileDao->getMonographFile($row['copyedit_file_id'], $row['ce_editor_author_revision']));
		}

		// Final Copyedit File
		if ($row['copyeditor_final_revision'] != null) {
			$authorSubmission->setFinalCopyeditFile($this->monographFileDao->getMonographFile($row['copyedit_file_id'], $row['copyeditor_final_revision']));
		}

		// Copyeditor Assignment
		$authorSubmission->setCopyedId($row['copyed_id']);
		$authorSubmission->setCopyeditorId($row['copyeditor_id']);
		$authorSubmission->setCopyeditor($this->userDao->getUser($row['copyeditor_id']), true);
		$authorSubmission->setCopyeditorDateNotified($this->datetimeFromDB($row['copyeditor_date_notified']));
		$authorSubmission->setCopyeditorDateUnderway($this->datetimeFromDB($row['copyeditor_date_underway']));
		$authorSubmission->setCopyeditorDateCompleted($this->datetimeFromDB($row['copyeditor_date_completed']));
		$authorSubmission->setCopyeditorDateAcknowledged($this->datetimeFromDB($row['copyeditor_date_acknowledged']));
		$authorSubmission->setCopyeditorDateAuthorNotified($this->datetimeFromDB($row['ce_date_author_notified']));
		$authorSubmission->setCopyeditorDateAuthorUnderway($this->datetimeFromDB($row['ce_date_author_underway']));
		$authorSubmission->setCopyeditorDateAuthorCompleted($this->datetimeFromDB($row['ce_date_author_completed']));
		$authorSubmission->setCopyeditorDateAuthorAcknowledged($this->datetimeFromDB($row['ce_date_author_acknowledged']));
		$authorSubmission->setCopyeditorDateFinalNotified($this->datetimeFromDB($row['ce_date_final_notified']));
		$authorSubmission->setCopyeditorDateFinalUnderway($this->datetimeFromDB($row['ce_date_final_underway']));
		$authorSubmission->setCopyeditorDateFinalCompleted($this->datetimeFromDB($row['ce_date_final_completed']));
		$authorSubmission->setCopyeditorDateFinalAcknowledged($this->datetimeFromDB($row['ce_date_final_acknowledged']));
		$authorSubmission->setCopyeditorInitialRevision($row['copyeditor_initial_revision']);
		$authorSubmission->setCopyeditorEditorAuthorRevision($row['ce_editor_author_revision']);
		$authorSubmission->setCopyeditorFinalRevision($row['copyeditor_final_revision']);

		// Layout Assignment
		$authorSubmission->setLayoutAssignment($this->layoutAssignmentDao->getLayoutAssignmentByMonographId($row['monograph_id']));

		// Proof Assignment
		$authorSubmission->setProofAssignment($this->proofAssignmentDao->getProofAssignmentByMonographId($row['monograph_id']));

		HookRegistry::call('AuthorSubmissionDAO::_returnAuthorSubmissionFromRow', array(&$authorSubmission, &$row));
*/
		return $authorSubmission;
	}

	/**
	 * Update an existing author submission.
	 * @param $authorSubmission AuthorSubmission
	 */
	function updateAuthorSubmission(&$authorSubmission) {
		// Update monograph
		if ($authorSubmission->getMonographId()) {
			$monograph =& $this->monographDao->getMonograph($authorSubmission->getMonographId());

			// Only update fields that an author can actually edit.
			$monograph->setRevisedFileId($authorSubmission->getRevisedFileId());
			$monograph->setDateStatusModified($authorSubmission->getDateStatusModified());
			$monograph->setLastModified($authorSubmission->getLastModified());
			// FIXME: These two are necessary for designating the
			// original as the review version, but they are probably
			// best not exposed like this.
			$monograph->setReviewFileId($authorSubmission->getReviewFileId());
			$monograph->setEditorFileId($authorSubmission->getEditorFileId());

			$this->monographDao->updateMonograph($monograph);
		}


		// Update copyeditor assignment
		if ($authorSubmission->getCopyedId()) {
			$copyeditorSubmission =& $this->copyeditorSubmissionDao->getCopyeditorSubmission($authorSubmission->getMonographId());

			// Only update fields that an author can actually edit.
			$copyeditorSubmission->setDateAuthorUnderway($authorSubmission->getCopyeditorDateAuthorUnderway());
			$copyeditorSubmission->setDateAuthorCompleted($authorSubmission->getCopyeditorDateAuthorCompleted());
			$copyeditorSubmission->setDateFinalNotified($authorSubmission->getCopyeditorDateFinalNotified());
			$copyeditorSubmission->setEditorAuthorRevision($authorSubmission->getCopyeditorEditorAuthorRevision());
			$copyeditorSubmission->setDateStatusModified($authorSubmission->getDateStatusModified());
			$copyeditorSubmission->setLastModified($authorSubmission->getLastModified());

			$this->copyeditorSubmissionDao->updateCopyeditorSubmission($copyeditorSubmission);
		}
	}

	/**
	 * Get all author submissions for an author.
	 * @param $authorId int
	 * @return DAOResultFactory continaing AuthorSubmissions
	 */
	function &getAuthorSubmissions($authorId, $pressId, $active = true, $rangeInfo = null) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$result =& $this->retrieveRange(
			'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				c.copyed_id, c.copyeditor_id, c.date_notified AS copyeditor_date_notified, c.date_underway AS copyeditor_date_underway, c.date_completed AS copyeditor_date_completed, c.date_acknowledged AS copyeditor_date_acknowledged, c.date_author_notified AS ce_date_author_notified, c.date_author_underway AS ce_date_author_underway, c.date_author_completed AS ce_date_author_completed,
				c.date_author_acknowledged AS ce_date_author_acknowledged, c.date_final_notified AS ce_date_final_notified, c.date_final_underway AS ce_date_final_underway, c.date_final_completed AS ce_date_final_completed, c.date_final_acknowledged AS ce_date_final_acknowledged, c.initial_revision AS copyeditor_initial_revision, c.editor_author_revision AS ce_editor_author_revision,
				c.final_revision AS copyeditor_final_revision
			FROM monographs a
				LEFT JOIN acquisitions_arrangements s ON (s.arrangement_id = a.arrangement_id)
				LEFT JOIN copyed_assignments c on (a.monograph_id = c.monograph_id)
				LEFT JOIN acquisitions_arrangements_settings stpl ON (s.arrangement_id = stpl.arrangement_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings stl ON (s.arrangement_id = stl.arrangement_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings sapl ON (s.arrangement_id = sapl.arrangement_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings sal ON (s.arrangement_id = sal.arrangement_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.user_id = ? AND a.press_id = ? AND ' .
			($active?'a.status = 1':'(a.status <> 1 AND a.submission_progress = 0)'),
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$authorId,
				$pressId
			),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAuthorSubmissionFromRow');
		return $returner;
	}

	//
	// Miscellaneous
	//

	/**
	 * Get the editor decisions for a review round of an monograph.
	 * @param $monographId int
	 * @param $round int
	 */
	function getEditorDecisions($monographId, $round = null) {
		$decisions = array();

		if ($round == null) {
			$result =& $this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE monograph_id = ? ORDER BY date_decided ASC', $monographId
			);
		} else {
			$result =& $this->retrieve(
				'SELECT edit_decision_id, editor_id, decision, date_decided FROM edit_decisions WHERE monograph_id = ? AND round = ? ORDER BY date_decided ASC',
				array($monographId, $round)
			);
		}

		while (!$result->EOF) {
			$decisions[] = array(
				'editDecisionId' => $result->fields['edit_decision_id'],
				'editorId' => $result->fields['editor_id'],
				'decision' => $result->fields['decision'],
				'dateDecided' => $this->datetimeFromDB($result->fields['date_decided'])
			);
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $decisions;
	}

	/**
	 * Get count of active and complete assignments
	 * @param authorId int
	 * @param pressId int
	 */
	function getSubmissionsCount($authorId, $pressId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = 'SELECT count(*), status FROM monographs a LEFT JOIN acquisitions_arrangements s ON (s.arrangement_id = a.arrangement_id) LEFT JOIN copyed_assignments c on (a.monograph_id = c.monograph_id) WHERE a.press_id = ? AND a.user_id = ? GROUP BY a.status';

		$result =& $this->retrieve($sql, array($pressId, $authorId));

		while (!$result->EOF) {
			if ($result->fields['status'] != 1) {
				$submissionsCount[1] += $result->fields[0];
			} else {
				$submissionsCount[0] += $result->fields[0];
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $submissionsCount;
	}
}

?>
