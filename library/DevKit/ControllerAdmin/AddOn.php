<?php

/**
 * Add-ons controller.
 *
 * @package XenForo_AddOns
 */
class DevKit_ControllerAdmin_AddOn extends XFCP_DevKit_ControllerAdmin_AddOn
{

	/**
	 * Exports an add-on's XML data.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionZip()
	{
		$addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
		$addOn = $this->_getAddOnOrError($addOnId);

		$rootDir = XenForo_Application::getInstance()->getRootDir();
		$zipPath = XenForo_Helper_File::getTempDir().'/addon-'.$addOnId.'.zip';
		if (file_exists($zipPath)) unlink($zipPath);

		$zip = new ZipArchive;
		$res = $zip->open($zipPath, ZipArchive::CREATE);
		if ($res === TRUE) 
		{
			$zip->addFromString('addon-'.$addOnId.'.xml', $this->_getAddOnModel()->getAddOnXml($addOn)->saveXml());

			if(is_dir($rootDir . '/library/' . $addOnId))
			{
				$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDir . '/library/' . $addOnId));
				foreach ($iterator as $key=>$value)
					$zip->addFile(realpath($key), str_replace($rootDir.'/', '', $key));
			}

			if(is_dir($rootDir . '/js/' . strtolower($addOnId)))
			{
				$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDir . '/js/' . strtolower($addOnId)));
				foreach ($iterator as $key=>$value)
					$zip->addFile(realpath($key), str_replace($rootDir.'/', '', $key));
			}

			$zip->close();
		}

		if (!file_exists($zipPath) || !is_readable($zipPath))
		{
			return $this->responseError(new XenForo_Phrase('devkit_error_while_creating_zip'));
		}

		$this->_routeMatch->setResponseType('raw');

		$attachment = array(
			'filename' => 'addon-'.$addOnId.'_'.$addOn['version_string'].'.zip',
			'file_size' => filesize($zipPath),
			'attach_date' => XenForo_Application::$time
		);

		$viewParams = array(
			'attachment' => $attachment,
			'attachmentFile' => $zipPath
		);

		return $this->responseView('XenForo_ViewAdmin_Attachment_View', '', $viewParams);
	}

}