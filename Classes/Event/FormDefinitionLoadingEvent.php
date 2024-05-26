<?php

declare(strict_types=1);

namespace Petitglacon\FormModuleEnhancer\Event;


final class FormDefinitionLoadingEvent
{
    public function __construct(
        private array $formDefinition,
    ) {}

    public function getFormDefinition(): array
    {
        return $this->formDefinition;
    }

    public function setFormDefinition(array $formDefinition): void
    {
        $this->formDefinition = $formDefinition;
    }
}