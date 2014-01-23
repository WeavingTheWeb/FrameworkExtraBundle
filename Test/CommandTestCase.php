<?php

namespace WeavingTheWeb\Bundle\FrameworkExtraBundle\Test;

use Symfony\Bundle\FrameworkBundle\Console\Application;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Tester\CommandTester,
    Symfony\Component\HttpKernel\KernelInterface;

/**
 * Base class for testing Symfony command-line applications.
 * Largely inspired by an article of "Alexandre Salomé" <alexandre.salome@gmail.com> 
 * http://alexandre-salome.fr/blog/Test-your-commands-in-Symfony2
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org> 
 */
abstract class CommandTestCase extends TestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Console\Application
     */
    protected $application;

    protected $command;

    protected $commandClass;

    /**
     * @var $commandTester CommandTester
     */
    protected $commandTester;

    /**
     * Gets command
     *
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Gets command name
     *
     * @return mixed
     */
    public function getCommandName()
    {
        return $this->getCommand()->getName();
    }

    /**
     * Gets command tester
     *
     * @param string $name
     *
     * @return CommandTester
     */
    public function getCommandTester($name)
    {
        $this->command = $this->application->find($name);

        return new CommandTester($this->command);
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Console\Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Get file handler output
     *
     * @param resource $fp
     *
     * @return string
     */
    public function getOutput($fp)
    {
        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output .= fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }

    /**
     * Run an application command and bind its output to a temporary file handler
     *
     * @param string $command
     *
     * @return resource
     */
    public function getOutputFileHandle($command)
    {
        $stringInput = $this->getParameter('weaving_the_web_framework_extra.string_input.class');
        $streamOutput = $this->getParameter('weaving_the_web_framework_extra.stream_output.class');

        $fileHandle = tmpfile();

        $input = new $stringInput($command);
        $output = new $streamOutput($fileHandle);
        $this->runApplication($input, $output);

        return $fileHandle;
    }

    /**
     * Run an application
     *
     * @param InputInterface    $input
     * @param OutputInterface   $output
     */
    public function runApplication(InputInterface $input, OutputInterface $output)
    {
        $this->application->run($input, $output);
    }

    /**
     * Run a command and return it output
     *
     * @return string
     */
    public function runCommand()
    {
        $this->bootstrapApplication($this->client->getKernel());
        $fileHandle = $this->getOutputFileHandle($this->command);

        return $this->getOutput($fileHandle);
    }

    /**
     * Test command execution by passing command class name
     *
     * @param array $command
     * @param callable $beforeCommandExecution
     * @return mixed|string
     */
    protected function assertCommandExecution(array $command, callable $beforeCommandExecution = null)
    {
        $this->commandClass = $this->getParameter($command['class']);
        $this->setUpApplicationCommand();

        $this->commandTester = $this->getCommandTester($command['alias']);

        if (!is_null($beforeCommandExecution)) {
            $beforeCommandExecution();
        }

        $this->commandTester->execute($this->buildConsoleInput($command));

        $standardOutput = $this->commandTester->getDisplay();
        $this->assertNotContains('Exception', $standardOutput, 'It should not output the name of an exception');

        /** @var \Symfony\Component\Translation\Translator $translator */
        $translator = $this->get('translator');
        $translationKey = $command['success_translation_key'];

        if (array_key_exists('translation_parameters', $command)) {
            $translationParameters = $command['translation_parameters'];
        } else {
            $translationParameters = [];
        }

        $successMessage = $translator->trans($translationKey, $translationParameters, 'command');

        $this->assertNotContains($translationKey, $standardOutput, 'It should not output a translation key');
        $this->assertContains($successMessage, $standardOutput, 'It should output a success message');

        return $standardOutput;
    }

    /**
     * Set up an application command
     *
     * @param null $command
     */
    public function setUpApplicationCommand($command = null)
    {
        $commandClass = $this->commandClass;
        $this->bootstrapApplication($this->client->getKernel());

        if (is_null($command)) {
            $this->application->add(new $commandClass);
        } else {
            $this->application->add($command);
        }

    }

    /**
     * Bootstrap an application encapsulating a kernel
     *
     * @param KernelInterface $kernel
     */
    protected function bootstrapApplication(KernelInterface $kernel)
    {
        $this->application = new Application($kernel);
        $this->application->setAutoExit(false);
    }

    /**
     * @param array $command
     * @return array
     */
    protected function buildConsoleInput(array $command)
    {
        $input = ['command' => $this->getCommandName()];
        if (array_key_exists('options', $command)) {
            foreach ($command['options'] as $name => $value) {
                $input[$name] = $value;
            }
        }

        return $input;
    }
}
