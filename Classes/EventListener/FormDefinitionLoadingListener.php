<?php

namespace Petitglacon\FormModuleEnhancer\EventListener;

use Petitglacon\FormModuleEnhancer\Event\FormDefinitionLoadingEvent;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

class FormDefinitionLoadingListener
{
    public function __construct(
        private FormPersistenceManagerInterface $formPersistenceManager
    )
    {}

    public function __invoke(FormDefinitionLoadingEvent $event): void
    {
        $formCurrentDefinition = $event->getFormDefinition();

        $formDefinition = $this->formPersistenceManager->load($formCurrentDefinition['persistenceIdentifier']);
        $formCurrentDefinition['definition'] = $formDefinition;

        $event->setFormDefinition($formCurrentDefinition);
    }
}