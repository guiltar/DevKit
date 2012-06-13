<?php

/**
 * Model for phrases
 *
 * @package XenForo_Phrase
 */
class DevKit_Model_Phrase extends XFCP_DevKit_Model_Phrase
{
    public function getAllPhrasesInMasterLanguage()
    {
        return $this->fetchAllKeyed('
   			SELECT *, addon.title AS addonTitle,phrase.title AS title
   			FROM xf_phrase AS phrase
            LEFT JOIN xf_addon AS addon ON
                (addon.addon_id = phrase.addon_id)
   			WHERE language_id = 0
   			ORDER BY phrase.title
   		', 'title');
    }

    public function preparePhraseConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions[] = parent::preparePhraseConditions($conditions, $fetchOptions);
        $db = $this->_getDb();

        if (isset($conditions['addon_id'])) {
            $sqlConditions[] = 'phrase.addon_id = ' . $db->quote($conditions['addon_id']);
        }

        if (isset($conditions['phrase_filter'])) {
            switch ($conditions['phrase_filter']) {
                case 'untranslated':
                    $sqlConditions[] = 'phrase.language_id = 0';
                    break;
            }
        }

        return $this->getConditionsForClause($sqlConditions);
    }
}