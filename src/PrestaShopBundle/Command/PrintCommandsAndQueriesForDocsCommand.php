<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShopBundle\Command;

use PrestaShop\PrestaShop\Core\CommandBus\Parser\CommandHandlerDefinition;
use PrestaShop\PrestaShop\Core\CommandBus\Parser\CommandHandlerDefinitionParser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * Prints all existing commands and queries to .md file for documentation
 */
class PrintCommandsAndQueriesForDocsCommand extends ContainerAwareCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'prestashop:print-docs:commands-and-queries';

    /**
     * Option name for providing destination file path
     */
    private const FILE_PATH_OPTION_NAME = 'file';

    /**
     * Option name for forcing command (remove all confirmations)
     */
    private const FORCE_OPTION_NAME = 'force';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Environment
     */
    private $twigEnv;

    public function __construct(
        Filesystem $filesystem,
        Environment $twigEnv
    ) {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->twigEnv = $twigEnv;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setDescription('Prints available CQRS commands and queries to a file prepared for documentation')
            ->addOption(
                self::FILE_PATH_OPTION_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Path to file into which all commands and queries should be printed'
            )
            ->addOption(
                self::FORCE_OPTION_NAME,
                null,
                InputOption::VALUE_NONE,
                'Forces command to be executed without confirmations'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $filePath = $this->getFilePath($input);

        if (!$this->confirmExistingFileWillBeLost($filePath, $input, $output)) {
            $output->writeln('<comment>Cancelled</comment>');

            return null;
        }

        $this->filesystem->remove($filePath);
        $content = $this->twigEnv->render('src/PrestaShopBundle/Command/views/cqrs-commands-list.md.twig', [
            'commandDefinitions' => $this->getCommandHandlerDefinitions(),
        ]);

        $this->filesystem->dumpFile($filePath, $content);
        $output->writeln(sprintf('<info>dumped commands & queries to %s</info>', $filePath));

        return 0;
    }

    /**
     * @return array<string, array<int, CommandHandlerDefinition>>
     */
    private function getCommandHandlerDefinitions(): array
    {
        $handlerDefinitions = $this->getContainer()->getParameter('prestashop.commands_and_queries');
        /** @var CommandHandlerDefinitionParser $commandHandlerDefinitionParser */
        $commandHandlerDefinitionParser = $this->getContainer()->get('prestashop.core.provider.command_handler_definition_parser');

        $commandDefinitions = [];
        foreach ($handlerDefinitions as $handlerClass => $commandClass) {
            $commandDefinition = $commandHandlerDefinitionParser->parseDefinition($handlerClass, $commandClass);
            if ($commandDefinition->getType() === CommandHandlerDefinition::TYPE_QUERY) {
                $commandDefinitions[CommandHandlerDefinition::TYPE_QUERY][] = $commandDefinition;
                continue;
            }

            $commandDefinitions[CommandHandlerDefinition::TYPE_COMMAND][] = $commandDefinition;
        }

        return $commandDefinitions;
    }

    /**
     * @param string $filePath
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    private function confirmExistingFileWillBeLost(string $filePath, InputInterface $input, OutputInterface $output): bool
    {
        $force = $input->getOption(self::FORCE_OPTION_NAME);

        if ($force || !$this->filesystem->exists($filePath) || !filesize($filePath)) {
            return true;
        }

        $helper = $this->getHelper('question');
        $confirmation = new ConfirmationQuestion(sprintf(
            '<question>File "%s" is not empty. All data will be lost. Proceed?</question>',
            $filePath
        ));

        return (bool) $helper->ask($input, $output, $confirmation);
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getFilePath(InputInterface $input): string
    {
        $filePath = $input->getOption(self::FILE_PATH_OPTION_NAME);

        if (!$filePath || !$this->filesystem->isAbsolutePath($filePath)) {
            throw new InvalidOptionException(sprintf(
                'Option --%s is required. It should contain absolute path to a destination file',
                self::FILE_PATH_OPTION_NAME
            ));
        }

        return $filePath;
    }
}
