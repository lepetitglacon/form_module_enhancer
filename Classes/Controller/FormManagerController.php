<?php

declare(strict_types=1);

namespace Petitglacon\FormModuleEnhancer\Controller;

use Petitglacon\FormModuleEnhancer\Event\FormDefinitionLoadingEvent;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Form\Controller\FormManagerController as BaseFormManagerController;

use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Form\Service\DatabaseService;
use TYPO3\CMS\Form\Service\TranslationService;

class FormManagerController extends BaseFormManagerController
{
    protected array $searchFields = ['name'];
    protected string $searchKey = '';
    protected bool $displayFlashInfo = false;

    protected function indexAction(int $page = 1, string $searchKey = null): \Psr\Http\Message\ResponseInterface
    {
        $forms = [];

        $this->displayFlashInfo = $this->settings['displayFlashInfo'] ? (bool)$this->settings['displayFlashInfo'] : false;
        $this->limit = $this->settings['limit'] ? (int)$this->settings['limit'] : 20;
        if (!empty($this->settings['additionalSearchFields'])) {
            $this->searchFields = array_merge($this->searchFields, explode(',', $this->settings['additionalSearchFields']));
        }
        $this->searchKey = $searchKey ?? $this->request->getParsedBody()['tx_form_web_formformbuilder']['search'] ?? '';

        if (empty($this->searchKey)) {
            $forms = $this->getAvailableFormDefinitions();
        } else {
            foreach ($this->getAvailableFormDefinitions() as $formDefinition) {
                foreach ($this->searchFields as $searchField) {
                    if (is_string($formDefinition[$searchField]) && str_contains($formDefinition[$searchField], $this->searchKey)) {
                        $forms[$formDefinition['identifier']] = $formDefinition;
                    }
                }
            }

            if ($this->displayFlashInfo) {
                if (empty($forms)) {
                    $this->addFlashMessage("for search : $this->searchKey", 'No result', ContextualFeedbackSeverity::WARNING);
                } else {
                    $this->addFlashMessage(count($forms) . " results for search : $this->searchKey", 'Search results', ContextualFeedbackSeverity::OK);
                }
            }
        }

        $arrayPaginator = new ArrayPaginator($forms, $page, $this->limit);
        $pagination = new SimplePagination($arrayPaginator);

        $moduleTemplate = $this->initializeModuleTemplate($this->request);
        $moduleTemplate->assignMultiple([
            'paginator' => $arrayPaginator,
            'pagination' => $pagination,
            'searchKey' => $this->searchKey,
            'searchFields' => $this->searchFields,
            'stylesheets' => $this->formSettings['formManager']['stylesheets'],
            'formManagerAppInitialData' => json_encode($this->getFormManagerAppInitialData()),
        ]);
        if (!empty($this->formSettings['formManager']['javaScriptTranslationFile'])) {
            $this->pageRenderer->addInlineLanguageLabelFile($this->formSettings['formManager']['javaScriptTranslationFile']);
        }

        $javaScriptModules = array_map(
            static fn(string $name) => JavaScriptModuleInstruction::create($name),
            array_filter(
                $this->formSettings['formManager']['dynamicJavaScriptModules'] ?? [],
                fn(string $name) => in_array($name, self::JS_MODULE_NAMES, true),
                ARRAY_FILTER_USE_KEY
            )
        );
        $requireJsModules = array_map(
            static fn(string $name) => JavaScriptModuleInstruction::forRequireJS($name),
            array_filter(
                $this->formSettings['formManager']['dynamicRequireJsModules'] ?? [],
                fn(string $name) => in_array($name, self::JS_MODULE_NAMES, true),
                ARRAY_FILTER_USE_KEY
            )
        );

        $jsModules = $requireJsModules + $javaScriptModules;
        if (count($requireJsModules)) {
            trigger_error(
                'formManager.dynamicRequireJsModules has been deprecated in v12 and will be removed with v13. ' .
                'Use formManager.dynamicJavaScriptModules instead.',
                E_USER_DEPRECATED
            );
            $this->pageRenderer->loadRequireJs();
        }
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/form/backend/helper.js', 'Helper')
                ->invoke('dispatchFormManager', $jsModules, $this->getFormManagerAppInitialData())
        );
        array_map($this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(...), $javaScriptModules);
        $moduleTemplate->setModuleClass($this->request->getPluginName() . '_' . $this->request->getControllerName());
        $moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
        $moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/locallang_module.xlf:mlang_tabs_tab')
        );
        return $moduleTemplate->renderResponse('Backend/FormManager/Index');
    }

    /**
     * List all formDefinitions which can be loaded through t form persistence
     * manager. Enrich this data by a reference counter.
     */
    protected function getAvailableFormDefinitions(): array
    {
        $allReferencesForFileUid = $this->databaseService->getAllReferencesForFileUid();
        $allReferencesForPersistenceIdentifier = $this->databaseService->getAllReferencesForPersistenceIdentifier();

        $availableFormDefinitions = [];
        foreach ($this->formPersistenceManager->listForms() as $formDefinition) {

            $referenceCount  = 0;
            if (
                isset($formDefinition['fileUid'])
                && array_key_exists($formDefinition['fileUid'], $allReferencesForFileUid)
            ) {
                $referenceCount = $allReferencesForFileUid[$formDefinition['fileUid']];
            } elseif (array_key_exists($formDefinition['persistenceIdentifier'], $allReferencesForPersistenceIdentifier)) {
                $referenceCount = $allReferencesForPersistenceIdentifier[$formDefinition['persistenceIdentifier']];
            }

            $formDefinition['referenceCount'] = $referenceCount;

            /** @var FormDefinitionLoadingEvent $event */
            $event = $this->eventDispatcher->dispatch(
                new FormDefinitionLoadingEvent($formDefinition),
            );
            $formDefinition = $event->getFormDefinition();

            $availableFormDefinitions[] = $formDefinition;
        }

        return $availableFormDefinitions;
    }

}
