<?php

namespace TheAentMachine\AentBootstrap\Event;

use TheAentMachine\Aent\Context\Context;
use TheAentMachine\Aent\Event\Bootstrap\AbstractBootstrapAddEvent;
use TheAentMachine\Aent\Payload\Bootstrap\BootstrapPayload;
use TheAentMachine\Aent\Payload\Bootstrap\BootstrapPayloadAggregator;
use TheAentMachine\Aent\Payload\Bootstrap\Exception\BootstrapPayloadException;
use TheAentMachine\Aent\Registry\AentItemRegistry;
use TheAentMachine\Aent\Registry\ColonyRegistry;
use TheAentMachine\Aent\Registry\Exception\ColonyRegistryException;
use TheAentMachine\Prompt\Helper\ValidatorHelper;

final class AddEvent extends AbstractBootstrapAddEvent
{
    /** @var BootstrapPayloadAggregator */
    private $boostrapPayloadAggregator;

    /** @var string */
    private $appName;

    /** @var string[] */
    private $setupTypes = [
        'Docker Compose for your development environment',
        'Docker Compose for your development environment and Kubernetes for your test environment',
        'Docker Compose for your development environment and Kubernetes for your test and production environments',
        'Custom',
    ];

    /** @var ColonyRegistry */
    private $orchestratorRegistry;

    /** @var ColonyRegistry */
    private $CIRegistry;

    /**
     * @return void
     * @throws ColonyRegistryException
     */
    protected function before(): void
    {
        $this->output->writeln("👋 Hello! I'm the aent <info>Bootstrap</info> and I'll help you bootstrapping a Docker project for your web application.");
        $this->boostrapPayloadAggregator = new BootstrapPayloadAggregator();
        $appName = $this->prompt->input("\nYour application name", null, null, true, ValidatorHelper::getAlphaValidator());
        $this->appName = \strtolower($appName ?? '');
        $this->orchestratorRegistry = ColonyRegistry::orchestratorRegistry();
        $this->CIRegistry = ColonyRegistry::CIRegistry();
    }

    /**
     * @return BootstrapPayloadAggregator
     * @throws BootstrapPayloadException
     * @throws ColonyRegistryException
     */
    protected function process(): BootstrapPayloadAggregator
    {
        $setupTypeIndex = $this->getSetupType();
        if ($setupTypeIndex < 3) {
            $setupType = $this->setupTypes[$setupTypeIndex];
            $this->output->writeln("\n👌 Alright, I'm going to setup <info>$setupType</info>!");
            $this->addDefaultPayloads($setupTypeIndex);
            $this->printSummary();
            $this->printStandBy();
            return $this->boostrapPayloadAggregator;
        }
        $this->output->writeln("\n👌 In this step, you may add as many environments as you wish. Let's begin with your first environment!");
        $this->addCustomPayload();
        $this->printSummary();
        if ($this->prompt->confirm("\nDo you want to add another environment?")) {
            do {
                $this->addCustomPayload();
                $this->printSummary();
            } while ($this->prompt->confirm("\nDo you want to add another environment?"));
        }
        $this->printStandBy();
        return $this->boostrapPayloadAggregator;
    }

    /**
     * @return void
     */
    protected function after(): void
    {
        $this->output->writeln("\n👋 Hello again! I'm the aent <info>Bootstrap</info> and we have finished your project setup.");
        $this->printSummary();
        $this->output->writeln("\nYou may now start adding services with <info>aenthill add [image]</info>. See <info>https://aenthill.github.io/</info> for the list of available services!");
    }

    /**
     * @return void
     */
    private function printSummary(): void
    {
        $this->output->writeln("\nSetup summary:");
        /** @var BootstrapPayload $payload */
        foreach ($this->boostrapPayloadAggregator->getBootstrapPayloads() as $payload) {
            $orchestrator = $payload->getOrchestratorAent()->getName();
            $ci = !empty($payload->getCIAent()) ? $payload->getCIAent()->getName() : null;
            $context = $payload->getContext();
            $type = $context->getType();
            $name = $context->getName();
            $baseVirtualHost = $context->getBaseVirtualHost();
            $payload->getCIAent();
            $message = " - a <info>$type</info> environment <info>$name</info> with the base virtual host <info>$baseVirtualHost</info>";
            $message .= !empty($ci) ? ", <info>$orchestrator</info> as orchestrator and <info>$ci</info> as CI provider" : " and <info>$orchestrator</info> as orchestrator";
            $this->output->writeln($message);
        }
    }

    /**
     * @return void
     */
    private function printStandBy(): void
    {
        $this->output->writeln("\n👌 I'm going to wake up some aents in order to finish your project setup, see you later!");
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
     * @throws BootstrapPayloadException
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
     * @throws BootstrapPayloadException
     * @throws ColonyRegistryException
     */
    private function addDockerComposeForDevelopment(): void
    {
        $type = Context::DEV;
        $name = 'dev';
        $baseVirtualHost = $this->getBaseVirtualHost($type, $name);
        $payload = new BootstrapPayload();
        $payload->setContext(new Context($type, $name, $baseVirtualHost));
        $payload->setOrchestratorAent($this->orchestratorRegistry->getAent(ColonyRegistry::DOCKER_COMPOSE));
        $this->boostrapPayloadAggregator->addBootstrapPayload($payload);
    }

    /**
     * @return void
     * @throws BootstrapPayloadException
     * @throws ColonyRegistryException
     */
    private function addKubernetesForTest(): void
    {
        $type = Context::TEST;
        $name = 'test';
        $baseVirtualHost = $this->getBaseVirtualHost($type, $name);
        $payload = new BootstrapPayload();
        $payload->setContext(new Context($type, $name, $baseVirtualHost));
        $payload->setOrchestratorAent($this->orchestratorRegistry->getAent(ColonyRegistry::KUBERNETES));
        $payload->setCIAent($this->getCIAent($type, $name));
        $this->boostrapPayloadAggregator->addBootstrapPayload($payload);
    }

    /**
     * @return void
     * @throws BootstrapPayloadException
     * @throws ColonyRegistryException
     */
    private function addKubernetesForProd(): void
    {
        $type = Context::PROD;
        $name = 'prod';
        $baseVirtualHost = $this->getBaseVirtualHost($type, $name);
        $payload = new BootstrapPayload();
        $payload->setContext(new Context($type, $name, $baseVirtualHost));
        $payload->setOrchestratorAent($this->orchestratorRegistry->getAent(ColonyRegistry::KUBERNETES));
        $payload->setCIAent($this->getCIAent($type, $name));
        $this->boostrapPayloadAggregator->addBootstrapPayload($payload);
    }

    /**
     * @return void
     * @throws BootstrapPayloadException
     */
    private function addCustomPayload(): void
    {
        $typeHelpText = "We organize environments into three categories:\n - <info>development</info>: your local environment\n - <info>test</info>: a remote environment where you're pushing some features to test\n - <info>production</info>: a remote environment for staging/production purpose";
        $type = $this->prompt->select("\nYour environment type", Context::getList(), $typeHelpText, null, true) ?? '';
        $nameHelpText = "A unique identifier for your environment.\nFor instance, a <info>development</info> environment might be called <info>dev</info>.";
        $nameValidator =  ValidatorHelper::merge(
            ValidatorHelper::getFuncShouldNotReturnTrueValidator([$this->boostrapPayloadAggregator, 'doesEnvironmentNameExist'], 'Environment "%s" does already exist!'),
            ValidatorHelper::getAlphaValidator()
        );
        $name = $this->prompt->input("\nYour <info>$type</info> environment name", $nameHelpText, null, true, $nameValidator) ?? '';
        $baseVirtualHost = $this->getBaseVirtualHost($type, $name);
        $context = new Context($type, $name, $baseVirtualHost);
        $payload = new BootstrapPayload();
        $payload->setContext($context);
        $payload->setOrchestratorAent($this->getOrchestratorAent($type, $name));
        if (!$context->isDevelopment()) {
            $payload->setCIAent($this->getCIAent($type, $name));
        }
        $this->boostrapPayloadAggregator->addBootstrapPayload($payload);
    }

    /**
     * @param string $type
     * @param string $name
     * @return string
     */
    private function getBaseVirtualHost(string $type, string $name): string
    {
        $appName = $this->appName;
        $default = null;
        switch ($type) {
            case Context::DEV:
                $default = "$appName.localhost";
                break;
            case Context::TEST:
                $default = "$name.$appName.com";
                break;
            default:
                $default = "$appName.com";
        }
        $default = !empty($default) && !$this->boostrapPayloadAggregator->doesBaseVirtualHostExist($default) ? $default : null;
        $helpText = "The base virtual host will determine on which URL your web services will be accessible.\nFor instance, if your base virtual host is <info>foo.localhost</info>, a web service may be accessible through <info>{service sub domain}.foo.localhost</info>.";
        $validator = ValidatorHelper::merge(
            ValidatorHelper::getFuncShouldNotReturnTrueValidator([$this->boostrapPayloadAggregator, 'doesBaseVirtualHostExist'], 'Base virtual host "%s" does already exist!'),
            ValidatorHelper::getDomainNameValidator()
        );
        return $this->prompt->input("\nYour base virtual host for your <info>$type</info> environment <info>$name</info>", $helpText, $default, true, $validator) ?? '';
    }

    /**
     * @param string $type
     * @param string $name
     * @return AentItemRegistry
     */
    private function getOrchestratorAent(string $type, string $name): AentItemRegistry
    {
        $text = "\nYour orchestrator for your <info>$type</info> environment <info>$name</info>";
        $helpText = 'The orchestrator is a tool which will manage your container.';
        $response = $this->prompt->getPromptHelper()->getFromColonyRegistry($this->orchestratorRegistry, $text, $helpText);
        if (!empty($response)) {
            return $response;
        }
        return $this->prompt->getPromptHelper()->getDockerHubImage();
    }

    /**
     * @param string $type
     * @param string $name
     * @return AentItemRegistry
     */
    private function getCIAent(string $type, string $name): AentItemRegistry
    {
        $text = "\nYour CI provider for your <info>$type</info> environment <info>$name</info>";
        $helpText = 'A CI provider will automatically build the images of your containers and deploy them in your remote environment. You should definitely use one in environments != development.';
        $response = $this->prompt->getPromptHelper()->getFromColonyRegistry($this->CIRegistry, $text, $helpText);
        if (!empty($response)) {
            return $response;
        }
        return $this->prompt->getPromptHelper()->getDockerHubImage();
    }
}
