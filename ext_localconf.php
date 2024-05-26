<?php

declare(strict_types=1);

defined('TYPO3') or die();

use Petitglacon\FormModuleEnhancer\Controller\FormManagerController as CustomFormManagerController;
use TYPO3\CMS\Form\Controller\FormManagerController;


$extensionKey = 'form_module_enhancer';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][FormManagerController::class] = [
    'className' => CustomFormManagerController::class,
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    $extensionKey,
    'setup',
    "@import 'EXT:$extensionKey/Configuration/TypoScript/setup.typoscript'"
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:form/Resources/Private/Language/locallang.xlf'][] =
    'EXT:form_module_enhancer/Resources/Private/Language/locallang.xlf';