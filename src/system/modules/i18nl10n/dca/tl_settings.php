<?php

/**
 * i18nl10n Contao Module
 *
 * The i18nl10n module for Contao allows you to manage multilingual content
 * on the element level rather than with page trees.
 *
 *
 * PHP version 5
 * @copyright   Verstärker, Patric Eberle 2014
 * @copyright   Krasimir Berov 2010-2013
 * @author      Patric Eberle <line-in@derverstaerker.ch>
 * @author      Krasimir Berov
 * @package     i18nl10n
 * @license     LGPLv3 http://www.gnu.org/licenses/lgpl-3.0.html
 */


$this->loadLanguageFile('languages');


/**
 * Get default language
 */
$sql = "
    SELECT
      language
    FROM
      tl_page
    WHERE
      type = 'root'
    ORDER BY
      sorting
";

$i18nl10n_default_language = \Database::getInstance()
    ->prepare($sql)
    ->limit(1)
    ->execute()
    ->language;


/**
 * i18nl10n settings palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{page_i18nl10n:hide},i18nl10n_languages,i18nl10n_default_language,i18nl10n_alias_suffix,i18nl10n_addLanguageToUrl';


/**
 * i18nl10n settings fields
 */
$i18nl10nSettings = array
(
    'i18nl10n_languages'        => array
    (
        'label'         => &$GLOBALS['TL_LANG']['tl_settings']['i18nl10n_languages'],
        'exclude'       => true,
        'default'       => array('en', 'de', 'bg'),
        'inputType'     => 'listWizard',
        'eval'          => array
        (
            'mandatory' => true,
            'style'     => 'width:2em;',
            'tl_class'  => 'w50 autoheight',
            'nospace'   => true
        ),
        'save_callback' => array
        (
            array('tl_settings_l10n', 'ensureUnique'),
            array('tl_settings_l10n', 'ensureExists')
        )
    ),
    'i18nl10n_default_language' => array
    (
        'label'     => &$GLOBALS['TL_LANG']['tl_settings']['i18nl10n_default_language'],
        'exclude'   => true,
        'inputType' => 'select',
        'options'   => array
        (
            $i18nl10n_default_language => $GLOBALS['TL_LANG']['LNG'][$i18nl10n_default_language]
        ),
        'default'   => $i18nl10n_default_language,
        'eval'      => array
        (
            'mandatory' => true,
            'tl_class'  => 'w50'
        )
    ),
    'i18nl10n_alias_suffix'     => array
    (
        'label'         => &$GLOBALS['TL_LANG']['tl_settings']['i18nl10n_alias_suffix'],
        'exclude'       => true,
        'inputType'     => 'checkbox',
        'default'       => false,
        'eval'          => array
        (
            'tl_class' => 'w50 clr'
        ),
        'save_callback' => array
        (
            array('tl_settings_l10n', 'ensureOthersUnchecked'),
        )
    ),
    'i18nl10n_addLanguageToUrl' => array
    (
        'label'         => &$GLOBALS['TL_LANG']['tl_settings']['i18nl10n_addLanguageToUrl'],
        'exclude'       => true,
        'inputType'     => 'checkbox',
        'default'       => false,
        'eval'          => array
        (
            'tl_class' => 'w50'
        ),
        'save_callback' => array
        (
            array('tl_settings_l10n', 'ensureOthersUnchecked'),
        )
    )
);

// inset i18nl10n fields
array_insert(
    $GLOBALS['TL_DCA']['tl_settings']['fields'],
    count($GLOBALS['TL_DCA']['tl_settings']['fields']),
    $i18nl10nSettings
);


/**
 * Class tl_settings_l10n
 *
 * @copyright   Verstärker, Patric Eberle 2014
 * @copyright   Krasimir Berov 2010-2011
 * @author      Patric Eberle <line-in@derverstaerker.ch>
 * @author      Krasimir Berov <http://i-can.eu>
 * @package     i18nl10n
 * @license     LGPLv3 http://www.gnu.org/licenses/lgpl-3.0.html
 */
class tl_settings_l10n extends Backend
{

    /**
     * Ensure a langauge is not already in use
     *
     * @param $languages
     * @param DataContainer $dc
     * @return string
     */
    function ensureUnique($languages, DataContainer $dc) {
        return serialize( array_unique( deserialize( $languages ) ) );
    }


    /**
     * Ensure a language exists
     *
     * @param $languages
     * @param DataContainer $dc
     * @return bool|string
     */
    function ensureExists($languages, DataContainer $dc) {

        $array_language_exists = array();
        $array_languages = deserialize( $languages );
        $default_language_present = false;
        $i18nl10n_default_language = \Input::post('i18nl10n_default_language');

        if(!empty($array_languages))
        {
            foreach($array_languages as $k) {
                if( array_key_exists($k, $GLOBALS['TL_LANG']['LNG']) )
                {
                    array_push($array_language_exists,$k);
                }

                if($k == $i18nl10n_default_language)
                {
                    $default_language_present = true;
                }
            }
        }

        //make sure default language is present
        if(!$default_language_present){
            // print error message
            $errorMessage = $GLOBALS['TL_LANG']['tl_settings']['i18nl10n_defLangMissingError'];
            \Message::addError($errorMessage);

            return false;
        }
        return serialize( $array_language_exists );
    }


    /**
     * Ensure Contao add language to url is disabled
     *
     * @param $value
     * @param DataContainer $dc
     * @return bool
     */
    function ensureOthersUnchecked($value, DataContainer $dc){

        if($value
            && ($dc->field == 'i18nl10n_alias_suffix'
                && $GLOBALS['TL_CONFIG']['i18nl10n_addLanguageToUrl'])
            || ($dc->field == 'i18nl10n_addLanguageToUrl'
                && $GLOBALS['TL_CONFIG']['i18nl10n_alias_suffix']))
        {
                // show error and write to log
                $errorMessage = &$GLOBALS['TL_LANG']['tl_settings']['i18nl10n_alias_suffixError'];
                $errorMessage = sprintf(
                    $errorMessage,
                    $GLOBALS['TL_LANG']['tl_settings']['i18nl10n_alias_suffix'][0],
                    $GLOBALS['TL_LANG']['tl_settings']['i18nl10n_addLanguageToUrl'][0]
                );
                \Message::addError($errorMessage);
                \System::log($errorMessage, __METHOD__, TL_CONFIGURATION);

                return false;
        }
        elseif($GLOBALS['TL_CONFIG']['addLanguageToUrl'])
        {
            $errorMessage = &$GLOBALS['TL_LANG']['tl_settings']['i18nl10n_contaoAddLanguageToUrlError'];
            $errorMessage = sprintf(
                $errorMessage,
                $GLOBALS['TL_LANG']['tl_settings']['addLanguageToUrl'][0]
            );
            \Message::addError($errorMessage);
            \System::log($errorMessage, __METHOD__, TL_CONFIGURATION);
            return false;
        }


        return $value;
    }

}