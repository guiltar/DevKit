<?php

class DevKit_ControllerAdmin_Language extends XFCP_DevKit_ControllerAdmin_Language
{

    /**
     * Phrase index. This is a list of phrases, so redirect this to a
     * language-specific list.
     *
     * @return XenForo_ControllerResponse_Redirect
     */
    public function actionPhrases()
    {
        $languageModel = $this->_getLanguageModel();
        $addOnModel = $this->_getAddOnModel();

        $languageId = $this->_input->filterSingle('language_id', XenForo_Input::STRING);
        $addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
        $phraseFilter = $this->_input->filterSingle('phrase_filter', XenForo_Input::STRING);

        if ($addOnId == '') {
            $addOnId = (XenForo_Helper_Cookie::getCookie('edit_addon_id')) ? (XenForo_Helper_Cookie::getCookie('edit_addon_id')) : 'All';
        }

        if ($languageId == '') {
            $languageId = $languageModel->getLanguageIdFromCookie();
        }

        if ($phraseFilter == '') {
            $phraseFilter = (XenForo_Helper_Cookie::getCookie('phrase_filter')) ? (XenForo_Helper_Cookie::getCookie('phrase_filter')) : 'existing';
        }

        if (($languageId && in_array($phraseFilter, array('found', 'undefined'))) || !$languageId && in_array($phraseFilter, array('untranslated'))) {
            $phraseFilter = 'existing';
        }

        $phraseModel = $this->_getPhraseModel();

        if (!$phraseModel->canModifyPhraseInLanguage($languageId)) {
            return $this->responseError(new XenForo_Phrase('phrases_in_this_language_can_not_be_modified'));
        }

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $perPage = 100;

        $conditions = array();
        if ($addOnId != 'All') $conditions['addon_id'] = $addOnId;
        $conditions['phrase_filter'] = $phraseFilter;

        $filter = $this->_input->filterSingle('_filter', XenForo_Input::ARRAY_SIMPLE);
        if ($filter && isset($filter['value'])) {
            $conditions['title'] = array($filter['value'], empty($filter['prefix']) ? 'lr' : 'r');
            $filterView = true;
        }
        else
        {
            $filterView = false;
        }

        $fetchOptions = array(
            'page' => $page,
            'perPage' => $perPage
        );

        if ($phraseFilter == 'undefined' or $phraseFilter == 'found') {
            $allPhrases = $phraseModel->getAllPhrasesInMasterLanguage();
            //$limitOptions = $phraseModel->prepareLimitFetchOptions($fetchOptions);
            $phrases = array();
            $foundPhrases = $this->_getUndefinedPhrases($addOnId);
            $foundPhrases = array_unique($foundPhrases);
            sort($foundPhrases);


            //$limitedFoundPhrases = $foundPhrases;

            foreach ($foundPhrases as $phraseTitle) {
                $phrase = isset($allPhrases[$phraseTitle]) ? $allPhrases[$phraseTitle] : array('title' => $phraseTitle, 'phrase_state' => 'custom');
                if (($phraseFilter == 'found') || ($phraseFilter == 'undefined' && !isset($allPhrases[$phraseTitle]))) {
                    $phrases[] = $phrase;
                }
            }
            $totalPhrases = count($phrases);
            $page = null;
            $perPage = null; //die(Zend_Debug::dump($phrases));
        }
        else
        {
            $phrases = $phraseModel->getEffectivePhraseListForLanguage($languageId, $conditions, $fetchOptions);
            $totalPhrases = $phraseModel->countEffectivePhrasesInLanguage($languageId, $conditions, $fetchOptions);
        }

        if (is_array($phrases)) {
            // set an edit_language_id cookie so we can switch to another area and maintain the current style selection
            XenForo_Helper_Cookie::setCookie('edit_language_id', $languageId);
            XenForo_Helper_Cookie::setCookie('edit_addon_id', $addOnId);
            XenForo_Helper_Cookie::setCookie('phrase_filter', $phraseFilter);
        }
        else {
            XenForo_Helper_Cookie::setCookie('edit_language_id', 0);
            XenForo_Helper_Cookie::setCookie('edit_addon_id', 'All');
            XenForo_Helper_Cookie::setCookie('phrase_filter', 'existing');
        }


        $viewParams = array(
            'phrases' => $phrases,
            'languages' => $languageModel->getAllLanguagesAsFlattenedTree($languageModel->showMasterLanguage() ? 1 : 0),
            'masterLanguage' => $languageModel->showMasterLanguage() ? $languageModel->getLanguageById(0, true) : array(),
            'language' => $languageModel->getLanguageById($languageId, true),

            'addOns' => $addOnModel->getAllAddOns(),
            'addOn' => ($addOnId=='XenForo') ? array('addon_id'=>'XenForo', 'title'=> 'XenForo') : $addOnModel->getAddOnById($addOnId),
            'addOnId' => $addOnId,

            'phraseFilter' => $phraseFilter,
            'phraseFilterTitle' => new XenForo_Phrase('devkit_' . $phraseFilter),

            'page' => $page,
            'perPage' => $perPage,
            'totalPhrases' => $totalPhrases,

            'filterView' => $filterView,
            'filterMore' => ($filterView && $totalPhrases > $perPage)
        );
        return $this->responseView('XenForo_ViewAdmin_Phrase_List', 'phrase_list', $viewParams);
    }

    protected function _getUndefinedPhrases($addOnId)
    {
        $phrases = array();

        $phrases = $this->_getPhrasesFromFiles($addOnId, $phrases);
        $phrases = $this->_getPhrasesFromDB($addOnId, $phrases);

        return $phrases;
    }

    protected function _getPhrasesFromDB($addOnId, array &$phrases)
    {
	    $tms = $this->_getAddOnModel()->getAddOnById('TMS');
	    $modifications = array();
        if($addOnId=='All')
        {
            $publicTemplates = $this->_getTemplateModel()->getAllTemplatesInStyle(0);
            $adminTemplates = $this->_getAdminTemplateModel()->getAllAdminTemplates();
	        if(!empty($tms['active'])){
		        $modifications = XenForo_Model::create('TMS_Model_Modification')->getAllModifications();
	        }
        }
        else
        {
            $publicTemplates = $this->_getTemplateModel()->getMasterTemplatesInAddOn($addOnId);
            $adminTemplates = $this->_getAdminTemplateModel()->getAdminTemplatesByAddOn($addOnId);
	        if(!empty($tms['active'])){
		        $modifications = XenForo_Model::create('TMS_Model_Modification')->getModificationsByAddOn($addOnId);
	        }
        }

	    foreach($modifications as &$modification)
	    {
		    $modification['template'] = $modification['replace_value'];
	    }

        $this->_findPhrasesInTemplates($publicTemplates, $phrases);
        $this->_findPhrasesInTemplates($adminTemplates, $phrases);
	    $this->_findPhrasesInTemplates($modifications, $phrases);

        return $phrases;
    }

    protected function _findPhrasesInTemplates($templates, array &$phrases)
    {
        foreach ($templates as $template) {
            $code = $template['template'];
            $result = preg_match_all('/\{xen\s*:\s*phrase[\s|\']*[\w]*[\s|\']*[\}|\,]/', $code, $found);
            //print_r($result);
            foreach ($found[0] as &$str) {
                $str = preg_replace('/\{xen\s*:\s*phrase[\s|\']*([\w]*)[\s|\']*[\}|\,]/', '$1', $str);
                $phrases[] = $str;
            }
            ;
        }
    }

    protected function _getPhrasesFromFiles($addOnId, array &$phrases)
    {
        $addOnDir = $this->_getAddOnDir($addOnId);

        if (!$addOnDir) {
            throw new XenForo_Exception(new XenForo_Phrase('devkit_addon_directory_not_found', array('dirName' => str_replace('_', '', $addOnId))));
        }
        $filePaths = array();
        $this->_scanDir($addOnDir, $filePaths);

        foreach ($filePaths as $filePath) {
            $code = file_get_contents($filePath);
            $result = preg_match_all('/XenForo_Phrase\s*\(\s*\'[\w]*\'\s*[\)|\,]/', $code, $found);
            foreach ($found[0] as &$str) {
                $str = preg_replace('/XenForo_Phrase\s*\(\s*\'([\w]*)\'\s*[\)|\,]/', '$1', $str);
                $phrases[] = $str;
            }

        }
        return $phrases;
    }

    public function _getAddOnDir($addOnId)
    {
        $dirName = strtolower(str_replace('_', '', $addOnId));
        $libraryDir = XenForo_Application::getInstance()->getRootDir() . '/library';

        if($addOnId=='All')
        {
            return $libraryDir;
        }

        foreach (scandir($libraryDir) as $filename1) {
            if (!is_dir($libraryDir . '/' . $filename1) || in_array($filename1, array('.', '..'))) {
                continue;
            }
            elseif (strtolower($filename1) == $dirName) {
                return $libraryDir . '/' . $filename1;
            }
            else {
                foreach (scandir($libraryDir . '/' . $filename1) as $filename2) {
                    if (!is_dir($libraryDir . '/' . $filename1 . '/' . $filename2) || in_array($filename2, array('.', '..'))) {
                        continue;
                    }
                    elseif ((strtolower($filename2) == $dirName) || (stripos($dirName, $filename1) !== false && stripos($dirName, $filename2) !== false)) {
                        return $libraryDir . '/' . $filename1 . '/' . $filename2;
                    }
                }
            }
        }

        return null;
    }

    protected function _scanDir($dir, array &$filePaths)
    {
        foreach (scandir($dir) as $filename) {
            if (is_dir($dir . '/' . $filename) && $filename != '.' && $filename != '..') {
                $this->_scanDir($dir . '/' . $filename, $filePaths);
            }
            elseif (is_file($dir . '/' . $filename)) {
                $filePaths[] = $dir . '/' . $filename;
            }
        }
    }

    protected function _getPhraseOrError($phraseId)
    {
        $info = $this->_getPhraseModel()->getPhraseById($phraseId);
        if (!$info) {
            throw $this->responseException($this->responseError(new XenForo_Phrase('requested_phrase_not_found'), 404));
        }

        return $info;
    }

    protected function _getLanguageOrError($languageId, $allowMaster = false)
    {
        $language = $this->_getLanguageModel()->getLanguageById($languageId, $allowMaster);
        if (!$language) {
            throw $this->responseException($this->responseError(new XenForo_Phrase('requested_language_not_found'), 404));
        }

        return $language;
    }

    /**
     * Lazy load the phrase model object.
     *
     * @return  XenForo_Model_Phrase
     */
    protected function _getPhraseModel()
    {
        return $this->getModelFromCache('XenForo_Model_Phrase');
    }

    /**
     * Lazy load the language model object.
     *
     * @return  XenForo_Model_Language
     */
    protected function _getLanguageModel()
    {
        return $this->getModelFromCache('XenForo_Model_Language');
    }

    /**
     * Get the add-on model.
     *
     * @return XenForo_Model_AddOn
     */
    protected function _getAddOnModel()
    {
        return $this->getModelFromCache('XenForo_Model_AddOn');
    }

    /**
     * Get the add-on model.
     *
     * @return XenForo_Model_Template
     */
    protected function _getTemplateModel()
    {
        return $this->getModelFromCache('XenForo_Model_Template');
    }

    /**
     * Get the add-on model.
     *
     * @return XenForo_Model_AdminTemplate
     */
    protected function _getAdminTemplateModel()
    {
        return $this->getModelFromCache('XenForo_Model_AdminTemplate');
    }


}