<?php


class DevKit_Listener_Listener
{
    public static function loadClassModel($class, array &$extend)
    {
        if ($class == 'XenForo_Model_Phrase') {
            $extend[] = 'DevKit_Model_Phrase';
        }
    }

    public static function loadClassController($class, array &$extend)
    {
	    if ($class == 'XenForo_ControllerAdmin_AddOn') {
		    $extend[] = 'DevKit_ControllerAdmin_AddOn';
	    }

        if ($class == 'XenForo_ControllerAdmin_Phrase') {
            $extend[] = 'DevKit_ControllerAdmin_Phrase';
        }

        if ($class == 'XenForo_ControllerAdmin_Language') {
            $extend[] = 'DevKit_ControllerAdmin_Language';
        }
    }


    public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
    {
        if ($templateName == 'PAGE_CONTAINER' && $template instanceof XenForo_Template_Admin) {
            $template->addRequiredExternal('css', 'devkit');
        }
    }

    public static function templatePostRender($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
    {
        if ($templateName == 'phrase_list' && $template instanceof XenForo_Template_Admin) {

            $params = $template->getParams();
            $addonId = isset($params['addOn']['addon_id']) ? $params['addOn']['addon_id'] : '';

            $phraseFiltersTemplate = $template->create('devkit_phrase_filters', $params)->render();

            $content = preg_replace('#<div class="topChoosers">.*?</ul>\s*?</div>\s*?</div>#s', '$0' . $phraseFiltersTemplate, $content);

            if($params['phraseFilter']=='undefined' or $params['phraseFilter']=='found')
            {
                $content = preg_replace('#<div class="FilterControls">.*?</div>#s', '', $content);

                $content = preg_replace('#<li(.*?)id="">(.*?)<a href="(.*?)phrases/edit(.*?)>(.*?)<em>(.*?)</em>(.*?)</a>(.*?)</li>#s',
                    '<li$1id="">$2<a href="$3phrases/add&addon_id='.$addonId.'&phrase_title=$6$4>$5<em>$6</em>$7</a>$8</li>', $content);
            }
        }

	    if ($templateName == 'addon_list' && $template instanceof XenForo_Template_Admin) {
		    $content = preg_replace('#(<li>[\n\r\s]*<a href="admin.php\?add-ons/[a-zA-Z0-9_]*?/)export(">).*?(</a>[\n\r\s]*</li>)#s', '$0$1zip$2'.new XenForo_Phrase('devkit_create_zip').'$3', $content);
	    }
    }
}

