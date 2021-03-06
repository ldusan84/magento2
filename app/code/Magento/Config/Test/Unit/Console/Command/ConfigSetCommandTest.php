<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Config\Test\Unit\Console\Command;

use Magento\Config\App\Config\Type\System;
use Magento\Config\Console\Command\ConfigSet\EmulatedProcessorFacade;
use Magento\Config\Console\Command\ConfigSetCommand;
use Magento\Deploy\Model\DeploymentConfig\ChangeDetector;
use Magento\Deploy\Model\DeploymentConfig\Hash;
use Magento\Deploy\Model\DeploymentConfig\Validator;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test for ConfigSetCommand.
 *
 * @see ConfigSetCommand
 */
class ConfigSetCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigSetCommand
     */
    private $command;

    /**
     * @var EmulatedProcessorFacade|Mock
     */
    private $emulatedProcessorFacadeMock;

    /**
     * @var ChangeDetector|Mock
     */
    private $changeDetectorMock;

    /**
     * @var Hash|Mock
     */
    private $hashMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->emulatedProcessorFacadeMock = $this->getMockBuilder(EmulatedProcessorFacade::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->changeDetectorMock = $this->getMockBuilder(ChangeDetector::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hashMock = $this->getMockBuilder(Hash::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ConfigSetCommand(
            $this->emulatedProcessorFacadeMock,
            $this->changeDetectorMock,
            $this->hashMock
        );
    }

    public function testExecute()
    {
        $this->changeDetectorMock->expects($this->once())
            ->method('hasChanges')
            ->willReturn(false);
        $this->emulatedProcessorFacadeMock->expects($this->once())
            ->method('process')
            ->willReturn('Some message');
        $this->hashMock->expects($this->once())
            ->method('regenerate')
            ->with(System::CONFIG_TYPE);

        $tester = new CommandTester($this->command);
        $tester->execute([
            ConfigSetCommand::ARG_PATH => 'test/test/test',
            ConfigSetCommand::ARG_VALUE => 'value'
        ]);

        $this->assertContains(
            __('Some message')->render(),
            $tester->getDisplay()
        );
        $this->assertSame(Cli::RETURN_SUCCESS, $tester->getStatusCode());
    }

    public function testExecuteNeedsRegeneration()
    {
        $this->changeDetectorMock->expects($this->once())
            ->method('hasChanges')
            ->willReturn(true);
        $this->emulatedProcessorFacadeMock->expects($this->never())
            ->method('process');

        $tester = new CommandTester($this->command);
        $tester->execute([
            ConfigSetCommand::ARG_PATH => 'test/test/test',
            ConfigSetCommand::ARG_VALUE => 'value'
        ]);

        $this->assertContains(
            __('This command is unavailable right now.')->render(),
            $tester->getDisplay()
        );
        $this->assertSame(Cli::RETURN_FAILURE, $tester->getStatusCode());
    }

    public function testExecuteWithException()
    {
        $this->changeDetectorMock->expects($this->once())
            ->method('hasChanges')
            ->willReturn(false);
        $this->emulatedProcessorFacadeMock->expects($this->once())
            ->method('process')
            ->willThrowException(new ValidatorException(__('The "test/test/test" path does not exists')));

        $tester = new CommandTester($this->command);
        $tester->execute([
            ConfigSetCommand::ARG_PATH => 'test/test/test',
            ConfigSetCommand::ARG_VALUE => 'value'
        ]);

        $this->assertContains(
            __('The "test/test/test" path does not exists')->render(),
            $tester->getDisplay()
        );
        $this->assertSame(Cli::RETURN_FAILURE, $tester->getStatusCode());
    }
}
