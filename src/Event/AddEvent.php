<?php

namespace TheAentMachine\AentBootstrap\Event;

use TheAentMachine\Aent\Context\Context;
use TheAentMachine\Aent\Event\Bootstrap\AbstractBootstrapAddEvent;
use TheAentMachine\Aent\Event\Bootstrap\Model\OrchestratorBootstrap;
use TheAentMachine\Aent\Registry\AentItemRegistry;
use TheAentMachine\Aent\Registry\ColonyRegistry;
use TheAentMachine\Aent\Registry\Exception\ColonyRegistryException;
use TheAentMachine\Prompt\Helper\ValidatorHelper;

final class AddEvent extends AbstractBootstrapAddEvent
{
    /** @var ColonyRegistry */
    private $orchestratorRegistry;

    /** @var OrchestratorBootstrap[] */
    private $bootstraps;

    /** @var string */
    private $appName;

    /** @var string[] */
    private $setupTypes = [
        'Docker Compose for your development environment',
        'Docker Compose for your development environment and Kubernetes for your test environment',
        'Docker Compose for your development environment and Kubernetes for your test and production environments',
        'Custom',
    ];

    /**
     * @return OrchestratorBootstrap[]
     * @throws ColonyRegistryException
     */
    protected function getOrchestratorsBootstraps(): array
    {
        $this->orchestratorRegistry = ColonyRegistry::orchestratorRegistry();
        $this->bootstraps = [];
        $appName = $this->prompt->input("\nYour application name", null, null, true, ValidatorHelper::getAlphaValidator());
        $this->appName = \strtolower($appName ?? '');
        $this->processSetupType();
        return $this->bootstraps;
    }

    /**
     * @return void
     * @throws ColonyRegistryException
     */
    private function processSetupType(): void
    {
        $setupTypeIndex = $this->getSetupType();
        if ($setupTypeIndex < 3) {
            $setupType = $this->setupTypes[$setupTypeIndex];
            $this->output->writeln("\n👌 Alright, I'm going to setup <info>$setupType</info>!");
            $this->addDefaultPayloads($setupTypeIndex);
            return;
        }
        $this->output->writeln("\n👌 In this step, you may add as many environments as you wish. Let's begin with your first environment!");
        $this->addCustomPayload();
        $this->printSummary($this->bootstraps);
        if ($this->prompt->confirm("\nDo you want to add another environment?")) {
            do {
                $this->addCustomPayload();
                $this->printSummary($this->bootstraps);
            } while ($this->prompt->confirm("\nDo you want to add another environment?"));
        }
    }

    /**
     * @return int
     */
    private function getSetupType(): int
    {
        $appName = $this->appName;
        $helpText = 'We provide a bunch of defaults setup which fit for most cases. By choosing the custom option, you may define your own environments.';
        $response = $this->prompt->select("\nYour setup type for <info>$appName</info>", $this->setupTypes, $helpText, null, true);
        $setupTypeIndex = \array_search($response, $this->setupTypes);
        return $setupTypeIndex !== false ? (int)$setupTypeIndex : 3;
    }

    /**
     * @param int $setupTypeIndex
     * @return void
     * @throws ColonyRegistryException
     */
    private function addDefaultPayloads(int $setupTypeIndex): void
    {
        switch ($setupTypeIndex) {
            case 0:
                // Docker Compose for your development environment
                $this->addDockerComposeForDevelopment();
                break;
            case 1:
                // Docker Compose for your development environment and Kubernetes for your test environment
                $this->addDockerComposeForDevelopment();
                $this->addKubernetesForTest();
                break;
            default:
                // Docker Compose for your development environment and Kubernetes for your test and production environments
                $this->addDockerComposeForDevelopment();
                $this->addKubernetesForTest();
                $this->addKubernetesForProd();
        }
    }

    /**
     * @return void
     * @throws ColonyRegistryException
     */
    private function addDockerComposeForDevelopment(): void
    {
        $environmentType = Context::DEV;
        $environmentName = 'dev';
        $bootstrap = new OrchestratorBootstrap();
        $bootstrap->setAent($this->orchestratorRegistry->getAent(ColonyRegistry::DOCKER_COMPOSE));
        $bootstrap->setEnvironmentType($environmentType);
        $bootstrap->setEnvironmentName($environmentName);
        $bootstrap->setBaseVirtualHost($this->getBaseVirtualHost($environmentType, $environmentName));
        $this->bootstraps[] = $bootstrap;
    }

    /**
     * @return void
     * @throws ColonyRegistryException
     */
    private function addKubernetesForTest(): void
    {
        $environmentType = Context::TEST;
        $environmentName = 'test';
        $bootstrap = new OrchestratorBootstrap();
        $bootstrap->setAent($this->orchestratorRegistry->getAent(ColonyRegistry::KUBERNETES));
        $bootstrap->setEnvironmentType($environmentType);
        $bootstrap->setEnvironmentName($environmentName);
        $bootstrap->setBaseVirtualHost($this->getBaseVirtualHost($environmentType, $environmentName));
        $this->bootstraps[] = $bootstrap;
    }

    /**
     * @return void
     * @throws ColonyRegistryException
     */
    private function addKubernetesForProd(): void
    {
        $environmentType = Context::PROD;
        $environmentName = 'prod';
        $bootstrap = new OrchestratorBootstrap();
        $bootstrap->setAent($this->orchestratorRegistry->getAent(ColonyRegistry::KUBERNETES));
        $bootstrap->setEnvironmentType($environmentType);
        $bootstrap->setEnvironmentName($environmentName);
        $bootstrap->setBaseVirtualHost($this->getBaseVirtualHost($environmentType, $environmentName));
        $this->bootstraps[] = $bootstrap;
    }

    /**
     * @return void
     */
    private function addCustomPayload(): void
    {
        $typeHelpText = "We organize environments into three categories:\n - <info>development</info>: your local environment\n - <info>test</info>: a remote environment where you're pushing some features to test\n - <info>production</info>: a remote environment for staging/production purpose";
        $environmentType = $this->prompt->select("\nYour environment type", Context::getEnvironmentTypeList(), $typeHelpText, null, true) ?? '';
        $nameHelpText = "A unique identifier for your environment.\nFor instance, a <info>development</info> environment might be called <info>dev</info>.";
        $nameValidator =  ValidatorHelper::merge(
            ValidatorHelper::getFuncShouldNotReturnTrueValidator([$this, 'doesEnvironmentNameExist'], 'Environment "%s" does already exist!'),
            ValidatorHelper::getAlphaValidator()
        );
        $environmentName = $this->prompt->input("\nYour <info>$environmentType</info> environment name", $nameHelpText, null, true, $nameValidator) ?? '';
        $bootstrap = new OrchestratorBootstrap();
        $bootstrap->setEnvironmentType($environmentType);
        $bootstrap->setEnvironmentName($environmentName);
        $bootstrap->setBaseVirtualHost($this->getBaseVirtualHost($environmentType, $environmentName));
        $bootstrap->setAent($this->getOrchestratorAent($environmentType, $environmentName));
        $this->bootstraps[] = $bootstrap;
    }

    /**
     * @param string $environmentType
     * @param string $environmentName
     * @return string
     */
    private function getBaseVirtualHost(string $environmentType, string $environmentName): string
    {
        $appName = $this->appName;
        $default = null;
        switch ($environmentType) {
            case Context::DEV:
                $default = "$appName.localhost";
                break;
            case Context::TEST:
                $default = "$environmentName.$appName.com";
                break;
            default:
                $default = "$appName.com";
        }
        $default = !empty($default) && !$this->doesBaseVirtualHostExist($default) ? $default : null;
        $helpText = "The base virtual host will determine on which URL your web services will be accessible.\nFor instance, if your base virtual host is <info>foo.localhost</info>, a web service may be accessible through <info>{service sub domain}.foo.localhost</info>.";
        $validator = ValidatorHelper::merge(
            ValidatorHelper::getFuncShouldNotReturnTrueValidator([$this, 'doesBaseVirtualHostExist'], 'Base virtual host "%s" does already exist!'),
            ValidatorHelper::getDomainNameValidator()
        );
        return $this->prompt->input("\nYour base virtual host for your <info>$environmentType</info> environment <info>$environmentName</info>", $helpText, $default, true, $validator) ?? '';
    }

    /**
     * @param string $environmentType
     * @param string $environmentName
     * @return AentItemRegistry
     */
    private function getOrchestratorAent(string $environmentType, string $environmentName): AentItemRegistry
    {
        $text = "\nYour orchestrator for your <info>$environmentType</info> environment <info>$environmentName</info>";
        $helpText = 'The orchestrator is a tool which will manage your container.';
        return $this->prompt->getPromptHelper()->getFromColonyRegistry($this->orchestratorRegistry, $text, $helpText);
    }

    /**
     * @param string $environmentName
     * @return bool
     */
    public function doesEnvironmentNameExist(string $environmentName): bool
    {
        foreach ($this->bootstraps as $bootstrap) {
            if ($bootstrap->getEnvironmentName() === $environmentName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $baseVirtualHost
     * @return bool
     */
    public function doesBaseVirtualHostExist(string $baseVirtualHost): bool
    {
        foreach ($this->bootstraps as $bootstrap) {
            if ($bootstrap->getBaseVirtualHost() === $baseVirtualHost) {
                return true;
            }
        }
        return false;
    }
}
