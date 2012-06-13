<?php

class DevKit_ControllerAdmin_Phrase extends XFCP_DevKit_ControllerAdmin_Phrase
{

    protected function _getPhraseAddEditResponse(array $phrase, $inputLanguageId, $inputPhraseId = 0)
    {
        /* @var $response XenForo_ControllerResponse_View*/
        $response = parent::_getPhraseAddEditResponse($phrase, $inputLanguageId, $inputPhraseId);

        if (!$response->params['phrase']['phrase_id']) {
            $addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
            $phraseTitle = $this->_input->filterSingle('phrase_title', XenForo_Input::STRING);
            if (empty($response->params['phrase']['title'])) {
                $response->params['phrase']['title'] = $phraseTitle;
            }
            if (empty($response->params['addOnSelected'])) {
                $response->params['addOnSelected'] = $addOnId;
            }
        }


        return $response;
    }

}