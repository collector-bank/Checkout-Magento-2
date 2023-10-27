<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webbhuset\CollectorCheckout\Test\InitiateCheckout;

class TestInitiateCheckout extends Command
{
    /**
     * @var State
     */
    private $appState;
    /**
     * @var InitiateCheckout
     */
    private $initiateCheckout;

    public function __construct(
        State $appState,
        InitiateCheckout $initiateCheckout,
        string $name = null
    ) {
        parent::__construct($name);

        $this->appState = $appState;
        $this->initiateCheckout = $initiateCheckout;
    }

    protected function configure()
    {
        $options = [
            new InputOption(
                'storeId',
                null,
                InputOption::VALUE_REQUIRED,
                'Walley - Test init checkout for store view'
            ),
        ];
        $this->setName('walley:test:initcheckout')
            ->setDescription('Test initiate store view for checkout')
            ->setDefinition($options);

        parent::configure();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->appState->setAreaCode('adminhtml');
        $storeId = (int)$input->getOption('storeId');
        $result = $this->initiateCheckout->execute($storeId);
        $output->writeln($result);

        return 1;
    }
}
